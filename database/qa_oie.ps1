# QA HTTP automatizado — Otros Ingresos/Egresos (item 7)
# Solo Contador (coordinador sin rutas), CSRF 419, ingreso hibrido (al total de la
# fuente con programa NULL / al sobre de un programa), egreso con programa obligatorio,
# vinculo (sobre) existente y tope por sobre (incluida la exclusion del propio OIE al
# editar). Verificacion en BD (tablas + vistas de saldo).
param(
    [string]$BaseUrl    = 'http://localhost:3000',          # URL del servidor de desarrollo
    [string]$MysqlExe   = 'C:/xampp/mysql/bin/mysql.exe',   # ruta a mysql.exe de XAMPP
    [string]$EmailCoord = 'coordinador@sysai.test',         # email del coordinador de prueba
    [string]$EmailConta = 'contador@sysai.test',            # email del contador de prueba
    [string]$PassCoord  = 'Test1234*',                      # password del coordinador
    [string]$PassConta  = 'admin1234'                       # password del contador
)
$ErrorActionPreference = 'Stop'
$base = $BaseUrl
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
    if ($r.Status -eq 302) { foreach ($p in @('/ingreso_egreso/crear','/fuente_financiamiento/crear','/poa/admin')) { $token = Get-Csrf $s $p; if ($token) { break } } }
    return @{ Sess=$s; Resp=$r; Token=$token }
}
# Formulario completo de OIE (oie[...] + oie_comprobante[...])
function Form-Oie($tipo, $ff, $programa, $codigo, $monto, $token) {
    return @{
        'oie[oie_tipo_id]' = $tipo; 'oie[ff_id]' = $ff; 'oie[programa_id]' = $programa;
        'oie[codigo]' = $codigo; 'oie[descripcion]' = "OIE QA $codigo";
        'oie_comprobante[oie_tipo_comprobante_id]' = '1'; 'oie_comprobante[serie]' = 'Q001';
        'oie_comprobante[numero]' = '0001'; 'oie_comprobante[descripcion]' = "COMPROBANTE QA $codigo";
        'oie_comprobante[ruc]' = '20100100101'; 'oie_comprobante[razon_social]' = 'BENEFACTOR QA SAC';
        'oie_comprobante[monto]' = $monto; 'oie_comprobante[fecha_original]' = '2026-07-01';
        csrf_token = $token
    }
}
# Limpieza de datos QA (idempotente; tambien al inicio por si un run previo fallo)
function Cleanup {
    # primero los OIE (referencian al comprobante por FK), luego sus comprobantes
    & $mysql -u root sysai -e @"
CREATE TEMPORARY TABLE tmp_qao AS SELECT oie_comprobante_id FROM otros_ingresos_egresos WHERE codigo LIKE 'QAO%';
DELETE FROM otros_ingresos_egresos WHERE codigo LIKE 'QAO%';
DELETE FROM oie_comprobante WHERE id IN (SELECT oie_comprobante_id FROM tmp_qao);
DROP TEMPORARY TABLE tmp_qao;
DELETE df FROM detalle_financiamiento df JOIN fuente_financiamiento f ON f.id = df.fuente_financiamiento_id WHERE f.codigo = 'QAFF9';
DELETE FROM fuente_financiamiento WHERE codigo = 'QAFF9';
"@ | Out-Null
}

Cleanup
# Datos de prueba: fuente QA (presupuesto 10000) + sobre de 4000 para el programa 1.
# El programa 2 queda SIN vinculo con la fuente QA (para probar 'sobre inexistente').
& $mysql -u root sysai -e "INSERT INTO fuente_financiamiento (codigo,nombre,descripcion,presupuesto,fecha) VALUES ('QAFF9','FUENTE QA OIE','QA',10000,NOW());" | Out-Null
$ffQa = Q "SELECT id FROM fuente_financiamiento WHERE codigo='QAFF9';"
& $mysql -u root sysai -e "INSERT INTO detalle_financiamiento (programa_id,fuente_financiamiento_id,monto_asignado,fecha) VALUES (1,$ffQa,4000,NOW());" | Out-Null

Write-Host "`n=== 1) AUTENTICACION ===" -ForegroundColor Cyan
$conta = New-Login $EmailConta $PassConta
Assert ($conta.Resp.Status -eq 302) "Contador login OK"
Assert ($null -ne $conta.Token) "Contador obtiene token CSRF"
$coord = New-Login $EmailCoord $PassCoord
Assert ($coord.Resp.Status -eq 302) "Coordinador login OK"

Write-Host "`n=== 2) SOLO CONTADOR (coordinador sin rutas OIE) ===" -ForegroundColor Cyan
$r = Get-Raw $coord.Sess '/ingreso_egreso/admin'
Assert ($r.Location -like '*/error*') "Coordinador GET /ingreso_egreso/admin -> redirige a /error"
$r = Post-Raw $coord.Sess '/ingreso_egreso/crear' @{ }
Assert ($r.Location -like '*/error*' -or $r.Status -eq 419) "Coordinador POST /ingreso_egreso/crear no pasa"

Write-Host "`n=== 3) CSRF (419) ===" -ForegroundColor Cyan
$r = Post-Raw $conta.Sess '/ingreso_egreso/crear' @{ 'oie[codigo]'='QAOX' }
Assert ($r.Status -eq 419) "POST /ingreso_egreso/crear sin token -> 419 ($($r.Status))"

Write-Host "`n=== 4) INGRESO HIBRIDO: AL TOTAL DE LA FUENTE (programa NULL) ===" -ForegroundColor Cyan
$r = Post-Raw $conta.Sess '/ingreso_egreso/crear' (Form-Oie '1' $ffQa '' 'QAO001' '500' $conta.Token)
Assert ($r.Status -eq 302 -and $r.Location -like '*resultado=1*') "Ingreso al total creado (302 resultado=1)"
$row = Q "SELECT IFNULL(o.programa_id,'NULL'), oc.monto FROM otros_ingresos_egresos o JOIN oie_comprobante oc ON oc.id=o.oie_comprobante_id WHERE o.codigo='QAO001';"
Assert ($row -match '^NULL\s+500') "BD: programa_id=NULL y monto 500 en oie_comprobante ('$row')"
$saldoFf = Q "SELECT fuente_financiamiento_saldo FROM vista_saldo_fuente_financiamiento WHERE fuente_financiamiento_id=$ffQa;"
Assert ([decimal]$saldoFf -eq 10500) "Saldo de la fuente sube a 10500 ('$saldoFf')"
$saldoSobre = Q "SELECT saldo FROM vista_saldo_sobre WHERE programa_id=1 AND fuente_financiamiento_id=$ffQa;"
Assert ([decimal]$saldoSobre -eq 4000) "El ingreso al total NO entra al sobre (sigue 4000: '$saldoSobre')"

Write-Host "`n=== 5) EGRESO: PROGRAMA OBLIGATORIO Y SOBRE EXISTENTE ===" -ForegroundColor Cyan
$r = Post-Raw $conta.Sess '/ingreso_egreso/crear' (Form-Oie '2' $ffQa '' 'QAO002' '100' $conta.Token)
$existe = Q "SELECT COUNT(*) FROM otros_ingresos_egresos WHERE codigo='QAO002';"
Assert ($existe -eq '0' -and $r.Body -match 'debe indicar el programa') "Egreso sin programa NO se inserta y muestra error"
$r = Post-Raw $conta.Sess '/ingreso_egreso/crear' (Form-Oie '2' $ffQa '2' 'QAO002' '100' $conta.Token)
$existe = Q "SELECT COUNT(*) FROM otros_ingresos_egresos WHERE codigo='QAO002';"
Assert ($existe -eq '0' -and $r.Body -match 'no est&#225;s* vinculada al programa|no está vinculada al programa') "Egreso a programa sin vinculo (sobre inexistente) NO se inserta"

Write-Host "`n=== 6) EGRESO VALIDO Y TOPE POR SOBRE ===" -ForegroundColor Cyan
$r = Post-Raw $conta.Sess '/ingreso_egreso/crear' (Form-Oie '2' $ffQa '1' 'QAO002' '1000' $conta.Token)
$row = Q "SELECT o.programa_id, oc.monto FROM otros_ingresos_egresos o JOIN oie_comprobante oc ON oc.id=o.oie_comprobante_id WHERE o.codigo='QAO002';"
Assert ($r.Location -like '*resultado=1*' -and $row -match '^1\s+1000') "Egreso valido de 1000 al sobre (programa 1) creado ('$row')"
# disponible del sobre = 4000 - 1000 = 3000; un egreso de 3500 debe rechazarse
$r = Post-Raw $conta.Sess '/ingreso_egreso/crear' (Form-Oie '2' $ffQa '1' 'QAO003' '3500' $conta.Token)
$existe = Q "SELECT COUNT(*) FROM otros_ingresos_egresos WHERE codigo='QAO003';"
Assert ($existe -eq '0' -and $r.Body -match 'excede el saldo disponible del sobre') "Egreso de 3500 > disponible 3000 NO se inserta y muestra error"
# huerfanos: el comprobante del intento fallido no debe quedar en BD
$huerfanos = Q "SELECT COUNT(*) FROM oie_comprobante oc LEFT JOIN otros_ingresos_egresos o ON o.oie_comprobante_id=oc.id WHERE oc.razon_social='BENEFACTOR QA SAC' AND o.id IS NULL;"
Assert ($huerfanos -eq '0') "Sin comprobantes huerfanos tras validaciones fallidas"

Write-Host "`n=== 7) INGRESO AL SOBRE + EGRESO EXACTO ===" -ForegroundColor Cyan
$r = Post-Raw $conta.Sess '/ingreso_egreso/crear' (Form-Oie '1' $ffQa '1' 'QAO004' '200' $conta.Token)
Assert ($r.Location -like '*resultado=1*') "Ingreso de 200 dirigido al sobre del programa 1 creado"
# disponible = 4000 + 200 - 1000 = 3200; egreso exacto de 3200 debe pasar
$r = Post-Raw $conta.Sess '/ingreso_egreso/crear' (Form-Oie '2' $ffQa '1' 'QAO005' '3200' $conta.Token)
Assert ($r.Location -like '*resultado=1*') "Egreso exacto al disponible (3200) permitido"
$saldoSobre = Q "SELECT saldo FROM vista_saldo_sobre WHERE programa_id=1 AND fuente_financiamiento_id=$ffQa;"
Assert ([decimal]$saldoSobre -eq 0) "vista_saldo_sobre queda en 0 ('$saldoSobre')"

Write-Host "`n=== 8) EDICION: EL TOPE EXCLUYE EL PROPIO OIE ===" -ForegroundColor Cyan
$oieId = Q "SELECT id FROM otros_ingresos_egresos WHERE codigo='QAO002';"
# mantener 1000 (disponible para este OIE = 4200 - 3200 otros = 1000) -> debe pasar
$r = Post-Raw $conta.Sess "/ingreso_egreso/actualizar?id=$oieId" (Form-Oie '2' $ffQa '1' 'QAO002' '1000' $conta.Token)
Assert ($r.Status -eq 302 -and $r.Location -like '*resultado=2*') "Re-guardar el egreso con el mismo monto pasa (exclusion del propio OIE)"
# subir a 1200 excede el disponible (1000) -> debe fallar sin tocar la BD
$r = Post-Raw $conta.Sess "/ingreso_egreso/actualizar?id=$oieId" (Form-Oie '2' $ffQa '1' 'QAO002' '1200' $conta.Token)
$monto = Q "SELECT oc.monto FROM otros_ingresos_egresos o JOIN oie_comprobante oc ON oc.id=o.oie_comprobante_id WHERE o.id=$oieId;"
Assert ($r.Body -match 'excede el saldo disponible del sobre' -and [decimal]$monto -eq 1000) "Subir a 1200 se rechaza y el monto sigue en 1000 ('$monto')"

Write-Host "`n=== 9) ELIMINAR (borra OIE + su comprobante) ===" -ForegroundColor Cyan
$compId = Q "SELECT oie_comprobante_id FROM otros_ingresos_egresos WHERE codigo='QAO005';"
$oieId5 = Q "SELECT id FROM otros_ingresos_egresos WHERE codigo='QAO005';"
$r = Post-Raw $conta.Sess '/ingreso_egreso/eliminar' @{ id=$oieId5; tipo='ingreso_egreso'; csrf_token=$conta.Token }
$quedanOie = Q "SELECT COUNT(*) FROM otros_ingresos_egresos WHERE id=$oieId5;"
$quedanComp = Q "SELECT COUNT(*) FROM oie_comprobante WHERE id=$compId;"
Assert ($r.Location -like '*resultado=3*' -and $quedanOie -eq '0' -and $quedanComp -eq '0') "Eliminado el OIE y su comprobante"
$saldoSobre = Q "SELECT saldo FROM vista_saldo_sobre WHERE programa_id=1 AND fuente_financiamiento_id=$ffQa;"
Assert ([decimal]$saldoSobre -eq 3200) "El saldo del sobre se restituye a 3200 ('$saldoSobre')"

Cleanup

Write-Host "`n==================================" -ForegroundColor Cyan
Write-Host "RESULTADO: $global:ok OK / $global:fail FAIL" -ForegroundColor $(if ($global:fail -eq 0) { 'Green' } else { 'Red' })
exit $(if ($global:fail -eq 0) { 0 } else { 1 })
