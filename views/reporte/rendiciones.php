
<?php

use Model\ReportePoaRubros;
use Model\ReporteRendicionesVista;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

//Creando el nuevo array de resultados
// Definir las claves a eliminar de cada array
$claves_a_excluir_egresos = [
    'otros_ingresos_egresos_id',
    'otros_ingresos_egresos_oie_tipo_id',
    'oie_tipo_comprobante_id',
    'fuente_financiamiento_id'
];

$claves_a_excluir_rendiciones = [
    'rendicion_id',
    'rendicion_tipo_comprobante_id',
    'fuente_financiamiento_id'
];

// Inicializar el nuevo array combinado
$nuevo_array = [];

// Agregar los objetos del primer array ($resreporteegresos) excluyendo las claves específicas
foreach ($resreporteegresos as $obj) {
    $nuevo_obj = (array) $obj; // Convertir el objeto a array

    // Eliminar las claves específicas de los egresos
    foreach ($claves_a_excluir_egresos as $clave) {
        unset($nuevo_obj[$clave]);
    }

    // Convertir de nuevo a objeto y agregar al nuevo array
    $nuevo_array[] = (object) $nuevo_obj;
}

// Agregar los objetos del segundo array ($resreporterendiciones) excluyendo las claves específicas
foreach ($resreporterendiciones as $obj) {
    $nuevo_obj = (array) $obj; // Convertir el objeto a array

    // Eliminar las claves específicas de las rendiciones
    foreach ($claves_a_excluir_rendiciones as $clave) {
        unset($nuevo_obj[$clave]);
    }

    // Convertir de nuevo a objeto y agregar al nuevo array
    $nuevo_array[] = (object) $nuevo_obj;
}

// Ahora $nuevo_array contiene la combinación de ambos arrays con las modificaciones requeridas





// Definir el array de encabezados
$encabezados = [
    'FECHA',
    'CODIGO',
    'DESCRIPCION',
    'TIPO/COMPROBANTE',
    'MONTO',
    'FUENTE_FINANCIAMIENTO',
    'FECHA_COMPROBANTE',
    'RUC',
    'PERSONA',
    'SERIE',
    'NUMERO',
    'DETALLE',
    'MONTO/COMPROBANTE'
];

// Establecer la fila inicial para el título y los encabezados
$cntrows = 1;
$startColumn = 'A';
$endColumn = chr(ord($startColumn) + count($encabezados) - 1); // Calcula la última columna

// 1Insertar el título del reporte
$sheet->mergeCells("$startColumn$cntrows:$endColumn$cntrows"); // Combinar celdas
$sheet->setCellValue("$startColumn$cntrows", "REPORTE EGRESOS");
$sheet->getStyle("$startColumn$cntrows")->getFont()->setBold(true)->setSize(14);
$sheet->getStyle("$startColumn$cntrows")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("$startColumn$cntrows")->getFill()->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('FFA500'); // Color naranja

$cntrows++; // Pasar a la siguiente fila para los encabezados

// Insertar los encabezados de las columnas
$colIndex = $startColumn;
foreach ($encabezados as $encabezado) {
    $sheet->setCellValue($colIndex . $cntrows, $encabezado);
    $sheet->getStyle($colIndex . $cntrows)->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle($colIndex . $cntrows)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($colIndex . $cntrows)->getAlignment()->setWrapText(true);
    $sheet->getStyle($colIndex . $cntrows)->getFill()->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('FFD700'); // Color dorado para los encabezados
    $colIndex++;
}

// Ajustar automáticamente el ancho de las columnas
foreach (range($startColumn, $endColumn) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}


$reporterendi = ReporteRendicionesVista::insertarDatosDesdeArray($sheet, 3, $nuevo_array);
//$newcntrow = ReportePoaRubros::insertarCeldasReportePOA($sheet, $ultcont, $respoas, $tcdolar, $tceuro);

/*SECCION DE ALMACENAMIENTO EN EL SERVIDOR*/
$directory = __DIR__ . "/storage/reports/";
if (!is_dir($directory)) {
    mkdir($directory, 0777, true); // Crea la carpeta con permisos de escritura
}

$file = $directory . "reporte_poa_rendiciones_general.xlsx";
$writer = new Xlsx($spreadsheet);
$writer->save($file);

// Mostrar mensaje de reporte exitoso
echo "<h1>Reporte creado exitosamente</h1>";
echo "<a href='../descargar?rprt={$file}' target='_blank' class='btn btn-success' id='descargarReporte'>
        <i class='bi bi-file-earmark-excel'></i> Ver POA Global
      </a>";
/*SECCION DE ALMACENAMIENTO EN EL SERVIDOR*/
