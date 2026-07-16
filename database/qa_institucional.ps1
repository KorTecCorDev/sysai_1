# QA HTTP automatizado — Programa Institucional y transferencias (item 9, migr. 033-034)
# Cubre: sincronizacion del sobre derivado (Institucional, fuente) al crear/editar/
# eliminar transferencias; guardas de capacidad (aumento) y de reduccion bajo lo
# comprometido (resultado=27); bloqueos del Institucional como destino de sobres
# (resultado=26) y contra eliminacion (resultado=28); y la operacion del POA
# Institucional por el CONTADOR (crear/enviar/aprobar + rendicion que nace aprobada),
# con el bloqueo de crear/enviar POA de programas normales (resultado=26).
#
# PRECONDICION del fixture (seed_qa.sql): programa 1 con sobres en fuentes 1 y 2;
# programa Institucional id=9 (es_institucional=1); contador@sysai.test cargo 2.
param(
    [string]$BaseUrl   = 'http://localhost:3000',
    [string]$MysqlExe  = 'C:/xampp/mysql/bin/mysql.exe',
    [string]$PassCoord = 'Test1234*',
    [string]$PassConta = 'admin1234'
)
$ErrorActionPreference = 'Stop'
$base = $BaseUrl
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
    if ($r.Status -eq 302) { foreach ($p in @('/fuente_financiamiento/crear','/poa/admin')) { $token = Get-Csrf $s $p; if ($token) { break } } }
    return @{ Sess=$s; Resp=$r; Token=$token }
}

$INST = Q "SELECT id FROM programa WHERE es_institucional=1 LIMIT 1;"
$anio = (Get-Date).Year

Write-Host "`n=== 1) AUTENTICACION Y FIXTURE ===" -ForegroundColor Cyan
$conta = New-Login 'contador@sysai.test' $passConta
Assert ($conta.Resp.Status -eq 302 -and $conta.Token) "Contador login OK + token"
Assert ($INST -match '^\d+$') "Programa Institucional presente en el fixture (id=$INST)"

Write-Host "`n=== 2) TRANSFERENCIAS: SINCRONIZACION DEL SOBRE DERIVADO ===" -ForegroundColor Cyan
$r = Post-Raw $conta.Sess '/dfinanciamiento/crear?programa_id=1' @{ 'transferencia[fuente_financiamiento_id]'='1'; 'transferencia[monto]'='5000'; csrf_token=$conta.Token }
$sobre = Q "SELECT monto_asignado FROM detalle_financiamiento WHERE programa_id=$INST AND fuente_financiamiento_id=1;"
Assert ($sobre -eq '5000.00') "Crear transferencia 5000 -> sobre (Institucional, ff1) sincronizado ($sobre)"

$r = Post-Raw $conta.Sess '/dfinanciamiento/crear?programa_id=1' @{ 'transferencia[fuente_financiamiento_id]'='1'; 'transferencia[monto]'='8000'; csrf_token=$conta.Token }
$sobre = Q "SELECT monto_asignado FROM detalle_financiamiento WHERE programa_id=$INST AND fuente_financiamiento_id=1;"
Assert ($sobre -eq '8000.00') "Editar a 8000 -> sobre re-sincronizado ($sobre)"

$r = Post-Raw $conta.Sess '/dfinanciamiento/crear?programa_id=1' @{ 'transferencia[fuente_financiamiento_id]'='1'; 'transferencia[monto]'='999999999'; csrf_token=$conta.Token }
$monto = Q "SELECT monto FROM transferencia_institucional WHERE fuente_financiamiento_id=1 AND programa_origen_id=1;"
Assert ($r.Body -match 'excede' -and $monto -eq '8000.00') "Aumento sobre la capacidad asignable: error visible, sin cambio ($monto)"

Write-Host "`n=== 3) BLOQUEOS DEL INSTITUCIONAL ===" -ForegroundColor Cyan
$r = Get-Raw $conta.Sess "/dfinanciamiento/crear?programa_id=$INST"
Assert ($r.Status -eq 302 -and $r.Location -like '*resultado=26*') "Institucional bloqueado como destino de sobres (resultado=26)"

$r = Post-Raw $conta.Sess '/programa/eliminar' @{ id=$INST; tipo='programa'; csrf_token=$conta.Token }
$sigue = Q "SELECT COUNT(*) FROM programa WHERE id=$INST;"
Assert ($r.Location -like '*resultado=28*' -and $sigue -eq '1') "Programa Institucional no eliminable (resultado=28)"

Write-Host "`n=== 4) EL CONTADOR SOLO ELABORA EL POA DEL INSTITUCIONAL ===" -ForegroundColor Cyan
$nAntes = Q "SELECT COUNT(*) FROM poa WHERE programa_id=1 AND anio=$anio;"
$r = Post-Raw $conta.Sess '/poa/crear' @{ programa_id='1'; csrf_token=$conta.Token }
$nDespues = Q "SELECT COUNT(*) FROM poa WHERE programa_id=1 AND anio=$anio;"
Assert ($r.Location -like '*resultado=26*' -and $nAntes -eq $nDespues) "POA de programa normal bloqueado para el contador (resultado=26)"

# Jerarquia minima del Institucional (via BD; los CRUD del contador ya estan cubiertos por otros arneses)
& $mysql -u root sysai -e "INSERT INTO resultado (programa_id,codigo,nombre,descripcion,fecha) VALUES ($INST,'1','GESTION ADMINISTRATIVA QA','QA INSTITUCIONAL',NOW());" | Out-Null
$resId = Q "SELECT id FROM resultado WHERE programa_id=$INST AND codigo='1';"
& $mysql -u root sysai -e "INSERT INTO producto (resultado_id,codigo,nombre,descripcion,fecha) VALUES ($resId,'1.1','OFICINA QA','QA INSTITUCIONAL',NOW());" | Out-Null
$prodId = Q "SELECT id FROM producto WHERE resultado_id=$resId;"
& $mysql -u root sysai -e "INSERT INTO actividad (producto_id,codigo,nombre,descripcion,fecha) VALUES ($prodId,'1.1.1','SERVICIOS DE OFICINA QA','QA INSTITUCIONAL',NOW());" | Out-Null
$actId = Q "SELECT id FROM actividad WHERE producto_id=$prodId;"
& $mysql -u root sysai -e "INSERT INTO rubro (actividad_id,categoria_rubro_id,tipo_rubro_id,codigo,nombre,descripcion,monto,fecha) VALUES ($actId,1,2,'1.1.1.90','SERVICIOS BASICOS QA','QA INSTITUCIONAL',3000,NOW());" | Out-Null
$rubroId = Q "SELECT id FROM rubro WHERE actividad_id=$actId;"

$r = Post-Raw $conta.Sess '/poa/crear' @{ programa_id=$INST; csrf_token=$conta.Token }
$doc = Q "SELECT id FROM poa WHERE programa_id=$INST AND anio=$anio;"
$row = Q "SELECT estado, presupuesto FROM poa WHERE id=$doc;"
Assert ($r.Location -like '*resultado=1*' -and $row -match '^0\s+3000') "Contador inicia el POA Institucional (Borrador, presupuesto de rubros: '$row')"

$r = Post-Raw $conta.Sess '/poa/enviar' @{ id=$doc; csrf_token=$conta.Token }
$est = Q "SELECT estado FROM poa WHERE id=$doc;"
Assert ($est -eq '1') "Contador envia el POA Institucional (estado=$est)"

$r = Post-Raw $conta.Sess '/poa/aprobar' @{ id=$doc; csrf_token=$conta.Token }
$est = Q "SELECT estado FROM poa WHERE id=$doc;"
Assert ($est -eq '3') "Contador aprueba el POA Institucional (estado=$est)"

Write-Host "`n=== 5) RENDICION DEL CONTADOR CONTRA EL INSTITUCIONAL ===" -ForegroundColor Cyan
$form = @{ tipo_comprobante_id='1'; ff_id='1'; serie='SQAI'; numero='INST0001';
           detalle='SERVICIO ELECTRICO QA'; descripcion='PAGO LUZ OFICINA QA'; ruc='20100000001'; razon_social='HIDRANDINA SA';
           monto='1500'; fecha_original=(Get-Date -Format 'yyyy-MM-dd'); csrf_token=$conta.Token }
$r = Post-Raw $conta.Sess "/rendicion/crear?rubro_id=$rubroId" $form
$row = Q "SELECT estado, monto FROM rendicion WHERE serie='SQAI' AND numero='INST0001';"
Assert ($row -match '^1\s+1500') "Rendicion del contador nace Aprobada (POA aprobado) ('$row')"
$saldo = Q "SELECT saldo FROM vista_saldo_sobre WHERE programa_id=$INST AND fuente_financiamiento_id=1;"
Assert ($saldo -eq '6500.00') "Saldo del sobre Institucional = 8000 - 1500 ($saldo)"

Write-Host "`n=== 6) GUARDA DE REDUCCION BAJO LO COMPROMETIDO ===" -ForegroundColor Cyan
$r = Post-Raw $conta.Sess '/dfinanciamiento/crear?programa_id=1' @{ 'transferencia[fuente_financiamiento_id]'='1'; 'transferencia[monto]'='1000'; csrf_token=$conta.Token }
$monto = Q "SELECT monto FROM transferencia_institucional WHERE fuente_financiamiento_id=1 AND programa_origen_id=1;"
Assert ($r.Location -like '*resultado=27*' -and $monto -eq '8000.00') "Reducir a 1000 (< comprometido 1500) bloqueado (resultado=27, monto sigue $monto)"

Write-Host "`n=== 7) MONTO 0 ELIMINA TRANSFERENCIA Y SOBRE DERIVADO ===" -ForegroundColor Cyan
& $mysql -u root sysai -e "DELETE FROM rendicion WHERE serie='SQAI' AND numero='INST0001';" | Out-Null
$r = Post-Raw $conta.Sess '/dfinanciamiento/crear?programa_id=1' @{ 'transferencia[fuente_financiamiento_id]'='1'; 'transferencia[monto]'='0'; csrf_token=$conta.Token }
$nReg = Q "SELECT COUNT(*) FROM transferencia_institucional WHERE fuente_financiamiento_id=1;"
$nSobre = Q "SELECT COUNT(*) FROM detalle_financiamiento WHERE programa_id=$INST AND fuente_financiamiento_id=1;"
Assert ($nReg -eq '0' -and $nSobre -eq '0') "Transferencia a 0: registro y sobre derivado eliminados"

# Limpieza del escenario institucional
& $mysql -u root sysai -e "DELETE FROM poa WHERE id=$doc; DELETE FROM rubro WHERE id=$rubroId; DELETE FROM actividad WHERE id=$actId; DELETE FROM producto WHERE id=$prodId; DELETE FROM resultado WHERE id=$resId;" | Out-Null
Write-Host "`n(limpieza) POA, jerarquia y datos institucionales de prueba eliminados" -ForegroundColor DarkGray

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
