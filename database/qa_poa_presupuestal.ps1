# QA HTTP automatizado — POA Presupuestal (item 4)
# Login, CSRF 419, autorizacion por rol, cross-tenant, flujo de estados 0-1-2-3,
# congelado del presupuesto, bloqueo de rubros (Enviado/Aprobado). Verificacion en BD.
param(
    [string]$BaseUrl   = 'http://localhost:3000',          # URL del servidor de desarrollo
    [string]$MysqlExe  = 'C:/xampp/mysql/bin/mysql.exe',   # ruta a mysql.exe de XAMPP
    [string]$PassCoord = 'Test1234*',                      # password de coordinador@sysai.test
    [string]$PassConta = 'admin1234'                       # password de contador@sysai.test (= admin)
)
$ErrorActionPreference = 'Stop'
$base = $BaseUrl
$passCoord = $PassCoord
$passConta = $PassConta
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
        $r = Invoke-WebRequest -Uri "$base$url" -WebSession $sess -MaximumRedirection 0 -UseBasicParsing
        return @{ Status = [int]$r.StatusCode; Location = $r.Headers.Location; Body = $r.Content }
    } catch {
        $resp = $_.Exception.Response
        if ($resp) {
            $loc = $null; try { $loc = $resp.Headers.Location } catch {}
            $body = ''; try { $sr = New-Object IO.StreamReader($resp.GetResponseStream()); $body = $sr.ReadToEnd() } catch {}
            return @{ Status = [int]$resp.StatusCode; Location = $loc; Body = $body }
        }
        throw
    }
}
function Post-Raw($sess, $url, $form) {
    try {
        $r = Invoke-WebRequest -Uri "$base$url" -WebSession $sess -Method POST -Body $form -MaximumRedirection 0 -UseBasicParsing
        return @{ Status = [int]$r.StatusCode; Location = $r.Headers.Location; Body = $r.Content }
    } catch {
        $resp = $_.Exception.Response
        if ($resp) {
            $loc = $null; try { $loc = $resp.Headers.Location } catch {}
            $body = ''; try { $sr = New-Object IO.StreamReader($resp.GetResponseStream()); $body = $sr.ReadToEnd() } catch {}
            return @{ Status = [int]$resp.StatusCode; Location = $loc; Body = $body }
        }
        throw
    }
}
function Get-Csrf($sess, $url) {
    $r = Get-Raw $sess $url
    if ($r.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { return $matches[1] }
    return $null
}
function New-Login($email, $pwd) {
    $s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $login = Get-Csrf $s '/login'
    $r = Post-Raw $s '/login' @{ email = $email; password = $pwd; csrf_token = $login }
    $token = $null
    if ($r.Status -eq 302) {
        foreach ($p in @('/fuente_financiamiento/crear', '/programa/crear', '/poa/admin')) {
            $token = Get-Csrf $s $p
            if ($token) { break }
        }
    }
    return @{ Sess = $s; Resp = $r; Token = $token }
}

Write-Host "`n=== 1) AUTENTICACION ===" -ForegroundColor Cyan
$coord = New-Login 'coordinador@sysai.test' $passCoord
Assert ($coord.Resp.Status -eq 302) "Coordinador login OK"
$conta = New-Login 'contador@sysai.test' $passConta
Assert ($conta.Resp.Status -eq 302) "Contador login OK"

Write-Host "`n=== 2) CSRF (419) ===" -ForegroundColor Cyan
$r = Post-Raw $coord.Sess '/poa/crear' @{ }
Assert ($r.Status -eq 419) "POST /poa/crear sin token -> 419 ($($r.Status))"

Write-Host "`n=== 3) AUTORIZACION POR ROL (-> /error) ===" -ForegroundColor Cyan
$r = Post-Raw $coord.Sess '/poa/aprobar' @{ id = 1; csrf_token = $coord.Token }
Assert ($r.Status -eq 302 -and $r.Location -eq '/error') "Coordinador NO tiene ruta /poa/aprobar"
$r = Post-Raw $conta.Sess '/poa/crear' @{ csrf_token = $conta.Token }
Assert ($r.Status -eq 302 -and $r.Location -eq '/error') "Contador NO tiene ruta /poa/crear"

Write-Host "`n=== 4) CROSS-TENANT (coordinador prog.1 vs doc prog.5) ===" -ForegroundColor Cyan
& $mysql -u root sysai -e "INSERT INTO poa (programa_id,usuario_id,anio,presupuesto,estado,fecha) VALUES (5,6,$anio,0,1,NOW());" | Out-Null
$otroId = Q "SELECT id FROM poa WHERE programa_id=5 AND anio=$anio LIMIT 1;"
$r = Get-Raw $coord.Sess "/poa/revisar?id=$otroId"
Assert ($r.Status -eq 403) "Coordinador no revisa doc de otro programa (403)"
& $mysql -u root sysai -e "DELETE FROM poa WHERE id=$otroId;" | Out-Null

Write-Host "`n=== 5) FLUJO DE ESTADOS (doc nuevo programa 1, anio $anio) ===" -ForegroundColor Cyan
$r = Post-Raw $coord.Sess '/poa/crear' @{ csrf_token = $coord.Token }
Assert ($r.Location -like '/poa/admin?resultado=1*') "Crear doc -> resultado=1 (Borrador)"
$row = Q "SELECT estado, presupuesto FROM poa WHERE programa_id=1 AND anio=$anio;"
Assert ($row -match '^0\s+28000') "Doc en Borrador(0) con presupuesto=28000 calculado de rubros ('$row')"

$r = Post-Raw $coord.Sess '/poa/crear' @{ csrf_token = $coord.Token }
Assert ($r.Location -like '*resultado=17*') "Reintentar crear -> resultado=17 (no duplica)"

$docId = Q "SELECT id FROM poa WHERE programa_id=1 AND anio=$anio;"

# Enviar (Borrador->Enviado), congela presupuesto
$r = Post-Raw $coord.Sess '/poa/enviar' @{ id = $docId; csrf_token = $coord.Token }
$est = Q "SELECT estado FROM poa WHERE id=$docId;"
Assert ($est -eq '1') "Enviar -> Enviado(1) en BD (estado=$est)"

# Bloqueo de rubros con doc Enviado: coordinador POST /rubro/crear -> resultado=16, no inserta
$nAntes = Q "SELECT COUNT(*) FROM rubro WHERE actividad_id=1;"
$r = Post-Raw $coord.Sess '/rubro/crear?actividad_id=1' @{ nombre='QA'; monto='5'; categoria_rubro_id='1'; tipo_rubro_id='1'; csrf_token=$coord.Token }
$nDespues = Q "SELECT COUNT(*) FROM rubro WHERE actividad_id=1;"
Assert ($r.Location -like '*resultado=16*' -and $nAntes -eq $nDespues) "Rubros bloqueados con doc Enviado (resultado=16, sin insertar: $nAntes==$nDespues)"

# Contador observa sin comentario -> resultado=15, sin cambio
$r = Post-Raw $conta.Sess '/poa/observar' @{ id=$docId; observacion=''; csrf_token=$conta.Token }
$est = Q "SELECT estado FROM poa WHERE id=$docId;"
Assert ($r.Location -like '*resultado=15*' -and $est -eq '1') "Observar sin comentario -> resultado=15, estado sigue 1"

# Contador observa con comentario -> Observado(2)+observacion
$r = Post-Raw $conta.Sess '/poa/observar' @{ id=$docId; observacion='AJUSTAR RUBRO 02'; csrf_token=$conta.Token }
$row = Q "SELECT estado, observacion FROM poa WHERE id=$docId;"
Assert ($row -match '^2\s+AJUSTAR RUBRO 02') "Observar con comentario -> Observado(2)+observacion ('$row')"

# Aprobar desde estado invalido (Observado) -> resultado=13
$r = Post-Raw $conta.Sess '/poa/aprobar' @{ id=$docId; csrf_token=$conta.Token }
$est = Q "SELECT estado FROM poa WHERE id=$docId;"
Assert ($r.Location -like '*resultado=13*' -and $est -eq '2') "Aprobar desde Observado -> resultado=13 (sin cambio)"

# Coordinador reenvia (Observado->Enviado) -> observacion vacia
$r = Post-Raw $coord.Sess '/poa/enviar' @{ id=$docId; csrf_token=$coord.Token }
$est = Q "SELECT estado FROM poa WHERE id=$docId;"
$obsLen = Q "SELECT LENGTH(IFNULL(observacion,'')) FROM poa WHERE id=$docId;"
Assert ($est -eq '1' -and $obsLen -eq '0') "Reenviar -> Enviado(1) y observacion vacia (estado=$est, len=$obsLen)"

# Contador aprueba (Enviado->Aprobado)
$r = Post-Raw $conta.Sess '/poa/aprobar' @{ id=$docId; csrf_token=$conta.Token }
$est = Q "SELECT estado FROM poa WHERE id=$docId;"
Assert ($est -eq '3') "Aprobar -> Aprobado(3) en BD (estado=$est)"

# Bloqueo de rubros tambien con doc Aprobado
$r = Post-Raw $coord.Sess '/rubro/crear?actividad_id=1' @{ nombre='QA2'; monto='5'; categoria_rubro_id='1'; tipo_rubro_id='1'; csrf_token=$coord.Token }
Assert ($r.Location -like '*resultado=16*') "Rubros bloqueados tambien con doc Aprobado (resultado=16)"

Write-Host "`n=== 6) ACCIONES RECHAZAN GET ===" -ForegroundColor Cyan
$r = Get-Raw $coord.Sess '/poa/enviar'
Assert ($r.Status -eq 302 -and $r.Location -eq '/error') "GET /poa/enviar no registrado como GET (-> /error)"

# Limpieza: eliminar el doc de prueba del anio vigente (deja intacto el de 2025)
& $mysql -u root sysai -e "DELETE FROM poa WHERE id=$docId;" | Out-Null
Write-Host "`n(limpieza) doc de prueba id=$docId eliminado" -ForegroundColor DarkGray

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
