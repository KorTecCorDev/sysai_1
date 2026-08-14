# QA HTTP automatizado — Reportes
#
# Cubre tres cosas distintas:
#   1. Que ninguna ruta de reporte reviente, con TC registrado y sin ninguno
#      (antes: DivisionByZeroError fatal con las tablas de TC vacias).
#   2. El contenido celda a celda del Excel de rendicion POA (item 9, migr. 034).
#   3. El contenido celda a celda de los reportes de INGRESOS y EGRESOS
#      (plan de reportes 2026-08-14, migr. 035 + ReporteMovimientosXlsxBuilder).
#
# Los reportes se DESCARGAN por HTTP, no se leen de disco: desde la Fase 0 del
# plan (2026-08-14) el .xlsx se envia por streaming y ya no se escribe en
# views/reporte/storage/reports/, que era descargable sin sesion.
#
# Requiere el fixture seed_qa.sql (usuarios *.test + tipo_cambio con cobertura 2020).
param(
    [string]$BaseUrl   = 'http://localhost:3000',
    [string]$MysqlExe  = 'C:/xampp/mysql/bin/mysql.exe',
    [string]$PassCoord = 'Test1234*',   # no se usa; uniforma la firma para qa_all.ps1
    [string]$PassConta = 'admin1234'
)
$ErrorActionPreference = 'Stop'
$base = $BaseUrl
$mysql = $MysqlExe
$global:ok = 0; $global:fail = 0
$tmp = Join-Path ([IO.Path]::GetTempPath()) ("qa_reportes_" + [Guid]::NewGuid().ToString('N').Substring(0, 8))
New-Item -ItemType Directory -Path $tmp | Out-Null

function Assert($cond, $msg) {
    if ($cond) { Write-Host "  [OK]   $msg" -ForegroundColor Green; $global:ok++ }
    else       { Write-Host "  [FAIL] $msg" -ForegroundColor Red;   $global:fail++ }
}
function Get-Raw($sess, $url) {
    try {
        $resp = Invoke-WebRequest -Uri "$base$url" -WebSession $sess -MaximumRedirection 0 -UseBasicParsing
        return @{ Status = [int]$resp.StatusCode; Body = $resp.Content; Type = "$($resp.Headers['Content-Type'])" }
    } catch {
        $rp = $_.Exception.Response
        if ($rp) {
            $body = ''; try { $sr = New-Object IO.StreamReader($rp.GetResponseStream()); $body = $sr.ReadToEnd() } catch {}
            return @{ Status = [int]$rp.StatusCode; Body = $body; Type = '' }
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
        if ($rp) { return @{ Status = [int]$rp.StatusCode; Location = $(try { $rp.Headers.Location } catch { $null }) } }
        throw
    }
}
# Descarga un .xlsx servido por streaming y devuelve estado, content-type y ruta.
function Get-Xlsx($sess, $url, $nombre) {
    $destino = Join-Path $tmp $nombre
    try {
        $resp = Invoke-WebRequest -Uri "$base$url" -WebSession $sess -OutFile $destino -PassThru -UseBasicParsing -MaximumRedirection 0
        $magic = ''
        if (Test-Path $destino) {
            $bytes = [IO.File]::ReadAllBytes($destino) | Select-Object -First 2
            $magic = -join ($bytes | ForEach-Object { [char]$_ })
        }
        return @{ Status = [int]$resp.StatusCode; Type = "$($resp.Headers['Content-Type'])"; Path = $destino; Magic = $magic }
    } catch {
        return @{ Status = 0; Type = ''; Path = $destino; Magic = '' }
    }
}
# Mapa CELDA -> VALOR de un .xlsx ya descargado.
function Leer-Celdas($ruta) {
    $dump = & php (Join-Path $PSScriptRoot 'qa_leer_xlsx.php') $ruta
    $celdas = @{}
    foreach ($linea in $dump) { $p = $linea -split "`t", 2; if ($p.Count -eq 2) { $celdas[$p[0]] = $p[1] } }
    return $celdas
}
# Fila (numero) cuya celda de la columna $col vale $valor; $null si no existe.
function Fila-De($celdas, $col, $valor) {
    foreach ($k in $celdas.Keys) { if ($k -match "^$col(\d+)$" -and $celdas[$k] -eq $valor) { return [int]$Matches[1] } }
    return $null
}
function Q($sql) { return (& $mysql -u root sysai -N -e $sql).Trim() }

Write-Host "`n=== 1) AUTENTICACION (contador) ===" -ForegroundColor Cyan
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$pag = Get-Raw $sess '/login'
$tok = if ($pag.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { $matches[1] }
$resp = Post-Raw $sess '/login' @{ email = 'contador@sysai.test'; password = $PassConta; csrf_token = $tok }
Assert ($resp.Status -eq 302) "Contador login OK"

# Paginas HTML (formularios y saldos) y rutas que DESCARGAN un .xlsx.
$rutasPagina = @('/reporte/rendiciones', '/reporte/ingresos', '/saldos_contables/saldos')
$rutasDescarga = @(
    '/reporte/poa',
    '/reporte/poarendicion',
    '/reporte/poarubros',
    '/reporte/rendicionesdesc?fechainicio=2020-01-01&fechafin=2030-12-31',
    '/reporte/ingresosdesc?fechainicio=2020-01-01&fechafin=2030-12-31'
)

Write-Host "`n=== 2) CON TIPO DE CAMBIO (fixture 2020) ===" -ForegroundColor Cyan
foreach ($u in $rutasPagina) {
    $r = Get-Raw $sess $u
    Assert ($r.Status -eq 200 -and $r.Body -notmatch 'Fatal error|DivisionByZeroError|Uncaught') "GET $u -> 200 sin fatal ($($r.Status))"
}
$i = 0
foreach ($u in $rutasDescarga) {
    $r = Get-Xlsx $sess $u ("con_tc_{0}.xlsx" -f $i++)
    Assert ($r.Status -eq 200 -and $r.Type -match 'spreadsheetml' -and $r.Magic -eq 'PK') "GET $u -> xlsx por streaming ($($r.Status), $($r.Magic))"
}
$r = Get-Raw $sess '/saldos_contables/saldos'
Assert ($r.Body -match 'TC compra') "Saldos muestra la conversion al cierre con la tasa visible"

Write-Host "`n=== 3) SIN NINGUN TIPO DE CAMBIO (tabla vacia) ===" -ForegroundColor Cyan
& $mysql -u root sysai -e "DELETE FROM tipo_cambio;" | Out-Null
foreach ($u in $rutasPagina) {
    $r = Get-Raw $sess $u
    Assert ($r.Status -eq 200 -and $r.Body -notmatch 'Fatal error|DivisionByZeroError|Uncaught') "GET $u sin TC -> 200 sin fatal ($($r.Status))"
}
$i = 0
foreach ($u in $rutasDescarga) {
    $r = Get-Xlsx $sess $u ("sin_tc_{0}.xlsx" -f $i++)
    Assert ($r.Status -eq 200 -and $r.Magic -eq 'PK') "GET $u sin TC -> xlsx valido ($($r.Status), $($r.Magic))"
}
$r = Get-Raw $sess '/saldos_contables/saldos'
Assert ($r.Body -match 'sin tipo de cambio registrado') "Saldos sin TC avisa 'sin tipo de cambio registrado' (no inventa cifras)"

& $mysql -u root sysai -e "INSERT INTO tipo_cambio (moneda, fecha_vigencia, compra, venta, origen, usuario_id, fecha) VALUES ('USD','2020-01-01',3.700,3.750,'MANUAL',1,NOW()),('EUR','2020-01-01',4.000,4.050,'MANUAL',1,NOW());" | Out-Null
Write-Host "(limpieza) cobertura de TC del fixture restaurada" -ForegroundColor DarkGray

Write-Host "`n=== 4) EXCEL DE RENDICION POA, CELDA A CELDA (item 9) ===" -ForegroundColor Cyan
$tokPost = $null
foreach ($p in @('/fuente_financiamiento/crear', '/poa/admin')) {
    $pg = Get-Raw $sess $p
    if ($pg.Body -match 'name="csrf_token" value="([0-9a-f]+)"') { $tokPost = $matches[1]; break }
}
Post-Raw $sess '/dfinanciamiento/crear?programa_id=1' @{ 'transferencia[fuente_financiamiento_id]'='1'; 'transferencia[monto]'='4000'; csrf_token=$tokPost } | Out-Null
$hoy = Get-Date -Format 'yyyy-MM-dd'
& $mysql -u root sysai -e "INSERT INTO rendicion (rubro_id,tipo_comprobante_id,ff_id,codigo,serie,numero,detalle,ruc,razon_social,monto,estado,fecha_original,fecha,tc_usd,tc_eur) VALUES (2,1,1,'QARX1','SQAR','XLSX0001','QA XLSX APROBADA','20100100101','PROVEEDOR QA SAC',777.77,1,'$hoy',NOW(),3.750,4.050),(2,1,1,'QARX2','SQAR','XLSX0002','QA XLSX PENDIENTE','20100100101','PROVEEDOR QA SAC',555.55,0,'$hoy',NOW(),3.750,4.050);" | Out-Null

$rx = Get-Xlsx $sess '/reporte/poarendicion' 'poarendicion.xlsx'
Assert ($rx.Status -eq 200 -and $rx.Magic -eq 'PK') "Reporte de rendicion descargado por streaming"
$celdas = Leer-Celdas $rx.Path
Assert ($celdas.Count -gt 0) "qa_leer_xlsx.php vuelca el archivo ($($celdas.Count) celdas)"

$anio = (Get-Date).Year
Assert (($celdas.Values | Where-Object { $_ -like "RENDICI*N - $anio - PROGRAMA*" }).Count -ge 1) "Titulo del bloque: RENDICION - $anio - PROGRAMA ..."
Assert (($celdas.Values | Where-Object { $_ -eq 'TOTAL RENDIDO' }).Count -ge 1) "Encabezado de seccion TOTAL RENDIDO presente"
Assert (($celdas.Values | Where-Object { $_ -eq 'TOTAL' }).Count -ge 1) "Fila de totales ETIQUETADA (TOTAL)"

# La suma aprobada (777.77) va en la columna de SU fuente y la fila de SU rubro.
# Ni columna ni fila se hardcodean: se resuelven por los encabezados, que son el contrato.
$rubroNombre = Q "SELECT nombre FROM rubro WHERE id=2;"
$tipoRubro = Q "SELECT tipo_rubro_id FROM rubro WHERE id=2;"
$colRubro = if ($tipoRubro -eq '1') { 'C' } else { 'E' }
$filaRubro = Fila-De $celdas $colRubro $rubroNombre
$fuenteNombre = Q "SELECT nombre FROM fuente_financiamiento WHERE id=1;"
$colFuente = $null
foreach ($k in $celdas.Keys) { if ($k -match '^([A-Z]+)\d+$' -and $celdas[$k] -eq $fuenteNombre) { $colFuente = $Matches[1]; break } }
Assert ($filaRubro -and $colFuente -and $celdas["$colFuente$filaRubro"] -eq '777.77') "Suma aprobada 777.77 en la FILA de su rubro y la COLUMNA de su fuente"
Assert (($celdas.Values | Where-Object { $_ -eq '555.55' }).Count -eq 0) "Rendicion PENDIENTE (555.55) excluida del reporte"

$filaTransfer = Fila-De $celdas 'A' 'TRANSFERENCIA A PROGRAMA INSTITUCIONAL'
Assert ($filaTransfer -and $celdas["G$filaTransfer"] -eq '4000') "Fila TRANSFERENCIA A PROGRAMA INSTITUCIONAL con monto en G"

& $mysql -u root sysai -e "DELETE FROM rendicion WHERE serie='SQAR';" | Out-Null
Post-Raw $sess '/dfinanciamiento/crear?programa_id=1' @{ 'transferencia[fuente_financiamiento_id]'='1'; 'transferencia[monto]'='0'; csrf_token=$tokPost } | Out-Null

# ===========================================================================
Write-Host "`n=== 5) REPORTES DE INGRESOS Y EGRESOS, CELDA A CELDA (migr. 035) ===" -ForegroundColor Cyan
# Escenario que reproduce EXACTAMENTE los defectos que el plan corrige.
# La fecha de OPERACION cae 3 meses atras y la de REGISTRO es hoy: asi se
# comprueba que el reporte filtra por la primera y no por la segunda.
$op   = (Get-Date).AddMonths(-3)
$fOp  = $op.ToString('yyyy-MM-15')
$rIni = $op.ToString('yyyy-MM-01')
$rFin = $op.ToString('yyyy-MM-28')

& $mysql -u root sysai -e @"
INSERT INTO fuente_financiamiento (codigo,nombre,descripcion,presupuesto,fecha) VALUES ('FFQA9','FUENTE QA SIN SOBRES','QA',50000.00,NOW());
SET @ffsin = LAST_INSERT_ID();
-- Segundo sobre sobre la fuente 1: si el JOIN volviera a estar mal, duplicaria sus movimientos.
INSERT INTO detalle_financiamiento (programa_id,fuente_financiamiento_id,monto_asignado,fecha) VALUES (5,1,1000.00,NOW());
-- Ingreso al remanente de la fuente 1 (programa NULL), con TC congelado.
INSERT INTO oie_comprobante (oie_tipo_comprobante_id,serie,numero,descripcion,ruc,razon_social,monto,fecha_original,fecha,tc_usd,tc_eur) VALUES (1,'SQA','I0001','QA INGRESO FUENTE CON SOBRES','20100100101','DONANTE QA A',1000.00,'$fOp',NOW(),3.750,4.050);
INSERT INTO otros_ingresos_egresos (programa_id,oie_comprobante_id,oie_tipo_id,ff_id,codigo,descripcion,fecha) VALUES (NULL,LAST_INSERT_ID(),1,1,'QAI1','QA INGRESO A',NOW());
-- Ingreso sobre la fuente SIN sobres: es el que desaparecia del reporte.
INSERT INTO oie_comprobante (oie_tipo_comprobante_id,serie,numero,descripcion,ruc,razon_social,monto,fecha_original,fecha,tc_usd,tc_eur) VALUES (1,'SQA','I0002','QA INGRESO FUENTE SIN SOBRES','20100100101','DONANTE QA B',2000.00,'$fOp',NOW(),3.750,4.050);
INSERT INTO otros_ingresos_egresos (programa_id,oie_comprobante_id,oie_tipo_id,ff_id,codigo,descripcion,fecha) VALUES (NULL,LAST_INSERT_ID(),1,@ffsin,'QAI2','QA INGRESO B',NOW());
-- Ingreso SIN cobertura de TC: sus columnas USD/EUR deben decir el guion, no 0.
INSERT INTO oie_comprobante (oie_tipo_comprobante_id,serie,numero,descripcion,ruc,razon_social,monto,fecha_original,fecha,tc_usd,tc_eur) VALUES (1,'SQA','I0003','QA INGRESO SIN TC','20100100101','DONANTE QA C',4000.00,'$fOp',NOW(),NULL,NULL);
INSERT INTO otros_ingresos_egresos (programa_id,oie_comprobante_id,oie_tipo_id,ff_id,codigo,descripcion,fecha) VALUES (NULL,LAST_INSERT_ID(),1,1,'QAI3','QA INGRESO C',NOW());
-- Ingreso FUERA del rango (opera hoy): no debe aparecer.
INSERT INTO oie_comprobante (oie_tipo_comprobante_id,serie,numero,descripcion,ruc,razon_social,monto,fecha_original,fecha,tc_usd,tc_eur) VALUES (1,'SQA','I0004','QA INGRESO FUERA DE RANGO','20100100101','DONANTE QA D',9999.00,CURDATE(),NOW(),3.750,4.050);
INSERT INTO otros_ingresos_egresos (programa_id,oie_comprobante_id,oie_tipo_id,ff_id,codigo,descripcion,fecha) VALUES (NULL,LAST_INSERT_ID(),1,1,'QAI4','QA INGRESO D',NOW());
-- Egreso imputado al programa 1.
INSERT INTO oie_comprobante (oie_tipo_comprobante_id,serie,numero,descripcion,ruc,razon_social,monto,fecha_original,fecha,tc_usd,tc_eur) VALUES (1,'SQA','E0001','QA EGRESO','20100100101','PROVEEDOR QA E',500.00,'$fOp',NOW(),3.750,4.050);
INSERT INTO otros_ingresos_egresos (programa_id,oie_comprobante_id,oie_tipo_id,ff_id,codigo,descripcion,fecha) VALUES (1,LAST_INSERT_ID(),2,1,'QAE1','QA EGRESO A',NOW());
-- Rendicion APROBADA (debe salir) y PENDIENTE (no).
INSERT INTO rendicion (rubro_id,tipo_comprobante_id,ff_id,codigo,serie,numero,detalle,descripcion,ruc,razon_social,monto,estado,fecha_original,fecha,tc_usd,tc_eur) VALUES
 (2,1,1,'QARI1','SQAI','R0001','QA RENDICION APROBADA','QA APROBADA','20100100101','PROVEEDOR QA F',300.00,1,'$fOp',NOW(),3.750,4.050),
 (2,1,1,'QARI2','SQAI','R0002','QA RENDICION PENDIENTE','QA PENDIENTE','20100100101','PROVEEDOR QA G',600.00,0,'$fOp',NOW(),3.750,4.050);
"@ | Out-Null

# ---- INGRESOS ----
$rx = Get-Xlsx $sess "/reporte/ingresosdesc?fechainicio=$rIni&fechafin=$rFin" 'ingresos.xlsx'
Assert ($rx.Status -eq 200 -and $rx.Magic -eq 'PK') "Reporte de ingresos descargado ($rIni a $rFin)"
$ci = Leer-Celdas $rx.Path

Assert ($ci['A1'] -eq 'REPORTE DE INGRESOS') "Titulo del reporte de ingresos"
$fQAI2 = Fila-De $ci 'B' 'QAI2'
Assert ($fQAI2 -ne $null) "[1] El ingreso sobre una fuente SIN SOBRES aparece (antes desaparecia)"
Assert ((($ci.Keys | Where-Object { $ci[$_] -eq 'QAI1' }).Count) -eq 1) "[2] El ingreso de una fuente con DOS sobres aparece UNA sola vez"
$fQAI1 = Fila-De $ci 'B' 'QAI1'
Assert ($fQAI1 -and -not $ci["D$fQAI1"]) "[3] Ingreso hibrido (programa_id NULL): columna PROGRAMA vacia, fila presente"
$filaTotIng = Fila-De $ci 'A' 'TOTAL INGRESOS DEL PERIODO'
Assert ($filaTotIng -and [decimal]$ci["M$filaTotIng"] -eq 7000) "[4] TOTAL de ingresos = 1000+2000+4000 = 7000 (es $($ci["M$filaTotIng"]))"
$filaFuentes = Fila-De $ci 'A' 'PRESUPUESTO DE LAS FUENTES'
$filaTotFte = Fila-De $ci 'A' 'TOTAL PRESUPUESTO DE LAS FUENTES'
Assert ($filaFuentes -and $filaTotFte -and $filaFuentes -gt $filaTotIng) "[5] Las fuentes van en SECCION APARTE, con su propio total (no suman en MONTO)"
Assert ($ci["N$fQAI1"] -eq '266.67') "[7] Conversion USD con el TC congelado (1000/3.75 = 266.67, es '$($ci["N$fQAI1"])')"
$fQAI3 = Fila-De $ci 'B' 'QAI3'
Assert ($fQAI3 -and $ci["N$fQAI3"] -eq [char]0x2014) "[8] Fila sin cobertura de TC muestra el guion largo, no 0 ('$($ci["N$fQAI3"])')"
Assert ((Fila-De $ci 'B' 'QAI4') -eq $null) "[9] El movimiento FUERA del rango de operacion no aparece"
Assert (($ci.Values | Where-Object { $_ -like 'Subtotal *' }).Count -ge 2) "[10] Subtotales por fuente presentes"
Assert ($fQAI1 -and $ci["F$fQAI1"]) "[11] TIPO COMPROBANTE con valor ('$($ci["F$fQAI1"])'; antes siempre vacio)"

# ---- EGRESOS ----
$rx = Get-Xlsx $sess "/reporte/rendicionesdesc?fechainicio=$rIni&fechafin=$rFin" 'egresos.xlsx'
Assert ($rx.Status -eq 200 -and $rx.Magic -eq 'PK') "Reporte de egresos descargado ($rIni a $rFin)"
$ce = Leer-Celdas $rx.Path

Assert ($ce['A1'] -eq 'REPORTE DE EGRESOS') "Titulo del reporte de egresos"
Assert ((Fila-De $ce 'B' 'QARI1') -ne $null) "[6a] La rendicion APROBADA aparece"
Assert ((Fila-De $ce 'B' 'QARI2') -eq $null) "[6b] La rendicion PENDIENTE NO aparece"
$fRend = Fila-De $ce 'B' 'QARI1'
Assert ($ce["L$fRend"] -eq 'Aprobada') "[6c] Columna ESTADO deja constancia de que se muestra"
Assert ((Fila-De $ce 'A' 'RENDICIONES APROBADAS') -ne $null -and (Fila-De $ce 'A' 'OTROS EGRESOS (OIE)') -ne $null) "Dos secciones etiquetadas (rendiciones / otros egresos)"
$filaTotGen = Fila-De $ce 'A' 'TOTAL GENERAL'
Assert ($filaTotGen -and [decimal]$ce["M$filaTotGen"] -eq 800) "TOTAL GENERAL = 300 (rendicion) + 500 (egreso) = 800 (es $($ce["M$filaTotGen"]))"

# ---- Rango invalido ----
$r = Get-Raw $sess "/reporte/ingresosdesc?fechainicio=$rFin&fechafin=$rIni"
Assert ($r.Status -eq 302) "Rango invertido -> redirige al formulario en vez de generar una hoja vacia"

Write-Host "`n=== 6) LA DESCARGA YA NO TOCA EL DISCO (Fase 0) ===" -ForegroundColor Cyan
$r = Get-Raw $sess '/descargar?rprt=../../../../.env'
Assert ($r.Status -ne 200 -or $r.Body -notmatch 'DB_USER|MAIL_PASSWORD') "[12a] /descargar retirada: no sirve el .env ($($r.Status))"
$r = Get-Raw $sess '/views/reporte/storage/reports/reporte_poa_contador.xlsx'
Assert ($r.Status -ne 200) "[12b] La carpeta storage/reports ya no se sirve ($($r.Status))"

# Limpieza del escenario del bloque 5.
& $mysql -u root sysai -e "DELETE FROM rendicion WHERE serie='SQAI'; DELETE o, c FROM otros_ingresos_egresos o JOIN oie_comprobante c ON c.id=o.oie_comprobante_id WHERE c.serie='SQA'; DELETE FROM detalle_financiamiento WHERE programa_id=5 AND fuente_financiamiento_id=1; DELETE FROM fuente_financiamiento WHERE codigo='FFQA9';" | Out-Null
Remove-Item -Recurse -Force $tmp -ErrorAction SilentlyContinue
Write-Host "(limpieza) escenario de ingresos/egresos y descargas temporales eliminados" -ForegroundColor DarkGray

Write-Host "`n=== RESULTADO: $ok OK / $fail FAIL ===" -ForegroundColor Cyan
if ($fail -gt 0) { exit 1 } else { exit 0 }
