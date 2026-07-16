# QA HTTP automatizado — POA Presupuestal (item 4) + POA Rendicion (item 6)
# Login, CSRF 419, autorizacion por rol, cross-tenant, flujo de estados 0-1-2-3,
# congelado del presupuesto, bloqueo de rubros (Enviado/Aprobado). Verificacion en BD.
# Item 6: al aprobar el POA, las rendiciones del programa pasan a Aprobada(1) y recien
# entonces se descuentan del saldo contable (vista_total_egresos filtra estado=1).
# Item 4 (tope por sobres, 2026-07-15): enviar bajo tope pasa / sobre tope bloquea
# (resultado=20); bajar el sobre tras enviar hace que aprobar bloquee; programa SIN
# sobres (programa 5): rubro/POA/rendicion bloqueados (resultado=19) pero POA
# Indicadores y jerarquia permitidos; Contador no pasa por la puerta.
# Plan de montos (Fase 3): sin cobertura de TC la aprobacion bloquea (resultado=22)
# y al reponer las tasas el aprobar recongela la rendicion pendiente.
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
        # /resultado/crear como fallback: el /poa/admin de un coordinador SIN sobres ya
        # no muestra el formulario "Iniciar POA" (puerta del item 4) y no trae token.
        foreach ($p in @('/fuente_financiamiento/crear', '/programa/crear', '/poa/admin', '/resultado/crear')) {
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
# Migr. 033: el Contador SÍ tiene /poa/crear, pero SOLO para el programa Institucional;
# en un programa normal la guarda redirige con resultado=26 (sin crear documento).
$nDocsAntes = Q "SELECT COUNT(*) FROM poa WHERE programa_id=1 AND anio=$anio;"
$r = Post-Raw $conta.Sess '/poa/crear' @{ programa_id = 1; csrf_token = $conta.Token }
$nDocsDespues = Q "SELECT COUNT(*) FROM poa WHERE programa_id=1 AND anio=$anio;"
Assert ($r.Status -eq 302 -and $r.Location -like '*resultado=26*' -and $nDocsAntes -eq $nDocsDespues) "Contador con /poa/crear solo para el Institucional (programa normal -> resultado=26, sin documento)"

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

# Item 4 — tope por sobres: con sobres reducidos (10000+5000=15000 < 28000) enviar bloquea
& $mysql -u root sysai -e "UPDATE detalle_financiamiento SET monto_asignado=10000 WHERE id=1; UPDATE detalle_financiamiento SET monto_asignado=5000 WHERE id=2;" | Out-Null
$r = Post-Raw $coord.Sess '/poa/enviar' @{ id = $docId; csrf_token = $coord.Token }
$est = Q "SELECT estado FROM poa WHERE id=$docId;"
Assert ($r.Location -like '*resultado=20*' -and $est -eq '0') "Enviar SOBRE el tope (28000 > 15000) -> resultado=20, sigue Borrador (estado=$est)"
& $mysql -u root sysai -e "UPDATE detalle_financiamiento SET monto_asignado=100000 WHERE id=1; UPDATE detalle_financiamiento SET monto_asignado=50000 WHERE id=2;" | Out-Null

# Enviar BAJO el tope (28000 <= 150000) -> Enviado(1), congela presupuesto
$r = Post-Raw $coord.Sess '/poa/enviar' @{ id = $docId; csrf_token = $coord.Token }
$est = Q "SELECT estado FROM poa WHERE id=$docId;"
Assert ($est -eq '1') "Enviar bajo el tope -> Enviado(1) en BD (estado=$est)"

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

# --- Item 6: rendicion PENDIENTE no afecta el saldo; al aprobar el POA se aprueba y descuenta ---
$egresosAntes = Q "SELECT total_egresos FROM vista_total_egresos;"
& $mysql -u root sysai -e "INSERT INTO rendicion (rubro_id,tipo_comprobante_id,ff_id,codigo,serie,numero,detalle,ruc,razon_social,monto,estado,fecha_original,fecha) VALUES (1,1,1,'QAIT6','S001','1','QA ITEM6','20123456789','QA RAZON',123.45,0,'$anio-01-01',NOW());" | Out-Null
$rendId = Q "SELECT id FROM rendicion WHERE codigo='QAIT6' LIMIT 1;"
$estRend = Q "SELECT estado FROM rendicion WHERE id=$rendId;"
$egresosConPend = Q "SELECT total_egresos FROM vista_total_egresos;"
Assert ($estRend -eq '0' -and $egresosConPend -eq $egresosAntes) "Rendicion Pendiente(0) NO afecta el saldo contable ($egresosAntes==$egresosConPend)"

# Item 4 — bajar el sobre TRAS el envio: aprobar debe revalidar el tope y bloquear
& $mysql -u root sysai -e "UPDATE detalle_financiamiento SET monto_asignado=10000 WHERE id=1; UPDATE detalle_financiamiento SET monto_asignado=5000 WHERE id=2;" | Out-Null
$r = Post-Raw $conta.Sess '/poa/aprobar' @{ id=$docId; csrf_token=$conta.Token }
$est = Q "SELECT estado FROM poa WHERE id=$docId;"
Assert ($r.Location -like '/poa/revisar*resultado=20*' -and $est -eq '1') "Sobres bajados tras enviar: aprobar revalida y bloquea (resultado=20, sigue Enviado)"
& $mysql -u root sysai -e "UPDATE detalle_financiamiento SET monto_asignado=100000 WHERE id=1; UPDATE detalle_financiamiento SET monto_asignado=50000 WHERE id=2;" | Out-Null

# Plan de montos (Fase 3) — sin cobertura de TC, aprobar bloquea (resultado=22):
# la rendicion QAIT6 quedo con tc NULL (INSERT directo) y sin filas de tipo_cambio
# no se puede recongelar.
& $mysql -u root sysai -e "DELETE FROM tipo_cambio;" | Out-Null
$r = Post-Raw $conta.Sess '/poa/aprobar' @{ id=$docId; csrf_token=$conta.Token }
$est = Q "SELECT estado FROM poa WHERE id=$docId;"
Assert ($r.Location -like '/poa/revisar*resultado=22*' -and $est -eq '1') "Sin TC que cubra la rendicion: aprobar bloquea (resultado=22, sigue Enviado)"
# Reponer la cobertura del fixture: al aprobar se recongela la rendicion pendiente
& $mysql -u root sysai -e "INSERT INTO tipo_cambio (moneda, fecha_vigencia, compra, venta, origen, usuario_id, fecha) VALUES ('USD','2020-01-01',3.700,3.750,'MANUAL',1,NOW()),('EUR','2020-01-01',4.000,4.050,'MANUAL',1,NOW());" | Out-Null

# Contador aprueba (Enviado->Aprobado)
$r = Post-Raw $conta.Sess '/poa/aprobar' @{ id=$docId; csrf_token=$conta.Token }
$est = Q "SELECT estado FROM poa WHERE id=$docId;"
Assert ($est -eq '3') "Aprobar -> Aprobado(3) en BD (estado=$est)"

# Plan de montos (Fase 3) — al aprobar, la rendicion pendiente quedo recongelada
$tcRend = Q "SELECT CONCAT(tc_usd, '/', tc_eur) FROM rendicion WHERE id=$rendId;"
Assert ($tcRend -eq '3.750000/4.050000') "La rendicion recongelo su TC al aprobar (venta USD/EUR: $tcRend)"

# Item 6: la rendicion del programa quedo Aprobada(1) y el saldo contable la descuenta
$estRend = Q "SELECT estado FROM rendicion WHERE id=$rendId;"
$egresosDespues = Q "SELECT total_egresos FROM vista_total_egresos;"
Assert ($estRend -eq '1') "Al aprobar el POA, la rendicion del programa pasa a Aprobada(1) (estado=$estRend)"
$cmp = Q "SELECT ABS($egresosDespues - $egresosAntes - 123.45) < 0.001;"
Assert ($cmp -eq '1') "El saldo contable descuenta la rendicion al aprobar (egresos $egresosAntes -> $egresosDespues)"
& $mysql -u root sysai -e "DELETE FROM rendicion WHERE id=$rendId;" | Out-Null

# Bloqueo de rubros tambien con doc Aprobado
$r = Post-Raw $coord.Sess '/rubro/crear?actividad_id=1' @{ nombre='QA2'; monto='5'; categoria_rubro_id='1'; tipo_rubro_id='1'; csrf_token=$coord.Token }
Assert ($r.Location -like '*resultado=16*') "Rubros bloqueados tambien con doc Aprobado (resultado=16)"

Write-Host "`n=== 6) PUERTA DE SOBRES (programa 5, SIN sobres — item 4) ===" -ForegroundColor Cyan
$coord5 = New-Login 'coordinador5@sysai.test' $passCoord
Assert ($coord5.Resp.Status -eq 302 -and $coord5.Token) "Coordinador5 (programa 5, sin sobres) login OK + token"

# POA Presupuestal bloqueado: crear -> resultado=19, no crea documento
$r = Post-Raw $coord5.Sess '/poa/crear' @{ csrf_token = $coord5.Token }
$nDocs = Q "SELECT COUNT(*) FROM poa WHERE programa_id=5 AND anio=$anio;"
Assert ($r.Location -like '/poa/admin?resultado=19*' -and $nDocs -eq '0') "Sin sobres: /poa/crear bloqueado (resultado=19, sin documento)"

# Rubros bloqueados: crear en actividad 2 (programa 5) -> resultado=19, no inserta
$nAntes = Q "SELECT COUNT(*) FROM rubro WHERE actividad_id=2;"
$r = Post-Raw $coord5.Sess '/rubro/crear?actividad_id=2' @{ nombre='QA P5'; monto='5'; categoria_rubro_id='1'; tipo_rubro_id='1'; csrf_token=$coord5.Token }
$nDespues = Q "SELECT COUNT(*) FROM rubro WHERE actividad_id=2;"
Assert ($r.Location -like '/poa/admin?resultado=19*' -and $nAntes -eq $nDespues) "Sin sobres: /rubro/crear bloqueado (resultado=19, sin insertar)"

# Rendiciones bloqueadas: rubro temporal en programa 5 (via BD) -> crear rendicion -> resultado=19
& $mysql -u root sysai -e "INSERT INTO rubro (actividad_id, categoria_rubro_id, tipo_rubro_id, codigo, nombre, descripcion, monto, fecha) VALUES (2,1,1,'1.1.1.99','RUBRO QA PUERTA',NULL,1000,NOW());" | Out-Null
$rubroP5 = Q "SELECT id FROM rubro WHERE codigo='1.1.1.99' AND actividad_id=2 LIMIT 1;"
$nAntes = Q "SELECT COUNT(*) FROM rendicion;"
$r = Post-Raw $coord5.Sess "/rendicion/crear?rubro_id=$rubroP5" @{ csrf_token = $coord5.Token }
$nDespues = Q "SELECT COUNT(*) FROM rendicion;"
Assert ($r.Location -like '/poa/admin?resultado=19*' -and $nAntes -eq $nDespues) "Sin sobres: /rendicion/crear bloqueado (resultado=19, sin insertar)"

# El POA Indicadores y la jerarquia NO pasan por la puerta (no manejan dinero)
$r = Get-Raw $coord5.Sess '/poa_indicadores/admin'
Assert ($r.Status -eq 200) "Sin sobres: POA Indicadores PERMITIDO (200)"
$r = Get-Raw $coord5.Sess '/resultado/admin'
Assert ($r.Status -eq 200) "Sin sobres: jerarquia Resultado->Producto->Actividad PERMITIDA (200)"

# El Contador NO pasa por la puerta (adenda): crea rubro en programa 5 sin sobres
$nAntes = Q "SELECT COUNT(*) FROM rubro WHERE actividad_id=2;"
$r = Post-Raw $conta.Sess '/rubro/crear?actividad_id=2' @{ nombre='QA CONTA P5'; monto='7'; categoria_rubro_id='1'; tipo_rubro_id='1'; csrf_token=$conta.Token }
$nDespues = Q "SELECT COUNT(*) FROM rubro WHERE actividad_id=2;"
Assert ($r.Location -like '*resultado=1*' -and ([int]$nDespues) -eq ([int]$nAntes + 1)) "Contador NO pasa por la puerta: crea rubro sin sobres (resultado=1)"

# Limpieza de la seccion: rubros de prueba del programa 5
& $mysql -u root sysai -e "DELETE FROM rubro WHERE actividad_id=2;" | Out-Null

Write-Host "`n=== 7) ACCIONES RECHAZAN GET ===" -ForegroundColor Cyan
$r = Get-Raw $coord.Sess '/poa/enviar'
Assert ($r.Status -eq 302 -and $r.Location -eq '/error') "GET /poa/enviar no registrado como GET (-> /error)"

# Limpieza: eliminar el doc de prueba del anio vigente (deja intacto el de 2025)
& $mysql -u root sysai -e "DELETE FROM poa WHERE id=$docId;" | Out-Null
Write-Host "`n(limpieza) doc de prueba id=$docId eliminado" -ForegroundColor DarkGray

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
