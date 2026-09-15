# QA HTTP automatizado — Recuperacion / activacion de contrasena (/chgpsswd -> /token_verify -> /updtepsswd).
# Es la UNICA via para activar una cuenta (el alta genera una clave aleatoria que nadie conoce), asi que se
# prueba de punta a punta leyendo el codigo REAL desde Mailpit (API en http://localhost:8025).
# Cubre (revision 2026-09-14): IDOR (sin prueba no hay cambio), CSRF, respuesta neutra, codigo normalizado
# (mayusculas y espacios), codigo de un solo uso, validaciones de la clave nueva, desbloqueo del login tras
# recuperar, cambio con la sesion iniciada y limites por IP pensados para la IP compartida de una oficina.
# Requiere: servidor dev con MAIL_* apuntando a Mailpit, y Mailpit arriba (npm run dev lo levanta).
# Crea y borra su propio usuario (id 91): no toca las contrasenas de nadie mas.
param(
    [string]$BaseUrl    = 'http://localhost:3000',
    [string]$MysqlExe   = 'C:/xampp/mysql/bin/mysql.exe',
    [string]$PassCoord  = 'Test1234*',
    [string]$PassConta  = 'admin1234',
    [string]$MailpitUrl = 'http://localhost:8025'
)
$ErrorActionPreference = 'Stop'
$base = $BaseUrl
$mysql = $MysqlExe
$global:ok = 0; $global:fail = 0
$email = 'qa.recuperacion@sysai.test'

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
function Nueva-Sesion() { return New-Object Microsoft.PowerShell.Commands.WebRequestSession }
function Csrf($sess, $url) {
    $p = Get-Raw $sess $url
    if ($p.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { return $matches[1] }
    return $null
}
function New-Login($correo, $pwd) {
    $s = Nueva-Sesion
    $tok = Csrf $s '/login'
    $resp = Post-Raw $s '/login' @{ email = $correo; password = $pwd; csrf_token = $tok }
    return @{ Sess = $s; Resp = $resp }
}
function Mailpit-Ids() {
    $r = Invoke-RestMethod -Uri "$MailpitUrl/api/v1/search?query=$([uri]::EscapeDataString("to:$email"))"
    # OJO: `$null | ForEach-Object` ejecuta el bloque UNA vez; sin esta guarda, una bandeja vacia
    # devolvia un ID vacio y la consulta del mensaje respondia 404.
    if (-not $r.messages) { return @() }
    return @($r.messages | ForEach-Object { $_.ID } | Where-Object { $_ })
}
function Mailpit-Limpiar() {
    # @(...) en la llamada: un array de UN elemento devuelto por una funcion se desenrolla a
    # string, y $ids[0] pasaba a ser su primer caracter.
    $ids = @(Mailpit-Ids)
    if ($ids.Count -gt 0) {
        $json = ConvertTo-Json -InputObject @{ IDs = $ids }
        Invoke-RestMethod -Method Delete -Uri "$MailpitUrl/api/v1/messages" -ContentType 'application/json' -Body $json | Out-Null
    }
}
# Pide un codigo en la sesion dada y lo lee de Mailpit. Antes borra el cooldown del correo
# (RECUP_COOLDOWN = 2 min) para que el arnes no tenga que esperar entre casos.
function Pedir-Codigo($sess, $correoTecleado) {
    & $mysql -u root sysai -e "DELETE FROM recuperacion_intentos WHERE email='$email' OR ip IN ('127.0.0.1','::1');" | Out-Null
    Mailpit-Limpiar
    $tok = Csrf $sess '/chgpsswd'
    $r = Post-Raw $sess '/chgpsswd' @{ email = $correoTecleado; csrf_token = $tok }
    $codigo = $null
    for ($i = 0; $i -lt 20 -and -not $codigo; $i++) {
        Start-Sleep -Milliseconds 500
        $ids = @(Mailpit-Ids)
        if ($ids.Count -gt 0) {
            $msg = Invoke-RestMethod -Uri "$MailpitUrl/api/v1/message/$($ids[0])"
            if ("$($msg.Text)" -match 'es:\s*([0-9a-f]{10})') { $codigo = $matches[1] }
        }
    }
    return @{ Resp = $r; Codigo = $codigo }
}

Write-Host "`n=== 0) PREPARACION ===" -ForegroundColor Cyan
try { Invoke-RestMethod -Uri "$MailpitUrl/api/v1/info" | Out-Null }
catch { Write-Host "Mailpit no responde en $MailpitUrl (npm run dev lo levanta). Abortando." -ForegroundColor Red; exit 1 }
& $mysql -u root sysai -e "DELETE FROM login_intentos WHERE email='$email'; DELETE FROM usuario WHERE id=91; DELETE FROM persona WHERE id=91; INSERT INTO persona (id, nro_documento, apellido_paterno, apellido_materno, nombres, telefono, fecha) VALUES (91, 79900091, 'QA', 'RECUPERACION', 'USUARIO QA', NULL, NOW()); INSERT INTO usuario (id, persona_id, cargo_id, email, password, fecha) VALUES (91, 91, 2, '$email', '`$2y`$10`$UDUnNShjObLdfWgjxk6MTuZlbM7/guNFkupgQwe91G0BMDMFTpZYq', NOW());" | Out-Null
Assert ((Q "SELECT COUNT(*) FROM usuario WHERE id=91;") -eq '1') "Usuario QA desechable creado (id 91)"

Write-Host "`n=== 1) SIN PRUEBA NO HAY CAMBIO (IDOR) + CSRF + RESPUESTA NEUTRA ===" -ForegroundColor Cyan
$s = Nueva-Sesion
$r = Get-Raw $s '/updtepsswd'
Assert ($r.Status -eq 302 -and "$($r.Location)" -eq '/chgpsswd') "/updtepsswd sin haber verificado un codigo -> /chgpsswd"
$r = Get-Raw $s '/updtepsswd?id=91'
Assert ($r.Status -eq 302 -and "$($r.Location)" -eq '/chgpsswd') "/updtepsswd?id=91 no sirve de nada (la identidad no viene de la URL)"
$r = Post-Raw $s '/chgpsswd' @{ email = $email }
Assert ($r.Status -eq 403) "POST /chgpsswd sin token CSRF -> 403 ($($r.Status))"
$tok = Csrf $s '/chgpsswd'
$r = Post-Raw $s '/chgpsswd' @{ email = 'no-es-un-correo'; csrf_token = $tok }
Assert ($r.Status -eq 200 -and $r.Body -match 'Ingrese su correo') "Correo con formato invalido: aviso visible, no avanza"
$r = Post-Raw $s '/chgpsswd' @{ email = 'no.registrado@sysai.test'; csrf_token = $tok }
Assert ($r.Status -eq 302 -and "$($r.Location)" -eq '/token_verify') "Correo NO registrado: misma respuesta que uno registrado (sin enumeracion)"

Write-Host "`n=== 2) SOLICITUD: EL CODIGO LLEGA Y SE GUARDA HASHEADO ===" -ForegroundColor Cyan
$p = Pedir-Codigo $s "  $email  "
Assert ($p.Resp.Status -eq 302 -and "$($p.Resp.Location)" -eq '/token_verify') "Solicitud con espacios alrededor del correo -> /token_verify"
Assert ($p.Codigo) "El codigo llega por correo (Mailpit) con el formato esperado ($($p.Codigo))"
$guardado = Q "SELECT CONCAT(LENGTH(reset_token),'|',reset_token_expira > NOW()) FROM usuario WHERE id=91;"
Assert ($guardado -eq '64|1') "En BD solo queda el hash sha256 del codigo, con vencimiento ('$guardado')"

Write-Host "`n=== 3) VERIFICACION: NORMALIZADA Y DE UN SOLO USO ===" -ForegroundColor Cyan
$tok = Csrf $s '/token_verify'
$r = Post-Raw $s '/token_verify' @{ reset_token = "  $("$($p.Codigo)".ToUpper()) "; csrf_token = $tok }
Assert ($r.Status -eq 302 -and "$($r.Location)" -eq '/updtepsswd') "Codigo en MAYUSCULAS y con espacios se acepta (movil / pegado desde el correo)"
Assert ((Q "SELECT reset_token IS NULL FROM usuario WHERE id=91;") -eq '1') "El codigo se consume al verificarse (ya no queda en BD)"
$otra = Nueva-Sesion
$tok2 = Csrf $otra '/token_verify'
$r = Post-Raw $otra '/token_verify' @{ reset_token = $p.Codigo; csrf_token = $tok2 }
Assert ($r.Status -eq 200 -and $r.Body -match 'no es correcto') "El mismo codigo NO sirve en otra sesion"

Write-Host "`n=== 4) VALIDACIONES DE LA CLAVE NUEVA ===" -ForegroundColor Cyan
$tok = Csrf $s '/updtepsswd'
$r = Post-Raw $s '/updtepsswd' @{ password = 'NuevaClave1'; password_confirm = 'OtraClave1'; csrf_token = $tok }
Assert ($r.Status -eq 200 -and $r.Body -match 'no coinciden') "Confirmacion distinta: aviso visible"
$r = Post-Raw $s '/updtepsswd' @{ password = 'abc'; password_confirm = 'abc'; csrf_token = $tok }
Assert ($r.Status -eq 200 -and $r.Body -match 'al menos 8') "Menos de 8 caracteres: aviso visible"
$larga = 'a' * 73
$r = Post-Raw $s '/updtepsswd' @{ password = $larga; password_confirm = $larga; csrf_token = $tok }
Assert ($r.Status -eq 200 -and $r.Body -match 'demasiado larga') "Mas de 72 bytes (limite de bcrypt): aviso visible, no se trunca en silencio"

Write-Host "`n=== 5) CAMBIO, DESBLOQUEO Y LOGIN ===" -ForegroundColor Cyan
$filas = ((1..5 | ForEach-Object { "('127.0.0.1','$email',NOW())" }) + (1..5 | ForEach-Object { "('::1','$email',NOW())" })) -join ','
& $mysql -u root sysai -e "INSERT INTO login_intentos (ip, email, fecha) VALUES $filas;" | Out-Null
$nueva = 'QaRecupera2026*'
$r = Post-Raw $s '/updtepsswd' @{ password = $nueva; password_confirm = $nueva; csrf_token = $tok }
Assert ($r.Status -eq 302 -and "$($r.Location)" -eq '/login?resultado=cambio') "Contrasena cambiada -> /login?resultado=cambio"
Assert ((Q "SELECT COUNT(*) FROM login_intentos WHERE email='$email';") -eq '0') "Recuperar la cuenta limpia sus bloqueos de login"
$r = Get-Raw $s '/updtepsswd'
Assert ($r.Status -eq 302 -and "$($r.Location)" -eq '/chgpsswd') "La prueba es de un solo uso: volver a /updtepsswd -> /chgpsswd"
$l = New-Login $email $nueva
Assert ($l.Resp.Status -eq 302 -and "$($l.Resp.Location)" -eq '/') "Login con la contrasena nueva"
$viejo = New-Login $email 'admin1234'
Assert ($viejo.Resp.Status -eq 200) "La contrasena anterior ya no entra"

Write-Host "`n=== 6) CAMBIO CON LA SESION INICIADA ===" -ForegroundColor Cyan
$ls = $l.Sess
$p = Pedir-Codigo $ls $email
$tok = Csrf $ls '/token_verify'
$r = Post-Raw $ls '/token_verify' @{ reset_token = $p.Codigo; csrf_token = $tok }
$tok = Csrf $ls '/updtepsswd'
$otraClave = 'QaRecupera2027*'
$r = Post-Raw $ls '/updtepsswd' @{ password = $otraClave; password_confirm = $otraClave; csrf_token = $tok }
$pag = Get-Raw $ls '/login?resultado=cambio'
Assert ("$($r.Location)" -eq '/login?resultado=cambio' -and $pag.Status -eq 200 -and $pag.Body -match 'se actualiz' -and $pag.Body -notmatch 'se cerr') "Con la sesion iniciada: tras el cambio se ve el aviso de exito, no 'sesion revocada'"

Write-Host "`n=== 7) LIMITES POR IP (OFICINA DETRAS DE UNA SOLA IP) ===" -ForegroundColor Cyan
& $mysql -u root sysai -e "DELETE FROM recuperacion_intentos WHERE ip IN ('127.0.0.1','::1');" | Out-Null
$filas = (1..6 | ForEach-Object { "('127.0.0.1','otro$_@sysai.test','solicitud',NOW()),('::1','otro$_@sysai.test','solicitud',NOW())" }) -join ','
& $mysql -u root sysai -e "INSERT INTO recuperacion_intentos (ip, email, tipo, fecha) VALUES $filas;" | Out-Null
$s7 = Nueva-Sesion
$tok = Csrf $s7 '/chgpsswd'
$r = Post-Raw $s7 '/chgpsswd' @{ email = 'otro7@sysai.test'; csrf_token = $tok }
Assert ($r.Status -eq 302 -and "$($r.Location)" -eq '/token_verify') "Tras 6 solicitudes desde la misma IP, la siguiente sigue pasando (antes el tope era 5)"
$filas = (1..30 | ForEach-Object { "('127.0.0.1','tope$_@sysai.test','solicitud',NOW()),('::1','tope$_@sysai.test','solicitud',NOW())" }) -join ','
& $mysql -u root sysai -e "INSERT INTO recuperacion_intentos (ip, email, tipo, fecha) VALUES $filas;" | Out-Null
$r = Post-Raw $s7 '/chgpsswd' @{ email = 'otro8@sysai.test'; csrf_token = $tok }
Assert ($r.Status -eq 200 -and $r.Body -match 'Demasiadas solicitudes') "Con 30 solicitudes en la ventana, la IP si se frena"

& $mysql -u root sysai -e "DELETE FROM recuperacion_intentos WHERE ip IN ('127.0.0.1','::1') OR email LIKE '%@sysai.test'; DELETE FROM login_intentos WHERE email='$email'; DELETE FROM usuario WHERE id=91; DELETE FROM persona WHERE id=91;" | Out-Null
Mailpit-Limpiar
Write-Host "`n(limpieza) usuario QA, intentos y correos de prueba eliminados" -ForegroundColor DarkGray

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
