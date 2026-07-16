# QA HTTP automatizado — Reportes (plan de montos, Fases 4-5)
# Las rutas de reporte NUNCA deben reventar: ni con TC registrado ni SIN ningun TC
# (antes: DivisionByZeroError fatal con las tablas de TC vacias — sin cobertura de QA,
# por eso paso inadvertido). Verifica ademas que la pantalla de saldos muestre la
# conversion al cierre con la tasa visible, y "sin tipo de cambio" cuando falta.
# Requiere el fixture seed_qa.sql (usuarios *.test + tipo_cambio con cobertura 2020).
param(
    [string]$BaseUrl   = 'http://localhost:3000',
    [string]$MysqlExe  = 'C:/xampp/mysql/bin/mysql.exe',
    [string]$PassCoord = 'Test1234*',   # no se usa; uniforma la firma para qa_all.ps1
    [string]$PassConta = 'admin1234'
)
$ErrorActionPreference = 'Stop'
$base = $BaseUrl
$mysql = $MysqlExe
$global:ok = 0; $global:fail = 0

function Assert($cond, $msg) {
    if ($cond) { Write-Host "  [OK]   $msg" -ForegroundColor Green; $global:ok++ }
    else       { Write-Host "  [FAIL] $msg" -ForegroundColor Red;   $global:fail++ }
}
function Get-Raw($sess, $url) {
    try {
        $resp = Invoke-WebRequest -Uri "$base$url" -WebSession $sess -MaximumRedirection 0 -UseBasicParsing
        return @{ Status = [int]$resp.StatusCode; Body = $resp.Content }
    } catch {
        $rp = $_.Exception.Response
        if ($rp) {
            $body = ''; try { $sr = New-Object IO.StreamReader($rp.GetResponseStream()); $body = $sr.ReadToEnd() } catch {}
            return @{ Status = [int]$rp.StatusCode; Body = $body }
        }
        throw
    }
}
function Post-Raw($sess, $url, $form) {
    try {
        $resp = Invoke-WebRequest -Uri "$base$url" -WebSession $sess -Method POST -Body $form -MaximumRedirection 0 -UseBasicParsing
        return @{ Status = [int]$resp.StatusCode; Location = $resp.Headers.Location }
    } catch {
        $rp = $_.Exception.Response
        if ($rp) { return @{ Status = [int]$rp.StatusCode; Location = $(try { $rp.Headers.Location } catch { $null }) } }
        throw
    }
}

Write-Host "`n=== 1) AUTENTICACION (contador) ===" -ForegroundColor Cyan
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$pag = Get-Raw $sess '/login'
$tok = if ($pag.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { $matches[1] }
$resp = Post-Raw $sess '/login' @{ email = 'contador@sysai.test'; password = $PassConta; csrf_token = $tok }
Assert ($resp.Status -eq 302) "Contador login OK"

# Las 7 rutas de reporte + saldos. Las *desc generan el Excel en el servidor.
$rutas = @(
    '/reporte/poa',
    '/reporte/poarendicion',
    '/reporte/poarubros',
    '/reporte/rendiciones',
    '/reporte/rendicionesdesc?fechainicio=2020-01-01&fechafin=2030-12-31',
    '/reporte/ingresos',
    '/reporte/ingresosdesc?fechainicio=2020-01-01&fechafin=2030-12-31',
    '/saldos_contables/saldos'
)

Write-Host "`n=== 2) CON TIPO DE CAMBIO (fixture 2020) ===" -ForegroundColor Cyan
foreach ($u in $rutas) {
    $resp = Get-Raw $sess $u
    $sinFatal = ($resp.Body -notmatch 'Fatal error|DivisionByZeroError|Uncaught')
    Assert ($resp.Status -eq 200 -and $sinFatal) "GET $u -> 200 sin fatal ($($resp.Status))"
}
$resp = Get-Raw $sess '/saldos_contables/saldos'
Assert ($resp.Body -match 'TC compra') "Saldos muestra la conversion al cierre con la tasa visible"

Write-Host "`n=== 3) SIN NINGUN TIPO DE CAMBIO (tabla vacia) ===" -ForegroundColor Cyan
& $mysql -u root sysai -e "DELETE FROM tipo_cambio;" | Out-Null
foreach ($u in $rutas) {
    $resp = Get-Raw $sess $u
    $sinFatal = ($resp.Body -notmatch 'Fatal error|DivisionByZeroError|Uncaught')
    Assert ($resp.Status -eq 200 -and $sinFatal) "GET $u sin TC -> 200 sin fatal ($($resp.Status))"
}
$resp = Get-Raw $sess '/saldos_contables/saldos'
Assert ($resp.Body -match 'sin tipo de cambio registrado') "Saldos sin TC avisa 'sin tipo de cambio registrado' (no inventa cifras)"

# Restaurar la cobertura del fixture
& $mysql -u root sysai -e "INSERT INTO tipo_cambio (moneda, fecha_vigencia, compra, venta, origen, usuario_id, fecha) VALUES ('USD','2020-01-01',3.700,3.750,'MANUAL',1,NOW()),('EUR','2020-01-01',4.000,4.050,'MANUAL',1,NOW());" | Out-Null
Write-Host "`n(limpieza) cobertura de TC del fixture restaurada" -ForegroundColor DarkGray

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
