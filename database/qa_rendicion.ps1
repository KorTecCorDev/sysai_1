# QA HTTP automatizado — Rendiciones imputadas al rubro (item 5)
# Login, CSRF 419, autorizacion por rol/cross-tenant, creacion imputada a rubro,
# limite por rubro (Σ rendiciones ≤ monto del rubro). Verificacion en BD.
$ErrorActionPreference = 'Stop'
$base = 'http://localhost:3000'
$passCoord = 'Test1234*'
$passConta = 'admin1234'
$mysql = 'C:/xampp/mysql/bin/mysql.exe'
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

$RUBRO = 2          # programa 1, monto 3500
$RUBRO_MONTO = 3500

Write-Host "`n=== 1) AUTENTICACION ===" -ForegroundColor Cyan
$coord = New-Login 'coordinador@sysai.test' $passCoord
Assert ($coord.Resp.Status -eq 302) "Coordinador login OK"
$conta = New-Login 'contador@sysai.test' $passConta
Assert ($conta.Resp.Status -eq 302) "Contador login OK"

Write-Host "`n=== 2) CSRF (419) ===" -ForegroundColor Cyan
$r = Post-Raw $coord.Sess "/rendicion/crear?rubro_id=$RUBRO" @{ }
Assert ($r.Status -eq 419) "POST /rendicion/crear sin token -> 419 ($($r.Status))"

Write-Host "`n=== 3) CROSS-TENANT (coordinador prog.1 vs rubro de prog.5) ===" -ForegroundColor Cyan
$act5 = Q "SELECT a.id FROM actividad a JOIN producto p ON p.id=a.producto_id JOIN resultado re ON re.id=p.resultado_id WHERE re.programa_id=5 LIMIT 1;"
& $mysql -u root sysai -e "INSERT INTO rubro (actividad_id,categoria_rubro_id,tipo_rubro_id,codigo,nombre,monto,fecha) VALUES ($act5,1,1,'QAXR1','RUBRO QA P5',100,NOW());" | Out-Null
$rubro5 = Q "SELECT id FROM rubro WHERE codigo='QAXR1';"
$r = Get-Raw $coord.Sess "/rendicion/admin?rubro_id=$rubro5"
Assert ($r.Status -eq 403) "Coordinador no accede a rendiciones de rubro de otro programa (403)"
& $mysql -u root sysai -e "DELETE FROM rubro WHERE id=$rubro5;" | Out-Null

Write-Host "`n=== 4) CREAR RENDICION IMPUTADA AL RUBRO ===" -ForegroundColor Cyan
$nAntes = Q "SELECT COUNT(*) FROM rendicion WHERE rubro_id=$RUBRO;"
$form = @{ tipo_comprobante_id='1'; ff_id='1'; codigo='QAR001'; serie='S001'; numero='0001';
           detalle='COMPRA QA'; descripcion='COMENTARIO QA'; ruc='20100100101'; razon_social='PROVEEDOR QA SAC';
           monto='3000'; fecha_original='2026-06-01'; csrf_token=$coord.Token }
$r = Post-Raw $coord.Sess "/rendicion/crear?rubro_id=$RUBRO" $form
$nDespues = Q "SELECT COUNT(*) FROM rendicion WHERE rubro_id=$RUBRO;"
Assert ($r.Location -like "*rubro_id=$RUBRO*resultado=1*" -and [int]$nDespues -eq [int]$nAntes+1) "Rendicion creada e imputada al rubro $RUBRO (filas $nAntes->$nDespues)"
$row = Q "SELECT rubro_id, estado, IFNULL(poa_rendicion_id,'NULL'), monto FROM rendicion WHERE codigo='QAR001';"
Assert ($row -match "^$RUBRO\s+0\s+NULL\s+3000") "Fila correcta: rubro_id=$RUBRO, estado=0, poa_rendicion_id=NULL, monto=3000 ('$row')"

Write-Host "`n=== 5) LIMITE POR RUBRO (Σ ≤ monto rubro=$RUBRO_MONTO) ===" -ForegroundColor Cyan
# Ya hay 3000 rendido; intentar 1000 mas (total 4000 > 3500) -> debe fallar sin insertar
$form2 = @{ tipo_comprobante_id='1'; ff_id='1'; codigo='QAR002'; serie='S001'; numero='0002';
            detalle='COMPRA QA2'; descripcion='COMENTARIO QA2'; ruc='20100100101'; razon_social='PROVEEDOR QA SAC';
            monto='1000'; fecha_original='2026-06-02'; csrf_token=$coord.Token }
$r = Post-Raw $coord.Sess "/rendicion/crear?rubro_id=$RUBRO" $form2
$existe = Q "SELECT COUNT(*) FROM rendicion WHERE codigo='QAR002';"
$superaLimite = ($r.Body -match 'excede el saldo del rubro') -or ($r.Status -eq 200)
Assert ($existe -eq '0') "Rendicion que excede el rubro NO se inserta (4000 > 3500)"
Assert ($r.Body -match 'excede el saldo del rubro') "Muestra error de limite excedido"

# Una que completa exacto el saldo (500 -> total 3500 = monto) debe pasar
$form3 = @{ tipo_comprobante_id='1'; ff_id='2'; codigo='QAR003'; serie='S001'; numero='0003';
            detalle='COMPRA QA3'; descripcion='COMENTARIO QA3'; ruc='20100100101'; razon_social='PROVEEDOR QA SAC';
            monto='500'; fecha_original='2026-06-03'; csrf_token=$coord.Token }
$r = Post-Raw $coord.Sess "/rendicion/crear?rubro_id=$RUBRO" $form3
$total = Q "SELECT COALESCE(SUM(monto),0) FROM rendicion WHERE rubro_id=$RUBRO;"
Assert ($r.Location -like '*resultado=1*' -and $total -match '^3500') "Rendicion al limite exacto pasa (total rendido=3500=monto)"

Write-Host "`n=== 6) AUTORIZACION GET de eliminar ===" -ForegroundColor Cyan
$r = Get-Raw $coord.Sess '/rendicion/eliminar'
Assert ($r.Status -eq 302) "GET /rendicion/eliminar no ejecuta (redirige)"

# Limpieza
& $mysql -u root sysai -e "DELETE FROM rendicion WHERE codigo IN ('QAR001','QAR002','QAR003');" | Out-Null
Write-Host "`n(limpieza) rendiciones de prueba eliminadas" -ForegroundColor DarkGray

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
