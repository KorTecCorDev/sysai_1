# QA HTTP automatizado — Rendiciones imputadas al rubro (item 5)
# Login, CSRF 403, autorizacion por rol/cross-tenant, creacion imputada a rubro,
# limite por SOBRE (programa, fuente): el monto de la rendicion no puede exceder el
# saldo disponible del sobre. Verificacion en BD.
#
# NOTA (enmienda de sobres, migr. 020): el tope dejo de ser rubro.monto y paso a ser
# el saldo del sobre (DetalleFinanciamiento::saldoSobre). El codigo de la rendicion
# YA NO se envia en el form: lo autogenera el modelo (REN###); por eso las filas de
# prueba se identifican por serie+numero, no por codigo.
# PRECONDICION del fixture local: el programa 1 debe tener un sobre (detalle_financiamiento
# con monto_asignado) para la fuente 1 con saldo suficiente para la rendicion de la seccion 4.
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
$global:ok = 0; $global:fail = 0

function Assert($cond, $msg) {
    if ($cond) { Write-Host "  [OK]   $msg" -ForegroundColor Green; $global:ok++ }
    else       { Write-Host "  [FAIL] $msg" -ForegroundColor Red;   $global:fail++ }
}
function Q($sql) { return (& $mysql -u root sysai -N -e $sql).Trim() }
function Get-Raw($sess, $url) {
    try { $r = Invoke-WebRequest -Uri "$base$url" -WebSession $sess -MaximumRedirection 0 -UseBasicParsing
        return @{ Status=[int]$r.StatusCode; Location=$r.Headers.Location; Body=$r.Content }
    } catch { $resp=$_.Exception.Response
        if ($resp){ $loc=$null;try{$loc=$resp.Headers.Location}catch{}; $b='';try{$sr=New-Object IO.StreamReader($resp.GetResponseStream());$b=$sr.ReadToEnd()}catch{}
            return @{ Status=[int]$resp.StatusCode; Location=$loc; Body=$b } }
        throw }
}
function Post-Raw($sess, $url, $form) {
    try { $r = Invoke-WebRequest -Uri "$base$url" -WebSession $sess -Method POST -Body $form -MaximumRedirection 0 -UseBasicParsing
        return @{ Status=[int]$r.StatusCode; Location=$r.Headers.Location; Body=$r.Content }
    } catch { $resp=$_.Exception.Response
        if ($resp){ $loc=$null;try{$loc=$resp.Headers.Location}catch{}; $b='';try{$sr=New-Object IO.StreamReader($resp.GetResponseStream());$b=$sr.ReadToEnd()}catch{}
            return @{ Status=[int]$resp.StatusCode; Location=$loc; Body=$b } }
        throw }
}
function Get-Csrf($sess, $url) {
    $r = Get-Raw $sess $url
    if ($r.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { return $matches[1] }
    return $null
}
function New-Login($email, $pwd) {
    $s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $login = Get-Csrf $s '/login'
    $r = Post-Raw $s '/login' @{ email=$email; password=$pwd; csrf_token=$login }
    $token = $null
    # Paginas con form csrf segun rol: contador usa fuente_financiamiento/crear;
    # el coordinador no tiene esa ruta, pero si rubro/crear (de su actividad).
    if ($r.Status -eq 302) { foreach ($p in @('/fuente_financiamiento/crear','/rubro/crear?actividad_id=1','/poa/admin')) { $token = Get-Csrf $s $p; if ($token) { break } } }
    return @{ Sess=$s; Resp=$r; Token=$token }
}

$RUBRO = 2          # rubro del programa 1 (la actividad se deriva por rubro)

Write-Host "`n=== 1) AUTENTICACION ===" -ForegroundColor Cyan
$coord = New-Login 'coordinador@sysai.test' $passCoord
Assert ($coord.Resp.Status -eq 302) "Coordinador login OK"
$conta = New-Login 'contador@sysai.test' $passConta
Assert ($conta.Resp.Status -eq 302) "Contador login OK"

Write-Host "`n=== 2) CSRF (403) ===" -ForegroundColor Cyan
$r = Post-Raw $coord.Sess "/rendicion/crear?rubro_id=$RUBRO" @{ }
Assert ($r.Status -eq 403) "POST /rendicion/crear sin token -> 403 ($($r.Status))"

Write-Host "`n=== 3) CROSS-TENANT (coordinador prog.1 vs rubro de prog.5) ===" -ForegroundColor Cyan
$act5 = Q "SELECT a.id FROM actividad a JOIN producto p ON p.id=a.producto_id JOIN resultado re ON re.id=p.resultado_id WHERE re.programa_id=5 LIMIT 1;"
& $mysql -u root sysai -e "INSERT INTO rubro (actividad_id,categoria_rubro_id,tipo_rubro_id,codigo,nombre,monto,fecha) VALUES ($act5,1,1,'QAXR1','RUBRO QA P5',100,NOW());" | Out-Null
$rubro5 = Q "SELECT id FROM rubro WHERE codigo='QAXR1';"
$r = Get-Raw $coord.Sess "/rendicion/admin?rubro_id=$rubro5"
Assert ($r.Status -eq 403) "Coordinador no accede a rendiciones de rubro de otro programa (403)"
# Listados de la jerarquia: antes bastaba cambiar el id de la URL para LEER los de
# otro programa (auditoria de seguridad 2026-09-14, hallazgo M1).
$prod5 = Q "SELECT producto_id FROM actividad WHERE id=$act5;"
$res5  = Q "SELECT resultado_id FROM producto WHERE id=$prod5;"
$r = Get-Raw $coord.Sess "/producto/admin?resultado_id=$res5"
Assert ($r.Status -eq 403) "Coordinador no lista productos de un resultado de otro programa (403)"
$r = Get-Raw $coord.Sess "/actividad/admin?producto_id=$prod5"
Assert ($r.Status -eq 403) "Coordinador no lista actividades de un producto de otro programa (403)"
$r = Get-Raw $coord.Sess "/rubro/admin?actividad_id=$act5"
Assert ($r.Status -eq 403) "Coordinador no lista rubros (montos) de una actividad de otro programa (403)"
$r = Get-Raw $coord.Sess "/rubro/admin?actividad_id=1"
Assert ($r.Status -eq 200) "Coordinador SI lista los rubros de su propia actividad (200)"
# /rendicionff/* era codigo muerto sin ninguna guarda (hallazgo M2): la ruta ya no existe.
$r = Get-Raw $coord.Sess "/rendicionff/admin?actividad_id=$act5"
Assert ($r.Status -eq 302 -and "$($r.Location)" -like '*/error*') "Ruta retirada /rendicionff/admin -> /error ($($r.Status))"
& $mysql -u root sysai -e "DELETE FROM rubro WHERE id=$rubro5;" | Out-Null

Write-Host "`n=== 4) CREAR RENDICION IMPUTADA AL RUBRO ===" -ForegroundColor Cyan
$nAntes = Q "SELECT COUNT(*) FROM rendicion WHERE rubro_id=$RUBRO;"
# El codigo lo autogenera el modelo (REN###): NO se envia. Identificamos por serie+numero.
$form = @{ tipo_comprobante_id='1'; ff_id='1'; serie='S001'; numero='0001';
           detalle='COMPRA QA'; descripcion='COMENTARIO QA'; ruc='20100100101'; razon_social='PROVEEDOR QA SAC';
           monto='3000'; fecha_original='2026-06-01'; csrf_token=$coord.Token }
$r = Post-Raw $coord.Sess "/rendicion/crear?rubro_id=$RUBRO" $form
$nDespues = Q "SELECT COUNT(*) FROM rendicion WHERE rubro_id=$RUBRO;"
Assert ($r.Location -like "*rubro_id=$RUBRO*resultado=1*" -and [int]$nDespues -eq [int]$nAntes+1) "Rendicion creada e imputada al rubro $RUBRO (filas $nAntes->$nDespues)"
$row = Q "SELECT rubro_id, estado, IFNULL(poa_rendicion_id,'NULL'), monto FROM rendicion WHERE serie='S001' AND numero='0001';"
Assert ($row -match "^$RUBRO\s+0\s+NULL\s+3000") "Fila correcta: rubro_id=$RUBRO, estado=0, poa_rendicion_id=NULL, monto=3000 ('$row')"
# El codigo autogenerado sigue el patron REN###
$cod = Q "SELECT codigo FROM rendicion WHERE serie='S001' AND numero='0001';"
Assert ($cod -match '^REN\d{3,}$') "Codigo de rendicion autogenerado con patron REN### ('$cod')"

Write-Host "`n=== 5) LIMITE POR SOBRE (monto <= disponible del sobre programa 1 / fuente 1) ===" -ForegroundColor Cyan
# Tras la enmienda de sobres (migr. 020) el tope es el saldo del sobre, no rubro.monto.
# Leemos el "disponible para comprometer" actual del sobre (prog 1, ff 1) — mismo criterio
# que DetalleFinanciamiento::saldoSobre: asignado + ingresos OIE - egresos OIE - rendiciones
# de TODO estado — e intentamos excederlo por S/ 1000: debe rechazarse sin insertar.
$disp = Q @"
SELECT ROUND(
   COALESCE((SELECT monto_asignado FROM detalle_financiamiento WHERE programa_id=1 AND fuente_financiamiento_id=1),0)
 + COALESCE((SELECT SUM(oc.monto) FROM otros_ingresos_egresos oie JOIN oie_comprobante oc ON oc.id=oie.oie_comprobante_id WHERE oie.programa_id=1 AND oie.ff_id=1 AND oie.oie_tipo_id=1),0)
 - COALESCE((SELECT SUM(oc.monto) FROM otros_ingresos_egresos oie JOIN oie_comprobante oc ON oc.id=oie.oie_comprobante_id WHERE oie.programa_id=1 AND oie.ff_id=1 AND oie.oie_tipo_id=2),0)
 - COALESCE((SELECT SUM(r.monto) FROM rendicion r JOIN rubro ru ON ru.id=r.rubro_id JOIN actividad a ON a.id=ru.actividad_id JOIN producto p ON p.id=a.producto_id JOIN resultado re ON re.id=p.resultado_id WHERE re.programa_id=1 AND r.ff_id=1),0)
, 2);
"@
$exceso = [math]::Round([double]$disp + 1000, 2)
$form2 = @{ tipo_comprobante_id='1'; ff_id='1'; serie='S001'; numero='0002';
            detalle='COMPRA QA2'; descripcion='COMENTARIO QA2'; ruc='20100100101'; razon_social='PROVEEDOR QA SAC';
            monto="$exceso"; fecha_original='2026-06-02'; csrf_token=$coord.Token }
$r = Post-Raw $coord.Sess "/rendicion/crear?rubro_id=$RUBRO" $form2
$existe = Q "SELECT COUNT(*) FROM rendicion WHERE serie='S001' AND numero='0002';"
Assert ($existe -eq '0') "Rendicion que excede el sobre NO se inserta (monto=$exceso > disponible=$disp)"
Assert ($r.Body -match 'excede el saldo disponible del sobre') "Muestra error de limite de sobre excedido"

Write-Host "`n=== 6) AUTORIZACION GET de eliminar ===" -ForegroundColor Cyan
$r = Get-Raw $coord.Sess '/rendicion/eliminar'
Assert ($r.Status -eq 302) "GET /rendicion/eliminar no ejecuta (redirige)"

# Limpieza: las rendiciones de prueba se identifican por su serie/numero de comprobante
& $mysql -u root sysai -e "DELETE FROM rendicion WHERE serie='S001' AND numero IN ('0001','0002','0003');" | Out-Null
Write-Host "`n(limpieza) rendiciones de prueba eliminadas" -ForegroundColor DarkGray

# Endurecimiento de la auditoria 2026-09-14. Va al FINAL porque el ultimo paso cierra
# la sesion del coordinador.
Write-Host "`n=== 7) ENDURECIMIENTO (archivos de rutas, logout por POST) ===" -ForegroundColor Cyan
$r = Get-Raw $coord.Sess '/iadmin.php'
Assert ($r.Status -eq 404) "Archivo de rutas pedido directo (/iadmin.php) -> 404, sin fatal error ($($r.Status))"
$r = Get-Raw $coord.Sess '/logout'
$sigue = Get-Raw $coord.Sess '/rubro/admin?actividad_id=1'
Assert ("$($r.Location)" -like '*/error*' -and $sigue.Status -eq 200) "GET /logout ya no cierra la sesion (una pagina externa no puede forzar el cierre)"
$r = Post-Raw $coord.Sess '/logout' @{ }
$sigue = Get-Raw $coord.Sess '/rubro/admin?actividad_id=1'
Assert ($r.Status -eq 403 -and $sigue.Status -eq 200) "POST /logout sin token -> 403 y la sesion sigue viva ($($r.Status))"
$r = Post-Raw $coord.Sess '/logout' @{ csrf_token=$coord.Token }
Assert ("$($r.Location)" -like '*/login*') "POST /logout con token cierra la sesion -> /login"
$r = Get-Raw $coord.Sess '/rubro/admin?actividad_id=1'
Assert ($r.Status -eq 302 -and "$($r.Location)" -like '*/login*') "Tras cerrar sesion, una ruta protegida redirige al login"

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
