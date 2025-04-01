
<?php

use Model\ReportePoaRubros;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$columnas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
$columnasrubros = ['C', 'D', 'E', 'F'];

//Si es coordinador, tomamos el programa_id
if ($_SESSION['cargo_id'] == 3) {
  $prgmaid = $_SESSION['programa_id'] ?? null;
}

$colores = [
  'FFC000', // Amarillo dorado
  'FF5733', // Naranja
  '33FF57', // Verde lima
  '3357FF', // Azul claro
  'FF33A1', // Rosa
  'C70039', // Rojo oscuro
  '900C3F', // Borgoña
  '581845', // Púrpura oscuro
  '00FFFF', // Cian
  'FFFF00'  // Amarillo brillante
];
$colorIndex = 0;

// Bucle que recorre todos los programas_id registrados
// Agrupar los registros de $resbienes por id_programa
//Contador que indica la fila a insertar el nuevo POA, parámetro requerido por la función insertarCeldasReportePOA

$resbienesAgrupados = [];
if ($_SESSION['cargo_id'] == 3) {
  foreach ($resbienes as $bien) {
    if ($bien->id_programa == $prgmaid) {
      $resbienesAgrupados[$prgmaid][] = $bien;
    }
  }
} else {
  foreach ($resbienes as $bien) {
    $resbienesAgrupados[$bien->id_programa][] = $bien;
  }
}
foreach ($resbienesAgrupados as $respoas) {
  $ultcont = $newcntrow ?? 5; // Si $newcntrow no está definido, asignar 5 a $ultcont , está definido por default a un valor 5 para queinicie en la fila correcta el reporte.
  if ($ultcont != 5) {
    $ultcont += 4; //Sumamos 4 para que el contenido ingrese luego del encabezado
  }

  // Este contador nos permite saber en qué fila ingresamos los registros de cada POA de programa (esto incluye los encabezados)
  $cntrows = 1;
  if (isset($newcntrow)) {
    $cntrows = $newcntrow;
  }

  // Ajustando las columnas con texto ajustable y asignándole un ancho fijo
  foreach ($columnas as $columna) {
    $sheet->getColumnDimension($columna)->setAutoSize(false);
    $sheet->getColumnDimension($columna)->setWidth(15);
    $sheet->getStyle("{$columna}{$cntrows}")->getAlignment()->setWrapText(true);
  }

  // Personalizando altura de filas
  $sheet->getRowDimension(3)->setRowHeight(15);
  $sheet->getRowDimension(4)->setRowHeight(52);

  // Insertar una imagen en la celda A{$cntrows}
  $drawing = new Drawing();
  $drawing->setPath($_SERVER['DOCUMENT_ROOT'] . "/build/img/logo_last.png"); // Ruta de la imagen
  $drawing->setCoordinates("A{$cntrows}"); // Posición inicial (fila dinámica)

  // Ajustar la altura de la fila donde se inserta la imagen
  $sheet->getRowDimension($cntrows)->setRowHeight(35); // Altura fija de 35 píxeles

  // Configurar el tamaño de la imagen
  $drawing->setWidth(40); // Ancho de la imagen
  $drawing->setHeight(40); // Altura de la imagen
  $drawing->setWorksheet($sheet);

  // Agregar el texto "PROCESO DE PROYECTOS" en la celda B1
  $sheet->mergeCells("B{$cntrows}:C{$cntrows}"); // Combina desde B1 hasta C1 (ajusta según columnas)
  $sheet->setCellValue("B{$cntrows}", 'PROCESO DE PROYECTOS');

  // Combinar celdas para la cabecera
  $sheet->mergeCells("A" . ($cntrows + 1) . ":I" . ($cntrows + 1)); // Combina desde A2 hasta I2
  $sheet->setCellValue("A" . ($cntrows + 1), 'PRESUPUESTO - ' . date('Y') . ' - PROGRAMA ' . strtoupper($respoas[0]->programa));
  // Estilo negrita y tamaño
  $sheet->getStyle("A" . ($cntrows + 1))->getFont()->setBold(true)->setSize(14);
  $sheet->getStyle("A" . ($cntrows + 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

  // Aplicar color de fondo a las celdas combinadas
  $sheet->getStyle("A" . ($cntrows + 1) . ":I" . ($cntrows + 1))->getFill()->setFillType(Fill::FILL_SOLID);
  $sheet->getStyle("A" . ($cntrows + 1) . ":I" . ($cntrows + 1))->getFill()->getStartColor()->setRGB($colores[$colorIndex]); // Color naranja
  $sheet->getStyle("A" . ($cntrows + 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

  // Insertando encabezados personalizados
  $sheet->mergeCells("C" . ($cntrows + 2) . ":D" . ($cntrows + 2)); // Combinar celdas desde C3 hasta D3 (Preparando encabezado para BIENES)
  $sheet->setCellValue("C" . ($cntrows + 2), "1. BIENES");
  $sheet->getStyle("C" . ($cntrows + 2))->getFont()->setBold(true)->setSize(12);
  $sheet->getStyle("C" . ($cntrows + 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle("C" . ($cntrows + 2))->getAlignment()->setWrapText(true);

  $sheet->mergeCells("E" . ($cntrows + 2) . ":F" . ($cntrows + 2)); // Combinar celdas desde E3 hasta F3 (Preparando encabezado para SERVICIOS)
  $sheet->setCellValue("E" . ($cntrows + 2), "2. SERVICIOS");
  $sheet->getStyle("E" . ($cntrows + 2))->getFont()->setBold(true)->setSize(12);
  $sheet->getStyle("E" . ($cntrows + 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle("E" . ($cntrows + 2))->getAlignment()->setWrapText(true);

  // Encabezados de Detalle e Importe en moneda local para bienes
  $sheet->setCellValue("C" . ($cntrows + 3), "DETALLE");
  $sheet->setCellValue("D" . ($cntrows + 3), "Importe (moneda local)");
  $sheet->getStyle("C" . ($cntrows + 3) . ":D" . ($cntrows + 3))->getFont()->setBold(true)->setSize(11);
  $sheet->getStyle("C" . ($cntrows + 3) . ":D" . ($cntrows + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle("C" . ($cntrows + 3) . ":D" . ($cntrows + 3))->getAlignment()->setWrapText(true);

  // Encabezados de Detalle e Importe en moneda local para servicios
  $sheet->setCellValue("E" . ($cntrows + 3), "DETALLE");
  $sheet->setCellValue("F" . ($cntrows + 3), "Importe (moneda local)");
  $sheet->getStyle("E" . ($cntrows + 3) . ":F" . ($cntrows + 3))->getFont()->setBold(true)->setSize(11);
  $sheet->getStyle("E" . ($cntrows + 3) . ":F" . ($cntrows + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle("E" . ($cntrows + 3) . ":F" . ($cntrows + 3))->getAlignment()->setWrapText(true);

  // Encabezados para Total en moneda local y moneda extranjera
  $sheet->setCellValue("G" . ($cntrows + 3), "TOTAL (MONEDA LOCAL)");
  $sheet->setCellValue("H" . ($cntrows + 3), "TOTAL (DÓLARES)");
  $sheet->setCellValue("I" . ($cntrows + 3), 'TOTAL (EUROS)');
  $sheet->getStyle("G" . ($cntrows + 3) . ":I" . ($cntrows + 3))->getFont()->setBold(true)->setSize(12);
  $sheet->getStyle("G" . ($cntrows + 3) . ":I" . ($cntrows + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle("G" . ($cntrows + 3) . ":I" . ($cntrows + 3))->getAlignment()->setWrapText(true);

  // Aplicar estilo de alineación vertical centrada
  $sheet->getStyle("G" . ($cntrows + 3) . ":I" . ($cntrows + 3))
    ->getAlignment()
    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

  //Array de columnas que se necesitan para sumar
  $columnassumar = ['D', 'F', 'G', 'H', 'I', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
  $columnassoles = ['D', 'F', 'G', 'K', 'N', 'Q', 'T', 'W', 'Z', 'AC', 'AF', 'AI'];
  $columnasdolares = ['H', 'L', 'O', 'R', 'U', 'X', 'AA', 'AD', 'AG', 'AJ', 'AM'];
  $columnaseuros =    ['I', 'M', 'P', 'S', 'V', 'Y', 'AB', 'AE', 'AH', 'AK', 'AN'];
  $ultimacolumna = 'I'; //Es la última columna que se llea en el reporte rubros;

  // Creando nueva instancia de ReportePoaRubros, sincronizando con los datos recibidos y combinando celdas
  $repbien = new ReportePoaRubros();

  //Almacenamos la fila de inserción inicial de datos en cada reporte.
  $rowinicial = $ultcont;

  $newcntrow = ReportePoaRubros::insertarCeldasReportePOA($sheet, $respoas, $tcdolar, $tceuro, $ultcont);
  $cntrows = $newcntrow; // Actualizar $cntrows con el valor retornado por la función

  //SECCIÓN DE LA SUMA TOTALES DE COLUMNAS
  $limite = array_search($ultimacolumna, $columnassumar);
  for ($i = 0; $i <= $limite; $i++) {
    //LIMITE DE TODAS LAS INSERCIONES
    //Limite inferior con COLUMNA Y FILA
    $lmtinf = "{$columnassumar[$i]}{$rowinicial}";
    //Limite superior con COLUMNA Y FILA
    $lmtsup = "{$columnassumar[$i]}{$newcntrow}";
    //Disminuimos en uno el nuevo contador
    $newcntrowmns1 = $newcntrow - 1;
    //Establecemos el valor del límite superior menos unos
    $lmtsupmns1 = "{$columnassumar[$i]}$newcntrowmns1";

    //SECCION DE RENDICIONES
    $sheet->getStyle("$lmtinf:$lmtsupmns1")->getNumberFormat()->setFormatCode('#,##0.00');

    $sheet->setCellValue("$lmtsup", "=SUM($lmtinf:$lmtsupmns1)");


    $soles = in_array($columnassumar[$i], $columnassoles);
    $dolares = in_array($columnassumar[$i], $columnasdolares);
    $euros = in_array($columnassumar[$i], $columnaseuros);

    switch (true) {
      case $soles:
        $sheet->getStyle($lmtsup)->getNumberFormat()->setFormatCode('"S./ " #,##0.00');
        break;

      case $dolares:
        $sheet->getStyle($lmtsup)->getNumberFormat()->setFormatCode('"$ " #,##0.00');
        break;

      case $euros:
        $sheet->getStyle($lmtsup)->getNumberFormat()->setFormatCode('"€ " #,##0.00');
        break;
    }
  }

  //Marca para que phpinteliphense no marque error
  /** @var int $newcntrow */
  $newcntrow += 3; // Incrementar en 1 el valor de $newcntrow
  $colorIndex++; // Incrementar el índice del color
}
ReportePoaRubros::combinarCeldasRepetidas($sheet, $columnas);
// Guardando el archivo Excel
$writer = new Xlsx($spreadsheet);
$writer->save('reporte_poa_rubros.xlsx');

// Mostrar mensaje de reporte exitoso
echo "<h1>Reporte creado exitosamente</h1>";
echo "<a href='../reporte_poa_rubros.xlsx' target='_blank' class='btn btn-success' id='descargarReporte'>
        <i class='bi bi-file-earmark-excel'></i> Ver POA
      </a>";

// Modal para preguntar si desea guardar el POA en la base de datos

echo '
<div class="modal fade oculto" id="guardarModal" tabindex="-1" aria-labelledby="guardarModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="guardarModalLabel">Guardar POA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>¿Desea guardar el POA en la base de datos?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" class="btn btn-primary" id="guardarBtn">Sí</button>
      </div>
    </div>
  </div>
</div>
';


// Si se descarga el reporte, se debe abrir un modal con la pregunta si desea guardar el poa en la base de datos
// Si se acepta, se debe guardar el poa en la base de datos
// Si se cancela, se debe regresar la página a la vista anterior

//Modal
