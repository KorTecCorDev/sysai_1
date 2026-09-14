# QA HTTP automatizado — Item 10: CRUD de Usuarios (solo Administrador).
# Cubre: autorizacion por rol, CSRF, alta con password provisional hasheado (el
# usuario define su clave via recuperacion), unicidad de email (validacion +
# UNIQUE de la migr. 032), formato de email, vinculo coordinador-programa
# (alta, retiro al cambiar de cargo), anti mass-assignment del password (A2) y
# eliminacion completa (incluidos poa_indicadores por FK, que antes fallaba en
# silencio). Requiere el fixture seed_qa.sql.
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
function New-Login($email, $pwd) {
    $s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $pag = Get-Raw $s '/login'
    $login = if ($pag.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { $matches[1] }
    $resp = Post-Raw $s '/login' @{ email = $email; password = $pwd; csrf_token = $login }
    $token = $null
    if ($resp.Status -eq 302) {
        foreach ($p in @('/usuario/crear', '/resultado/crear')) {
            $pag = Get-Raw $s $p
            if ($pag.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { $token = $matches[1]; break }
        }
    }
    return @{ Sess = $s; Resp = $resp; Token = $token }
}
# Datos base de persona para las altas (el documento/telefono varian por caso)
function Form-Alta($doc, $tel, $email, $cargo, $programa) {
    return @{
        'persona[nro_documento]'    = $doc
        'persona[apellido_paterno]' = 'QA'
        'persona[apellido_materno]' = 'USUARIOS'
        'persona[nombres]'          = 'PRUEBA ITEM DIEZ'
        'persona[telefono]'         = $tel
        'usuario[email]'            = $email
        'usuario[cargo_id]'         = $cargo
        'coordinador_programa[programa_id]' = $programa
    }
}

Write-Host "`n=== 1) AUTENTICACION Y AUTORIZACION (solo admin) ===" -ForegroundColor Cyan
# Admin TEMPORAL propio del arnes: el admin real (id=1) gestiona su password y no
# debe tocarse (regla del proyecto). Hash = 'admin1234' (el mismo del fixture).
& $mysql -u root sysai -e "INSERT INTO persona (id, nro_documento, apellido_paterno, apellido_materno, nombres, telefono, fecha) VALUES (90, 79900090, 'QA', 'ADMIN', 'ADMIN QA ITEM10', NULL, NOW()); INSERT INTO usuario (id, persona_id, cargo_id, email, password, fecha) VALUES (90, 90, 1, 'qa.admin10@sysai.test', '`$2y`$10`$UDUnNShjObLdfWgjxk6MTuZlbM7/guNFkupgQwe91G0BMDMFTpZYq', NOW());" | Out-Null
$admin = New-Login 'qa.admin10@sysai.test' 'admin1234'
Assert ($admin.Resp.Status -eq 302 -and $admin.Token) "Admin (QA temporal) login OK + token"
$conta = New-Login 'contador@sysai.test' $PassConta
$r = Get-Raw $conta.Sess '/usuario/admin'
Assert ($r.Status -eq 302 -and $r.Location -eq '/error') "Contador NO tiene ruta /usuario/admin (-> /error)"

Write-Host "`n=== 2) CSRF (403) ===" -ForegroundColor Cyan
$r = Post-Raw $admin.Sess '/usuario/crear' @{ }
Assert ($r.Status -eq 403) "POST /usuario/crear sin token -> 403 ($($r.Status))"

Write-Host "`n=== 3) ALTA: PASSWORD PROVISIONAL HASHEADO ===" -ForegroundColor Cyan
$form = Form-Alta '79900001' '999900001' 'qa.item10@sysai.test' '2' '0'
$form['csrf_token'] = $admin.Token
$r = Post-Raw $admin.Sess '/usuario/crear' $form
Assert ($r.Location -like '*resultado=1*') "Alta de contador QA -> resultado=1"
$uid = Q "SELECT id FROM usuario WHERE email='qa.item10@sysai.test';"
Assert ($uid -match '^\d+$') "Usuario creado en BD (id=$uid) con su persona"
$hash = Q "SELECT password FROM usuario WHERE id=$uid;"
Assert ($hash -like '$2y$*') "Password provisional HASHEADO bcrypt (un solo hash, inutilizable hasta la recuperacion)"

Write-Host "`n=== 4) EMAIL: DUPLICADO Y FORMATO ===" -ForegroundColor Cyan
$form = Form-Alta '79900002' '999900002' 'qa.item10@sysai.test' '2' '0'
$form['csrf_token'] = $admin.Token
$r = Post-Raw $admin.Sess '/usuario/crear' $form
$nPers = Q "SELECT COUNT(*) FROM persona WHERE nro_documento='79900002';"
Assert ($r.Status -eq 200 -and $r.Body -match 'ya est' -and $nPers -eq '0') "Email duplicado: error visible, sin usuario NI persona huerfana"
Assert ((Q "SHOW INDEX FROM usuario WHERE Key_name='uq_usuario_email';") -ne '') "UNIQUE uq_usuario_email presente en BD (migr. 032)"
$form = Form-Alta '79900003' '999900003' 'no-es-un-email' '2' '0'
$form['csrf_token'] = $admin.Token
$r = Post-Raw $admin.Sess '/usuario/crear' $form
Assert ($r.Status -eq 200 -and $r.Body -match 'formato v') "Email con formato invalido: error visible, no inserta"

Write-Host "`n=== 5) COORDINADOR: VINCULO AL CREAR, RETIRO AL CAMBIAR DE CARGO ===" -ForegroundColor Cyan
& $mysql -u root sysai -e "INSERT INTO programa (id, codigo, nombre, descripcion, tipo_programa_id, fecha) VALUES (7,'PRG007','PROGRAMA QA ITEM10','QA',1,NOW());" | Out-Null
$form = Form-Alta '79900004' '999900004' 'qa.coord10@sysai.test' '3' '7'
$form['csrf_token'] = $admin.Token
$r = Post-Raw $admin.Sess '/usuario/crear' $form
$cid = Q "SELECT id FROM usuario WHERE email='qa.coord10@sysai.test';"
$vinc = Q "SELECT CONCAT(programa_id,'|',activo) FROM coordinador_programa WHERE usuario_id=$cid AND activo=1;"
Assert ($vinc -eq '7|1') "Coordinador QA creado con vinculo activo al programa 7 ('$vinc')"

# Cambio de cargo coordinador -> contador: el vinculo debe quedar inactivo
$pag = Get-Raw $admin.Sess "/usuario/actualizar?id=$cid"
$tokU = if ($pag.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { $matches[1] }
$form = Form-Alta '79900004' '999900004' 'qa.coord10@sysai.test' '2' '0'
$form['csrf_token'] = $tokU
$r = Post-Raw $admin.Sess "/usuario/actualizar?id=$cid" $form
$activos = Q "SELECT COUNT(*) FROM coordinador_programa WHERE usuario_id=$cid AND activo=1;"
Assert ($r.Location -like '*resultado=2*' -and $activos -eq '0') "Al dejar de ser coordinador, el vinculo queda inactivo (activos=$activos)"

Write-Host "`n=== 6) A2: EL PASSWORD NO SE REASIGNA POR POST ===" -ForegroundColor Cyan
$hashAntes = Q "SELECT password FROM usuario WHERE id=$uid;"
$pag = Get-Raw $admin.Sess "/usuario/actualizar?id=$uid"
$tokU = if ($pag.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { $matches[1] }
$form = Form-Alta '79900001' '999900001' 'qa.item10@sysai.test' '2' '0'
$form['usuario[password]'] = 'HACKEADO'
$form['usuario[persona_id]'] = '1'
$form['csrf_token'] = $tokU
$r = Post-Raw $admin.Sess "/usuario/actualizar?id=$uid" $form
$hashDespues = Q "SELECT password FROM usuario WHERE id=$uid;"
Assert ($r.Location -like '*resultado=2*' -and $hashDespues -eq $hashAntes) "Inyectar usuario[password]/persona_id via POST NO cambia nada (A2)"

Write-Host "`n=== 7) ELIMINAR: LIMPIA FKs (poa_indicadores) Y NO MIENTE ===" -ForegroundColor Cyan
# El usuario QA coordinador tiene un POA Indicadores (FK que antes rompia el DELETE en silencio)
& $mysql -u root sysai -e "INSERT INTO poa_indicadores (programa_id, usuario_id, anio, estado, fecha) VALUES (7, $cid, $anio, 0, NOW());" | Out-Null
$r = Post-Raw $admin.Sess '/usuario/eliminar' @{ id = $cid; tipo = 'usuario'; csrf_token = $admin.Token }
$quedaU = Q "SELECT COUNT(*) FROM usuario WHERE id=$cid;"
$quedaP = Q "SELECT COUNT(*) FROM persona WHERE nro_documento='79900004';"
$quedaPI = Q "SELECT COUNT(*) FROM poa_indicadores WHERE usuario_id=$cid;"
Assert ($r.Location -like '*resultado=3*' -and $quedaU -eq '0' -and $quedaP -eq '0' -and $quedaPI -eq '0') "Eliminado REAL: usuario, persona y su poa_indicadores fuera (u=$quedaU p=$quedaP pi=$quedaPI)"

# Limpieza: usuario contador QA + programa de prueba
$r = Post-Raw $admin.Sess '/usuario/eliminar' @{ id = $uid; tipo = 'usuario'; csrf_token = $admin.Token }
Assert ($r.Location -like '*resultado=3*' -and (Q "SELECT COUNT(*) FROM usuario WHERE id=$uid;") -eq '0') "Limpieza: contador QA eliminado por la propia ruta"
Write-Host "`n=== 8) SESION REVALIDADA CONTRA LA BD (auditoria 2026-09-14, M3) ===" -ForegroundColor Cyan
# Cada caso abre una sesion NUEVA del admin QA: la revalidacion la cierra, que es
# justo lo que se comprueba. Al final se restauran cargo y hash originales.
$hashQa = Q "SELECT password FROM usuario WHERE id=90;"
$s8 = New-Login 'qa.admin10@sysai.test' 'admin1234'
$r = Get-Raw $s8.Sess '/usuario/admin'
Assert ($s8.Resp.Status -eq 302 -and $r.Status -eq 200) "Sin cambios en la BD, la sesion sigue valida entre peticiones (200)"
# Mismo password con otro hash bcrypt: equivale a que el usuario cambie su clave.
$hashNuevo = (& php -r "echo password_hash('admin1234', PASSWORD_DEFAULT);").Trim()
& $mysql -u root sysai -e "UPDATE usuario SET password='$hashNuevo' WHERE id=90;" | Out-Null
$r = Get-Raw $s8.Sess '/usuario/admin'
Assert ($r.Status -eq 302 -and "$($r.Location)" -like '/login*') "Cambiar la contrasena cierra las sesiones ya abiertas (-> $($r.Location))"
$s8 = New-Login 'qa.admin10@sysai.test' 'admin1234'
& $mysql -u root sysai -e "UPDATE usuario SET cargo_id=2 WHERE id=90;" | Out-Null
$r = Get-Raw $s8.Sess '/programa/admin'
Assert ($r.Status -eq 302 -and "$($r.Location)" -like '/login*') "Cambiar el cargo cierra la sesion abierta: no conserva los permisos con que entro"
& $mysql -u root sysai -e "UPDATE usuario SET cargo_id=1, password='$hashQa' WHERE id=90;" | Out-Null

Write-Host "`n=== 9) BLOQUEO DE LOGIN POR IP+EMAIL (auditoria 2026-09-14, M7) ===" -ForegroundColor Cyan
# 5 fallos contra el correo desde OTRA IP: antes bastaban para dejar fuera al dueno.
$filasAjenas = (1..5 | ForEach-Object { "('203.0.113.9','qa.admin10@sysai.test',NOW())" }) -join ','
& $mysql -u root sysai -e "INSERT INTO login_intentos (ip, email, fecha) VALUES $filasAjenas;" | Out-Null
$s9 = New-Login 'qa.admin10@sysai.test' 'admin1234'
Assert ($s9.Resp.Status -eq 302) "5 fallos desde OTRA IP no bloquean la cuenta (antes cualquiera podia bloquear a otro)"
& $mysql -u root sysai -e "DELETE FROM login_intentos WHERE email='qa.admin10@sysai.test';" | Out-Null
# 5 fallos desde la MISMA IP (php -S puede ver 127.0.0.1 o ::1): ese email queda bloqueado ahi.
$filasPropias = ((1..5 | ForEach-Object { "('127.0.0.1','qa.admin10@sysai.test',NOW())" }) + (1..5 | ForEach-Object { "('::1','qa.admin10@sysai.test',NOW())" })) -join ','
& $mysql -u root sysai -e "INSERT INTO login_intentos (ip, email, fecha) VALUES $filasPropias;" | Out-Null
$s9 = New-Login 'qa.admin10@sysai.test' 'admin1234'
Assert ($s9.Resp.Status -eq 200 -and $s9.Resp.Body -match 'Demasiados intentos') "5 fallos desde la MISMA IP bloquean ese email aunque la clave sea correcta"
& $mysql -u root sysai -e "DELETE FROM login_intentos WHERE email='qa.admin10@sysai.test';" | Out-Null

& $mysql -u root sysai -e "DELETE FROM programa WHERE id=7; DELETE FROM usuario WHERE id=90; DELETE FROM persona WHERE id=90;" | Out-Null
Write-Host "`n(limpieza) programa de prueba y admin QA temporal eliminados" -ForegroundColor DarkGray

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
