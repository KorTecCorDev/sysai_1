# QA HTTP automatizado — Item 8: cierre anual + visibilidad de saldos por POA.
# El cierre es un SNAPSHOT manual del Contador sobre fuente_presupuesto_anual
# (upsert por fuente/año, re-cerrable); las cifras salen del desglose EN VIVO:
#   monto_inicial = fuente.presupuesto · comprometido = suma de sobres ·
#   contable = inicial + ingresos - rendiciones APROBADAS - otros egresos.
# Regla de visibilidad: los saldos por sobre muestran cifras solo si el POA
# Presupuestal del programa (año vigente) está APROBADO; si no, badge.
# Requiere el fixture seed_qa.sql (fuentes 200000/100000; sobres 100000/50000).
param(
    [string]$BaseUrl   = 'http://localhost:3000',
    [string]$MysqlExe  = 'C:/xampp/mysql/bin/mysql.exe',
    [string]$PassCoord = 'Test1234*',
    [string]$PassConta = 'admin1234'
)
$ErrorActionPreference = 'Stop'
$base = $BaseUrl
$mysql = $MysqlExe
$anio = (Get-Date).Year
$global:ok = 0; $global:fail = 0

function Assert($cond, $msg) {
    if ($cond) { Write-Host "  [OK]   $msg" -ForegroundColor Green; $global:ok++ }
    else       { Write-Host "  [FAIL] $msg" -ForegroundColor Red;   $global:fail++ }
}
function Q($sql) { return (& $mysql -u root sysai -N -e $sql).Trim() }
function Get-Raw($sess, $url) {
    try {
        $resp = Invoke-WebRequest -Uri "$base$url" -WebSession $sess -MaximumRedirection 0 -UseBasicParsing
        return @{ Status = [int]$resp.StatusCode; Location = $resp.Headers.Location; Body = $resp.Content }
    } catch {
        $rp = $_.Exception.Response
        if ($rp) {
            $loc = $null; try { $loc = $rp.Headers.Location } catch {}
            $body = ''; try { $sr = New-Object IO.StreamReader($rp.GetResponseStream()); $body = $sr.ReadToEnd() } catch {}
            return @{ Status = [int]$rp.StatusCode; Location = $loc; Body = $body }
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
        if ($rp) {
            $loc = $null; try { $loc = $rp.Headers.Location } catch {}
            return @{ Status = [int]$rp.StatusCode; Location = $loc }
        }
        throw
    }
}
function New-Login($email, $pwd) {
    $s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $pag = Get-Raw $s '/login'
    $login = if ($pag.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { $matches[1] }
    $resp = Post-Raw $s '/login' @{ email = $email; password = $pwd; csrf_token = $login }
    $token = $null
    if ($resp.Status -eq 302) {
        foreach ($p in @('/saldos_contables/saldos', '/resultado/crear')) {
            $pag = Get-Raw $s $p
            if ($pag.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { $token = $matches[1]; break }
        }
    }
    return @{ Sess = $s; Resp = $resp; Token = $token }
}

Write-Host "`n=== 1) AUTENTICACION Y AUTORIZACION ===" -ForegroundColor Cyan
$conta = New-Login 'contador@sysai.test' $PassConta
Assert ($conta.Resp.Status -eq 302 -and $conta.Token) "Contador login OK + token"
$coord = New-Login 'coordinador@sysai.test' $PassCoord
Assert ($coord.Resp.Status -eq 302) "Coordinador login OK"

$r = Post-Raw $coord.Sess '/cierre_anual/guardar' @{ csrf_token = $coord.Token }
Assert ($r.Status -eq 302 -and $r.Location -eq '/error') "Coordinador NO tiene ruta /cierre_anual/guardar (-> /error)"

Write-Host "`n=== 2) CSRF (403) ===" -ForegroundColor Cyan
$r = Post-Raw $conta.Sess '/cierre_anual/guardar' @{ }
Assert ($r.Status -eq 403) "POST /cierre_anual/guardar sin token -> 403 ($($r.Status))"

Write-Host "`n=== 3) PANTALLA ANTES DEL CIERRE ===" -ForegroundColor Cyan
$r = Get-Raw $conta.Sess '/saldos_contables/saldos'
Assert ($r.Status -eq 200 -and $r.Body -match 'Registrar cierre del a') "Saldos muestra el boton de cierre (200)"
Assert ($r.Body -match 'no hay cierres registrados') "Historico vacio: aviso 'no hay cierres registrados'"

Write-Host "`n=== 4) CIERRE: SNAPSHOT CORRECTO ===" -ForegroundColor Cyan
$r = Post-Raw $conta.Sess '/cierre_anual/guardar' @{ csrf_token = $conta.Token }
Assert ($r.Location -like '*resultado=23*') "Cierre registrado -> resultado=23"
$filas = Q "SELECT COUNT(*) FROM fuente_presupuesto_anual WHERE anio='$anio';"
Assert ($filas -eq '2') "Una fila por fuente del anio $anio (filas=$filas)"
$f1 = Q "SELECT CONCAT(monto_inicial,'|',presupuesto_comprometido,'|',presupuesto_contable) FROM fuente_presupuesto_anual WHERE fuente_financiamiento_id=1 AND anio='$anio';"
Assert ($f1 -eq '200000.00|100000.00|200000.00') "Fuente 1: inicial|comprometido|contable = 200000|100000|200000 ('$f1')"
$f2 = Q "SELECT CONCAT(monto_inicial,'|',presupuesto_comprometido,'|',presupuesto_contable) FROM fuente_presupuesto_anual WHERE fuente_financiamiento_id=2 AND anio='$anio';"
Assert ($f2 -eq '100000.00|50000.00|100000.00') "Fuente 2: inicial|comprometido|contable = 100000|50000|100000 ('$f2')"

Write-Host "`n=== 5) RE-CIERRE: UPSERT, NO DUPLICA, CIFRAS FRESCAS ===" -ForegroundColor Cyan
# Una rendicion APROBADA de 123.45 contra la fuente 1 debe bajar su contable al re-cerrar
& $mysql -u root sysai -e "INSERT INTO rendicion (rubro_id,tipo_comprobante_id,ff_id,codigo,serie,numero,detalle,ruc,razon_social,monto,estado,fecha_original,fecha) VALUES (1,1,1,'QAC8A','S001','1','QA CIERRE','20123456789','QA RAZON',123.45,1,'$anio-01-01',NOW());" | Out-Null
$r = Post-Raw $conta.Sess '/cierre_anual/guardar' @{ csrf_token = $conta.Token }
$filas = Q "SELECT COUNT(*) FROM fuente_presupuesto_anual WHERE anio='$anio';"
$f1 = Q "SELECT presupuesto_contable FROM fuente_presupuesto_anual WHERE fuente_financiamiento_id=1 AND anio='$anio';"
Assert ($r.Location -like '*resultado=23*' -and $filas -eq '2') "Re-cierre no duplica filas (siguen $filas)"
Assert ($f1 -eq '199876.55') "Contable de fuente 1 refleja la rendicion aprobada (200000-123.45=$f1)"
$r = Get-Raw $conta.Sess '/saldos_contables/saldos'
Assert ($r.Body -match 'se REEMPLAZAR') "El boton avisa que el re-cierre reemplaza el snapshot existente"

Write-Host "`n=== 6) ADVERTENCIA DE RENDICIONES PENDIENTES ===" -ForegroundColor Cyan
& $mysql -u root sysai -e "INSERT INTO rendicion (rubro_id,tipo_comprobante_id,ff_id,codigo,serie,numero,detalle,ruc,razon_social,monto,estado,fecha_original,fecha) VALUES (1,1,1,'QAC8P','S001','2','QA PENDIENTE','20123456789','QA RAZON',50.00,0,'$anio-01-01',NOW());" | Out-Null
$r = Get-Raw $conta.Sess '/saldos_contables/saldos'
Assert ($r.Body -match 'PENDIENTE') "Con una rendicion pendiente, el confirm del cierre lo advierte (no bloquea)"

Write-Host "`n=== 7) VISIBILIDAD: SALDO DE SOBRE SOLO CON POA APROBADO ===" -ForegroundColor Cyan
# Fixture: programa 1 tiene sobres pero NO tiene POA del anio -> cifras ocultas
Assert ($r.Body -match 'POA no aprobado') "Sin POA aprobado: badge 'POA no aprobado' en los sobres"
& $mysql -u root sysai -e "INSERT INTO poa (programa_id,usuario_id,anio,presupuesto,estado,fecha) VALUES (1,3,$anio,28000,3,NOW());" | Out-Null
$r = Get-Raw $conta.Sess '/saldos_contables/saldos'
Assert ($r.Body -notmatch 'POA no aprobado') "Con POA Aprobado(3): las cifras del sobre se muestran (sin badge)"

# Limpieza: datos de prueba fuera (el fixture no trae cierres ni estos registros)
& $mysql -u root sysai -e "DELETE FROM rendicion WHERE codigo IN ('QAC8A','QAC8P'); DELETE FROM poa WHERE programa_id=1 AND anio=$anio; DELETE FROM fuente_presupuesto_anual WHERE anio='$anio';" | Out-Null
Write-Host "`n(limpieza) rendiciones, poa y cierres de prueba eliminados" -ForegroundColor DarkGray

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
