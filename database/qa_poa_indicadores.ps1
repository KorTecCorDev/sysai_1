# QA HTTP automatizado — POA Indicadores (item 3)
# Cubre lo verificable por programa: login, CSRF 403, autorizacion por rol,
# transiciones de estado 0->1->2->3, persistencia/limpieza de observacion,
# cross-tenant. Lo puramente visual se valida aparte en el navegador.
param(
    [string]$BaseUrl   = 'http://localhost:3000',          # URL del servidor de desarrollo
    [string]$MysqlExe  = 'C:/xampp/mysql/bin/mysql.exe',   # ruta a mysql.exe de XAMPP
    [string]$PassCoord = 'Test1234*',                      # password de coordinador@sysai.test
    [string]$PassConta = 'admin1234'                       # password de contador@sysai.test (= admin)
)
$ErrorActionPreference = 'Stop'
$base = $BaseUrl
$mysql = $MysqlExe
$passCoord = $PassCoord
$passConta = $PassConta
$global:ok = 0; $global:fail = 0

function Assert($cond, $msg) {
    if ($cond) { Write-Host "  [OK]   $msg" -ForegroundColor Green; $global:ok++ }
    else       { Write-Host "  [FAIL] $msg" -ForegroundColor Red;   $global:fail++ }
}

# GET que NO sigue redirects: devuelve @{Status; Location; Body}
function Get-Raw($sess, $url) {
    try {
        $r = Invoke-WebRequest -Uri "$base$url" -WebSession $sess -MaximumRedirection 0 -UseBasicParsing
        return @{ Status = [int]$r.StatusCode; Location = $r.Headers.Location; Body = $r.Content }
    } catch {
        $resp = $_.Exception.Response
        if ($resp) {
            $loc = $null; try { $loc = $resp.Headers.Location } catch {}
            $body = ''
            try { $sr = New-Object IO.StreamReader($resp.GetResponseStream()); $body = $sr.ReadToEnd() } catch {}
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
            $body = ''
            try { $sr = New-Object IO.StreamReader($resp.GetResponseStream()); $body = $sr.ReadToEnd() } catch {}
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
    # autenticar() hace $_SESSION = [...] (sobrescribe la sesion) -> el token de la
    # pagina /login NO sirve post-login. Tras autenticar, se toma un token fresco de
    # una pagina autenticada con formulario (se genera y persiste para la sesion).
    $s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $login = Get-Csrf $s '/login'
    $r = Post-Raw $s '/login' @{ email = $email; password = $pwd; csrf_token = $login }
    $token = $null
    if ($r.Status -eq 302) {
        foreach ($p in @('/fuente_financiamiento/crear', '/programa/crear', '/poa_indicadores/admin')) {
            $token = Get-Csrf $s $p
            if ($token) { break }
        }
    }
    return @{ Sess = $s; Resp = $r; Token = $token }
}

Write-Host "`n=== 1) AUTENTICACION ===" -ForegroundColor Cyan
$coord = New-Login 'coordinador@sysai.test' $passCoord
Assert ($coord.Resp.Status -eq 302 -and $coord.Resp.Location -eq '/') "Coordinador login OK (302 -> /)"
$conta = New-Login 'contador@sysai.test' $passConta
Assert ($conta.Resp.Status -eq 302 -and $conta.Resp.Location -eq '/') "Contador login OK (302 -> /)"
$bad = New-Login 'coordinador@sysai.test' 'malapass'
Assert ($bad.Resp.Status -eq 200) "Password incorrecto NO autentica (queda en login 200)"

Write-Host "`n=== 2) CSRF (403) ===" -ForegroundColor Cyan
$r = Post-Raw $coord.Sess '/poa_indicadores/crear' @{ }   # sin csrf_token
Assert ($r.Status -eq 403) "POST /crear sin token -> 403 ($($r.Status))"

Write-Host "`n=== 3) AUTORIZACION POR ROL (rutas no registradas -> /error) ===" -ForegroundColor Cyan
$t = $coord.Token
$r = Post-Raw $coord.Sess '/poa_indicadores/aprobar' @{ id = 1; csrf_token = $t }
Assert ($r.Status -eq 302 -and $r.Location -eq '/error') "Coordinador NO tiene ruta /aprobar (-> /error)"
$tc = $conta.Token
$r = Post-Raw $conta.Sess '/poa_indicadores/crear' @{ csrf_token = $tc }
Assert ($r.Status -eq 302 -and $r.Location -eq '/error') "Contador NO tiene ruta /crear (-> /error)"

Write-Host "`n=== 4) CROSS-TENANT (coordinador prog.1 vs doc prog.5) ===" -ForegroundColor Cyan
$r = Get-Raw $coord.Sess '/poa_indicadores/revisar?id=1'
Assert ($r.Status -eq 403) "Coordinador no puede revisar doc de otro programa (403)"

Write-Host "`n=== 5) FLUJO DE ESTADOS (doc nuevo programa 1) ===" -ForegroundColor Cyan
$t = $coord.Token
$r = Post-Raw $coord.Sess '/poa_indicadores/crear' @{ csrf_token = $t }
Assert ($r.Location -like '/poa_indicadores/admin?resultado=1*') "Crear doc -> resultado=1 (Borrador)"
# Reintentar no duplica
$t = $coord.Token
$r = Post-Raw $coord.Sess '/poa_indicadores/crear' @{ csrf_token = $t }
Assert ($r.Location -like '*resultado=11*') "Reintentar crear -> resultado=11 (no duplica)"

# Obtener id del doc de programa 1
$docId = (& $mysql -u root sysai -N -e "SELECT id FROM poa_indicadores WHERE programa_id=1 AND anio=YEAR(CURDATE());").Trim()
Assert ([int]$docId -gt 0) "Doc programa 1 creado en BD (id=$docId)"

# Enviar (Borrador->Enviado) — ahora /enviar exige CSRF
$t = $coord.Token
$r = Post-Raw $coord.Sess '/poa_indicadores/enviar' @{ id = $docId; csrf_token = $t }
$est = (& $mysql -u root sysai -N -e "SELECT estado FROM poa_indicadores WHERE id=$docId;").Trim()
Assert ($est -eq '1') "Enviar -> estado Enviado(1) en BD (estado=$est)"

# Bloqueo de jerarquia con doc Enviado: coordinador POST /resultado/crear -> resultado=14, no inserta
$nAntes = (& $mysql -u root sysai -N -e "SELECT COUNT(*) FROM resultado WHERE programa_id=1;").Trim()
$r = Post-Raw $coord.Sess '/resultado/crear' @{ nombre = 'QA LOCK'; descripcion = 'X'; csrf_token = $coord.Token }
$nDespues = (& $mysql -u root sysai -N -e "SELECT COUNT(*) FROM resultado WHERE programa_id=1;").Trim()
Assert ($r.Location -like '*resultado=14*' -and $nAntes -eq $nDespues) "Jerarquia bloqueada con doc Enviado (-> resultado=14, sin insertar: $nAntes==$nDespues)"

# Contador observa SIN comentario -> resultado=15, sin cambio
$tc = $conta.Token
$r = Post-Raw $conta.Sess '/poa_indicadores/observar' @{ id = $docId; observacion = ''; csrf_token = $tc }
$est = (& $mysql -u root sysai -N -e "SELECT estado FROM poa_indicadores WHERE id=$docId;").Trim()
Assert ($r.Location -like '*resultado=15*' -and $est -eq '1') "Observar sin comentario -> resultado=15, estado sigue 1"

# Contador observa CON comentario -> Observado(2) + observacion
$tc = $conta.Token
$r = Post-Raw $conta.Sess '/poa_indicadores/observar' @{ id = $docId; observacion = 'CORREGIR META ACTIVIDAD 1'; csrf_token = $tc }
$row = (& $mysql -u root sysai -N -e "SELECT estado, observacion FROM poa_indicadores WHERE id=$docId;").Trim()
Assert ($row -match '^2\s+CORREGIR META ACTIVIDAD 1') "Observar con comentario -> Observado(2)+observacion ('$row')"

# Aprobar desde estado invalido (Observado) -> resultado=13
$tc = $conta.Token
$r = Post-Raw $conta.Sess '/poa_indicadores/aprobar' @{ id = $docId; csrf_token = $tc }
$est = (& $mysql -u root sysai -N -e "SELECT estado FROM poa_indicadores WHERE id=$docId;").Trim()
Assert ($r.Location -like '*resultado=13*' -and $est -eq '2') "Aprobar desde Observado -> resultado=13 (sin cambio)"

# Coordinador reenvia (Observado->Enviado) -> observacion se limpia.
# Nota: el framework normaliza null->'' en UPDATE (ActiveRecord, intencional), por lo
# que "limpiar" = cadena vacia. El banner usa !empty(), asi que '' lo oculta igual.
$t = $coord.Token
$r = Post-Raw $coord.Sess '/poa_indicadores/enviar' @{ id = $docId; csrf_token = $t }
$est = (& $mysql -u root sysai -N -e "SELECT estado FROM poa_indicadores WHERE id=$docId;").Trim()
$obsLen = (& $mysql -u root sysai -N -e "SELECT LENGTH(IFNULL(observacion,'')) FROM poa_indicadores WHERE id=$docId;").Trim()
Assert ($est -eq '1' -and $obsLen -eq '0') "Reenviar -> Enviado(1) y observacion vacia (estado=$est, len=$obsLen)"

# Contador aprueba (Enviado->Aprobado)
$tc = $conta.Token
$r = Post-Raw $conta.Sess '/poa_indicadores/aprobar' @{ id = $docId; csrf_token = $tc }
$est = (& $mysql -u root sysai -N -e "SELECT estado FROM poa_indicadores WHERE id=$docId;").Trim()
Assert ($est -eq '3') "Aprobar -> Aprobado(3) en BD (estado=$est)"

Write-Host "`n=== 6) ACCIONES RECHAZAN GET ===" -ForegroundColor Cyan
$r = Get-Raw $coord.Sess '/poa_indicadores/enviar'
Assert ($r.Status -eq 302 -and $r.Location -eq '/error') "GET /enviar no esta registrado como GET (-> /error)"

# Limpieza: eliminar el doc de prueba del programa 1
& $mysql -u root sysai -e "DELETE FROM poa_indicadores WHERE id=$docId;" | Out-Null
Write-Host "`n(limpieza) doc de prueba id=$docId eliminado" -ForegroundColor DarkGray

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
