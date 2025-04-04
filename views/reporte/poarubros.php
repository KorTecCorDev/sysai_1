
<?php

use Model\ReportePoaRubros;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;






//Tenemos que insertar los encabezados de las fuentes de financiamiento que tengan enlazadas
//Ya tenemos las vistas de cantidad de fuentes presentes en rendiciones
//Modificar la vista de rendiciones, debería de integrar que fuente_id está relacionada
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$columnas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
$columnasrubros = ['C', 'D', 'E', 'F'];

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

//Si es coordinador, tomamos el programa_id
if ($_SESSION['cargo_id'] == 3) {
    $prgmaid = $_SESSION['programa_id'];
}

// Bucle que recorre todos los programas_id registrados
// Agrupar los registros de $resbienes por id_programa
//Contador que indica la fila a insertar el nuevo POA, parámetro requerido por la función insertarCeldasReportePOA

$resbienesAgrupados = [];
$fuentesPrograma = [];
if ($_SESSION['cargo_id'] == 3) {
    foreach ($resbienes as $bien) {
        $resbienesAgrupados[$prgmaid][] = $bien;
    }
} else {
    foreach ($resbienes as $bien) {
        $resbienesAgrupados[$bien->id_programa][] = $bien;
    }
}
foreach ($fuentes as $fuente) {
    $fuentesPrograma[$fuente->programa_id][] = $fuente;
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


    //INGRESAMOS LOS ENCABEZADOS DE LAS FUENTES DE FINANCIAMIENTO PARA LAS RENDICIONES
    //Creamos la columna disponibles que tendrá para insertar la suma de rendiciones
    $cols = ['K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    //Colocamos los encabezados de las fuentes de financiamiento
    //Creamos el array fuentesPrograma que te tiene las fuentes de financiamiento de cada programa almacenadas por indice del array como programa_id
    //Del array fuentesPrograma, obtenemos las fuentes de cada programa.
    foreach ($fuentesPrograma as $fuente) {
        //Recorremos todas estas fuentes para almacenarlas en otro array $fuentes_nombres que tendrá los encabezados ya verificados, listos para insertar
        for ($i = 0; $i < count($fuente) - 1; $i++) {
            if ($fuente[0]->programa_id = $respoas[0]->id_programa) {
                $fuentes_nombres[$i] = $fuente[$i]->fuente_financiamiento_nombre;
                //Al terminar de almacenar el primer dato, inmediatamente lo llenamos en el excel
                $sheet->setCellValue("$cols[$i]" . ($cntrows + 3), $fuentes_nombres[$i]);
            }
        }
        //Aumentamos el número de columnas
        $nrocolums = $i++;
    }
    //Aplica estilo negrita y alineaciones al encabezado
    $sheet->getStyle("$cols[0]" . ($cntrows + 3) . ":$cols[$nrocolums]" . ($cntrows + 3))->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle("$cols[0]" . ($cntrows + 3) . ":$cols[$nrocolums]" . ($cntrows + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("$cols[0]" . ($cntrows + 3) . ":$cols[$nrocolums]" . ($cntrows + 3))->getAlignment()->setWrapText(true);



    // Aplicar estilo de alineación vertical centrada
    $sheet->getStyle("G" . ($cntrows + 3) . ":I" . ($cntrows + 3))
        ->getAlignment()
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

    // Aplicar estilo de alineación vertical centrada a las sumas de rendiciones
    $sheet->getStyle("$cols[0]" . ($cntrows + 3) . ":$cols[$nrocolums]" . ($cntrows + 3))
        ->getAlignment()
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    // Creando nueva instancia de ReportePoaRubros, sincronizando con los datos recibidos y combinando celdas
    $repbien = new ReportePoaRubros();

    //Estar atento que el ff debe de llegar como un objeto
    $newcntrow = ReportePoaRubros::insertarCeldasReportePOA($sheet, $respoas, $tcdolar, $tceuro, $ultcont, $rendiciones, $ffnro[0]);
    $cntrows = $newcntrow; // Actualizar $cntrows con el valor retornado por la función
    //Marca para que phpinteliphense no marque error
    /** @var int $newcntrow */
    $newcntrow += 3; // Incrementar en 1 el valor de $newcntrow
    $colorIndex++; // Incrementar el índice del color
}
ReportePoaRubros::combinarCeldasRepetidas($sheet, $columnas);
/*SECCION DE ALMACENAMIENTO EN EL SERVIDOR*/
$directory = __DIR__ . "/storage/reports/";
if (!is_dir($directory)) {
    mkdir($directory, 0777, true); // Crea la carpeta con permisos de escritura
}

$file = $directory . "reporte_poa_rubros.xlsx";
$writer = new Xlsx($spreadsheet);
$writer->save($file);

// Mostrar mensaje de reporte exitoso
echo "<h1>Reporte creado exitosamente</h1>";
echo "<a href='../descargar?rprt={$file}' target='_blank' class='btn btn-success' id='descargarReporte'>
        <i class='bi bi-file-earmark-excel'></i> Ver POA Global
      </a>";
/*SECCION DE ALMACENAMIENTO EN EL SERVIDOR*/
