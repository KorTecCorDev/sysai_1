<?php
// Helper de QA: volcado compacto de un .xlsx para asserts desde PowerShell.
// Uso:  php database/qa_leer_xlsx.php <ruta.xlsx>
// Salida: una línea por celda no vacía, formato "CELDA<TAB>VALOR" (fórmulas
// tal cual, sin calcular — los asserts comparan literales). Exit 1 si el
// archivo no existe o no se puede leer.

// Solo por linea de comandos: recibe una RUTA por $argv y la vuelca. Servido por
// HTTP (con register_argc_argv, $argv sale de la query string) permitia leer
// hojas de calculo arbitrarias del servidor.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$archivo = $argv[1] ?? '';
if ($archivo === '' || !is_file($archivo)) {
    fwrite(STDERR, "qa_leer_xlsx: archivo no encontrado: {$archivo}\n");
    exit(1);
}

try {
    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(false);
    $sheet = $reader->load($archivo)->getActiveSheet();
} catch (\Throwable $e) {
    fwrite(STDERR, "qa_leer_xlsx: no se pudo leer: {$e->getMessage()}\n");
    exit(1);
}

$maxRow = $sheet->getHighestRow();
$maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
for ($r = 1; $r <= $maxRow; $r++) {
    for ($c = 1; $c <= $maxCol; $c++) {
        $col = Coordinate::stringFromColumnIndex($c);
        $v = $sheet->getCell($col . $r)->getValue();
        if ($v !== null && $v !== '') {
            // Normaliza saltos de línea para mantener una celda por línea.
            echo $col . $r . "\t" . str_replace(["\r", "\n"], ' ', (string) $v) . "\n";
        }
    }
}
exit(0);
