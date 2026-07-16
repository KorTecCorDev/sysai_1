<?php
/**
 * Backfill de tipos de cambio desde la SBS (plan de montos, Fase 6 — INFORMATIVO).
 *
 * Importa en lote las tasas de un rango de fechas con origen='SBS', para cubrir las
 * rendiciones históricas que se carguen al arrancar (teclear ~130 días hábiles a
 * mano no lo hace nadie). Sigue siendo informativa en lo que importa: la dispara
 * el Contador (o quien opere el setup), queda visible en /tcambio/admin y cualquier
 * fila puede sobreescribirse a mano.
 *
 * Uso (desde la raíz del proyecto, con SBS_API_URL configurada en .env):
 *   php database/importar_tc_sbs.php USD 2026-01-01 2026-07-15
 *   php database/importar_tc_sbs.php EUR 2026-01-01 2026-07-15
 *
 * - INSERT IGNORE: las fechas ya registradas (p. ej. tecleadas a mano) NO se pisan.
 * - Las fechas sin respuesta (feriados, fines de semana, API caída) se saltan y se
 *   informan: la resolución "vigente a una fecha" tolera los huecos por diseño.
 * - Nunca corre en la ruta de un reporte ni del despliegue: es una herramienta.
 */

if (PHP_SAPI !== 'cli') {
    exit("Este script solo corre por CLI.\n");
}

$moneda = strtoupper($argv[1] ?? '');
$desde  = $argv[2] ?? '';
$hasta  = $argv[3] ?? '';

if (!in_array($moneda, ['USD', 'EUR'], true)
    || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)
    || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)
    || strtotime($desde) === false || strtotime($hasta) === false
    || strtotime($desde) > strtotime($hasta)) {
    exit("Uso: php database/importar_tc_sbs.php <USD|EUR> <desde AAAA-MM-DD> <hasta AAAA-MM-DD>\n");
}

$_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);
require __DIR__ . '/../includes/funciones.php';
require __DIR__ . '/../includes/config/database.php';
require __DIR__ . '/../vendor/autoload.php';

$db = conectarDB();
\Model\ActiveRecord::setDB($db);

$plantilla = $_ENV['SBS_API_URL'] ?? getenv('SBS_API_URL') ?: '';
if (trim((string) $plantilla) === '') {
    exit("SBS_API_URL no está configurada en el .env — nada que importar.\n");
}

$insertadas = 0;
$existentes = 0;
$sinDato    = [];

$stmt = $db->prepare(
    "INSERT IGNORE INTO tipo_cambio (moneda, fecha_vigencia, compra, venta, origen, usuario_id, fecha)
     VALUES (?, ?, ?, ?, 'SBS', 1, NOW())"
);
if ($stmt === false) {
    exit("No se pudo preparar el INSERT: {$db->error}\n");
}

for ($ts = strtotime($desde); $ts <= strtotime($hasta); $ts = strtotime('+1 day', $ts)) {
    $fecha = date('Y-m-d', $ts);
    // Fines de semana: la SBS no publica; se salta sin consultar (menos HTTP).
    if ((int) date('N', $ts) >= 6) {
        continue;
    }
    $tasa = \Model\TipoCambio::consultarSbs($moneda, $fecha);
    if ($tasa === null) {
        $sinDato[] = $fecha;
        continue;
    }
    $stmt->bind_param('ssdd', $moneda, $fecha, $tasa['compra'], $tasa['venta']);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $insertadas++;
        echo "OK    {$fecha}  compra {$tasa['compra']}  venta {$tasa['venta']}\n";
    } else {
        $existentes++;   // ya había una fila (moneda, fecha): no se pisa
    }
}
$stmt->close();

echo "\nResumen {$moneda} {$desde} → {$hasta}:\n";
echo "  insertadas : {$insertadas}\n";
echo "  ya existían: {$existentes} (no se pisan)\n";
echo "  sin dato   : " . count($sinDato) . ($sinDato ? ' (' . implode(', ', array_slice($sinDato, 0, 10)) . (count($sinDato) > 10 ? ', …' : '') . ')' : '') . "\n";
echo "Los huecos no bloquean nada: el TC vigente a una fecha se resuelve por la última fecha de vigencia anterior.\n";
