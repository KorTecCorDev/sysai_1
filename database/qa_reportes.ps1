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

Write-Host "`n=== 4) CONTENIDO DEL EXCEL DE RENDICION (celda a celda, item 9) ===" -ForegroundColor Cyan
# El builder (migr. 034) alinea las sumas por RUBRO, cuenta SOLO aprobadas del año,
# etiqueta el TOTAL y agrega la fila de TRANSFERENCIA A PROGRAMA INSTITUCIONAL.
function Q($sql) { return (& $mysql -u root sysai -N -e $sql).Trim() }
$tokPost = $null
foreach ($p in @('/fuente_financiamiento/crear', '/poa/admin')) {
    $pg = Get-Raw $sess $p
    if ($pg.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { $tokPost = $matches[1]; break }
}
# Transferencia de 4000 (prog 1 -> Institucional, ff 1) para la fila del reporte.
Post-Raw $sess '/dfinanciamiento/crear?programa_id=1' @{ 'transferencia[fuente_financiamiento_id]'='1'; 'transferencia[monto]'='4000'; csrf_token=$tokPost } | Out-Null
# Rendicion APROBADA del año (aparece) y PENDIENTE (excluida), sobre el rubro 2 del fixture.
$hoy = Get-Date -Format 'yyyy-MM-dd'
& $mysql -u root sysai -e "INSERT INTO rendicion (rubro_id,tipo_comprobante_id,ff_id,codigo,serie,numero,detalle,ruc,razon_social,monto,estado,fecha_original,fecha) VALUES (2,1,1,'QARX1','SQAR','XLSX0001','QA XLSX APROBADA','20100100101','PROVEEDOR QA SAC',777.77,1,'$hoy',NOW()),(2,1,1,'QARX2','SQAR','XLSX0002','QA XLSX PENDIENTE','20100100101','PROVEEDOR QA SAC',555.55,0,'$hoy',NOW());" | Out-Null

$resp = Get-Raw $sess '/reporte/poarendicion'
Assert ($resp.Status -eq 200 -and $resp.Body -match 'descargarReporte') "Reporte de rendicion regenerado"

$xlsx = Join-Path (Split-Path $PSScriptRoot -Parent) 'views\reporte\storage\reports\reporte_poa_rendicion_contador.xlsx'
$dump = & php (Join-Path $PSScriptRoot 'qa_leer_xlsx.php') $xlsx
Assert ($LASTEXITCODE -eq 0 -and $dump.Count -gt 0) "qa_leer_xlsx.php vuelca el archivo ($($dump.Count) celdas)"
# Mapa CELDA -> VALOR
$celdas = @{}
foreach ($linea in $dump) { $p = $linea -split "`t", 2; if ($p.Count -eq 2) { $celdas[$p[0]] = $p[1] } }

$anio = (Get-Date).Year
Assert (($celdas.Values | Where-Object { $_ -like "RENDICI*N - $anio - PROGRAMA*" }).Count -ge 1) "Titulo del bloque: RENDICION - $anio - PROGRAMA ..."
Assert (($celdas.Values | Where-Object { $_ -eq 'TOTAL RENDIDO' }).Count -ge 1) "Encabezado de seccion TOTAL RENDIDO presente"
Assert (($celdas.Values | Where-Object { $_ -eq 'TOTAL' }).Count -ge 1) "Fila de totales ETIQUETADA (TOTAL)"

# La suma aprobada (777.77) debe estar en la columna de la fuente 1 (K) EN LA FILA del rubro 2.
$rubroNombre = Q "SELECT nombre FROM rubro WHERE id=2;"
$tipoRubro = Q "SELECT tipo_rubro_id FROM rubro WHERE id=2;"
$colRubro = if ($tipoRubro -eq '1') { 'C' } else { 'E' }
$filaRubro = $null
foreach ($k in $celdas.Keys) { if ($k -match "^$colRubro(\d+)$" -and $celdas[$k] -eq $rubroNombre) { $filaRubro = $Matches[1]; break } }
Assert ($filaRubro -and $celdas["K$filaRubro"] -eq '777.77') "Suma aprobada 777.77 en la FILA de su rubro (${colRubro}$filaRubro -> K$filaRubro='$($celdas["K$filaRubro"])')"
Assert (($celdas.Values | Where-Object { $_ -eq '555.55' }).Count -eq 0) "Rendicion PENDIENTE (555.55) excluida del reporte"

# Fila de transferencia: etiqueta y monto 4000 en G de la misma fila.
$filaTransfer = $null
foreach ($k in $celdas.Keys) { if ($k -match '^A(\d+)$' -and $celdas[$k] -eq 'TRANSFERENCIA A PROGRAMA INSTITUCIONAL') { $filaTransfer = $Matches[1]; break } }
Assert ($filaTransfer -and $celdas["G$filaTransfer"] -eq '4000') "Fila TRANSFERENCIA A PROGRAMA INSTITUCIONAL con monto en G (G$filaTransfer='$($celdas["G$filaTransfer"])')"

# Limpieza: rendiciones de prueba fuera y transferencia a 0 (elimina registro y sobre derivado).
& $mysql -u root sysai -e "DELETE FROM rendicion WHERE serie='SQAR';" | Out-Null
Post-Raw $sess '/dfinanciamiento/crear?programa_id=1' @{ 'transferencia[fuente_financiamiento_id]'='1'; 'transferencia[monto]'='0'; csrf_token=$tokPost } | Out-Null
Write-Host "`n(limpieza) rendiciones y transferencia de prueba eliminadas" -ForegroundColor DarkGray

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
