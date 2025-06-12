<?php
//Namespaces
use Model\ReportePoaRubros;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Reader\Xml\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Reader\Xml\Style\NumberFormat;

//Captamos datos del usuario
//datos de usuario
// $usuario = $_SESSION['datos'];
//Tenemos que insertar los encabezados de las fuentes de financiamiento que tengan enlazadas
//Ya tenemos las vistas de cantidad de fuentes presentes en rendiciones
//Modificar la vista de rendiciones, debería de integrar que fuente_id está relacionada
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$columnas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
$columnasrubros = ['C', 'D', 'E', 'F'];

//Si es coordinador, tomamos el programa_id
if ($_SESSION['cargo_id'] == 3) {
    $prgmaid = $_SESSION['programa_id'];
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
//Creamos la columna disponibles que tendrá para insertar la suma de rendiciones
$cols = [
    'K',
    'L',
    'M',
    'N',
    'O',
    'P',
    'Q',
    'R',
    'S',
    'T',
    'U',
    'V',
    'W',
    'X',
    'Y',
    'Z',
    'AA',
    'AB',
    'AC',
    'AD',
    'AE',
    'AF',
    'AG',
    'AH',
    'AI',
    'AJ',
    'AK',
    'AL',
    'AM',
    'AN',
    'AO',
    'AP',
    'AQ',
    'AR',
    'AS',
    'AT',
    'AU',
    'AV',
    'AW',
    'AX',
    'AY',
    'AZ'
];
$colorIndex = 0;

// Bucle que recorre todos los programas_id registrados
// Agrupar los registros de $resbienes por id_programa
//Contador que indica la fila a insertar el nuevo POA, parámetro requerido por la función insertarCeldasReportePOA
$resbienesAgrupados = [];
$fuentesPrograma = [];
//En caso sea coordinador
if ($_SESSION['cargo_id'] == 3) {
    foreach ($resbienes as $bien) {
        if ($bien->id_programa == $prgmaid) {
            //Array que contiene a los rubros agrupados por id_programa del coordinador
            //Vista - reporte_poa_rubros
            $resbienesAgrupados[$prgmaid][] = $bien;
        }
    }
    foreach ($fuentes as $fuente) {
        //Array que contiene a las fuentes relacionadas al programa por id_programa del coordinador
        //Vista - reporte_fuentes_programa_rendicion
        if ($fuente->programa_id == $prgmaid) {
            $fuentesPrograma[$prgmaid][] = $fuente;
        }
    }
} else {
    //En caso sea un usuario administrador
    foreach ($resbienes as $bien) {
        //Array que contiene a los rubros agrupados por id_programa de todos los programas (Reporte Poa Rubros Completo)
        $resbienesAgrupados[$bien->id_programa][] = $bien;
    }
    foreach ($fuentes as $fuente) {
        //Array que contiene a las fuentes relacionadas al programa de todos los programas (Reporte Poa Rubros Completo)
        $fuentesPrograma[$fuente->programa_id][] = $fuente;
    }
}
//Recorremos el array $resbienesAgrupados
foreach ($resbienesAgrupados as $respoas) {
    // Capturamos el nombre del programa actual para la cabecera principal
    $nombreProgramaActual = $respoas[0]->programa;
    // Determinar la última columna realmente utilizada para este bloque (antes de insertar datos)
    $fuentesCount = isset($fuentesPrograma[$respoas[0]->id_programa]) ? count($fuentesPrograma[$respoas[0]->id_programa]) : 0;
    $colFin = $fuentesCount > 0 ? $cols[$fuentesCount * 3 - 1] : 'I'; // Si hay fuentes, hasta la última columna de fuentes, si no, hasta I
    // Determinar la fila actual donde irá la cabecera (antes del bloque de ese programa)
    $cabeceraFila = isset($newcntrow) ? $newcntrow + 1 : 2;
    $sheet->mergeCells("A{$cabeceraFila}:{$colFin}{$cabeceraFila}");
    $sheet->setCellValue("A{$cabeceraFila}", 'PRESUPUESTO - ' . date('Y') . ' - PROGRAMA ' . strtoupper($nombreProgramaActual));
    $sheet->getStyle("A{$cabeceraFila}")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("A{$cabeceraFila}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("A{$cabeceraFila}:{$colFin}{$cabeceraFila}")->getFill()->setFillType(Fill::FILL_SOLID);
    $sheet->getStyle("A{$cabeceraFila}:{$colFin}{$cabeceraFila}")->getFill()->getStartColor()->setRGB($colores[$colorIndex % count($colores)]);
    $colorIndex++;

    $ultcont = $newcntrow ?? 5; // Si $newcntrow no está definido, asignar 5 a $ultcont , está definido por default a un valor 5 para que inicie en la fila correcta el reporte.
    if ($ultcont != 5) {
        $ultcont += 4; //Sumamos 4 para que el contenido ingrese luego del encabezado
    }

    // Este contador nos permite saber en qué fila ingresamos los registros de cada POA de programa (esto incluye los encabezados)
    $cntrows = 1;
    if (isset($newcntrow)) {
        //En caso exista la variable $newcntrow que es retornada por la función insertarCeldasDatos
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

    /*Bloque de inserción de la imagen*/
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
    /*FIN - Bloque de inserción de la imagen*/

    /*Bloque del encabezado*/
    /*Bloque de inserción del título del encabezado de cada POA*/
    // --- ELIMINADO: Inserción de la cabecera principal aquí ---

    // Insertando encabezados personalizados
    //Bloque de inserción de los encabezados estáticos (Siempre van a estar ahí para todo POA)
    //Bloque de Bienes
    $sheet->mergeCells("C" . ($cntrows + 2) . ":D" . ($cntrows + 2)); // Combinar celdas desde C3 hasta D3 (Preparando encabezado para BIENES)
    $sheet->setCellValue("C" . ($cntrows + 2), "1. BIENES");
    $sheet->getStyle("C" . ($cntrows + 2))->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle("C" . ($cntrows + 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("C" . ($cntrows + 2))->getAlignment()->setWrapText(true);
    //FIN - Bloque de Bienes

    //Bloque de Servicios
    $sheet->mergeCells("E" . ($cntrows + 2) . ":F" . ($cntrows + 2)); // Combinar celdas desde E3 hasta F3 (Preparando encabezado para SERVICIOS)
    $sheet->setCellValue("E" . ($cntrows + 2), "2. SERVICIOS");
    $sheet->getStyle("E" . ($cntrows + 2))->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle("E" . ($cntrows + 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("E" . ($cntrows + 2))->getAlignment()->setWrapText(true);
    //FIN Bloque de Servicios

    //Bloque de Moneda Local
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
    //FIN - Bloque de Moneda Local

    // Encabezados para Total en moneda local y moneda extranjera
    $sheet->setCellValue("G" . ($cntrows + 3), "TOTAL (MONEDA LOCAL)");
    $sheet->setCellValue("H" . ($cntrows + 3), "TOTAL (DÓLARES)");
    $sheet->setCellValue("I" . ($cntrows + 3), 'TOTAL (EUROS)');
    $sheet->getStyle("G" . ($cntrows + 3) . ":I" . ($cntrows + 3))->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle("G" . ($cntrows + 3) . ":I" . ($cntrows + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("G" . ($cntrows + 3) . ":I" . ($cntrows + 3))->getAlignment()->setWrapText(true);

    //FIN - Bloque de inserción de los encabezados estáticos (Siempre van a estar ahí para todo POA)

    // Encabezados para las fuentes de financiamiento
    // Determinar la cantidad de fuentes para este programa
    $fuentesCount = isset($fuentesPrograma[$respoas[0]->id_programa]) ? count($fuentesPrograma[$respoas[0]->id_programa]) : 0;
    if ($fuentesCount > 0) {
        $colInicio = 'K';
        $colFin = $cols[$fuentesCount * 3 - 1]; // Cada fuente ocupa 3 columnas (Soles, Dólares, Euros)
        $sheet->setCellValue("{$colInicio}" . ($cntrows + 2), "FUENTES DE FINANCIAMIENTO");
        $sheet->mergeCells("{$colInicio}" . ($cntrows + 2) . ":{$colFin}" . ($cntrows + 2));
        $sheet->getStyle("{$colInicio}" . ($cntrows + 2) . ":{$colFin}" . ($cntrows + 2))->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("{$colInicio}" . ($cntrows + 2) . ":{$colFin}" . ($cntrows + 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$colInicio}" . ($cntrows + 2) . ":{$colFin}" . ($cntrows + 2))->getAlignment()->setWrapText(true);
    }

    //INGRESAMOS LOS ENCABEZADOS DE LAS FUENTES DE FINANCIAMIENTO PARA LAS RENDICIONES

    //Colocamos los encabezados de las fuentes de financiamiento
    //Creamos el array fuentesPrograma que te tiene las fuentes de financiamiento de cada programa almacenadas por indice del array como programa_id
    //Del array fuentesPrograma, obtenemos las fuentes de cada programa.
    //Bloque del separador COLUMNA 'J'
    //Asignando tamaño de celda para la columna J, es un SEPARADOR
    $sheet->getColumnDimension('J')->setAutoSize(false);
    $sheet->getColumnDimension('J')->setWidth(3);
    //FIN - Bloque del separador COLUMNA 'J'

    //Bloque de inserción de los encabezados dinámicos (Cambian de acuerdo a la relación detalle_financiamiento que exista en la DB)
    //Creamos un array que contendrá las columnas seleccionadas según el id de la fuente de financiamiento
    $arrayFuentes = [];
    //Insertamos los encabezados según el programa_id
    //Si, existiera una sola fuente para el programa que se está recorriendo en el foreach de $resbienesAgrupados...
    if ($fuentesPrograma[$respoas[0]->id_programa]) {
        //Creamos una variable $fte que tome una o la única fuente que está vinculada al programa que estamos recorriendo por el momento.
        $fte = $fuentesPrograma[$respoas[0]->id_programa];
        if ($fte) {
            //Creamos un contador para ...
            $contespecial = 0;

            for ($i = 0; $i < count($fte); $i++) {
                //Obtenemos el objeto fuente de financiamiento que toque en el ciclo
                $fuenteobj = $fte[$i];
                //Obtenemos el nombre de la fuente de financiamiento
                $fuentenombre = $fuenteobj->fuente_financiamiento_nombre;
                //Obtenemos el id de la fuente de financiamiento
                $fuenteid = $fuenteobj->fuente_financiamiento_id;
                //Obtenemos la columna donde se insertará la suma de rendiciones
                $columnita = $cols[$contespecial];
                $columnita1 = $cols[$contespecial + 1];
                $columnita2 = $cols[$contespecial + 2];

                //Insertar los datos en los encabezados por programa
                $sheet->setCellValue($columnita . ($cntrows + 3), $fuentenombre);
                $sheet->getStyle($columnita . ($cntrows + 3))->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle($columnita . ($cntrows + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($columnita . ($cntrows + 3))->getAlignment()->setWrapText(true);
                //Asignando tamaño de celda para la columna
                $sheet->getColumnDimension($columnita)->setWidth(15);

                //Insertamos la columna de los dólares
                $sheet->setCellValue($columnita1 . ($cntrows + 3), "TOTAL DÓLARES");
                $sheet->getStyle($columnita1 . ($cntrows + 3))->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle($columnita1 . ($cntrows + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($columnita1 . ($cntrows + 3))->getAlignment()->setWrapText(true);
                //Asignando tamaño de celda para la columna
                $sheet->getColumnDimension($columnita1)->setWidth(15);

                //Insertamos la columna de los euros
                $sheet->setCellValue($columnita2 . ($cntrows + 3), "TOTAL EUROS");
                $sheet->getStyle($columnita2 . ($cntrows + 3))->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle($columnita2 . ($cntrows + 3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($columnita2 . ($cntrows + 3))->getAlignment()->setWrapText(true);
                //Asignando tamaño de celda para la columna
                $sheet->getColumnDimension($columnita2)->setWidth(15);

                //Almacenamos la relación entre la fuente financiamiento y la columna donde se inserta la suma de rendiciones en el reporte
                if (empty($arrayFuentes)) {
                    $arrayFuentes[$fuenteid] = $columnita;
                    $contespecial = 3; //Considerar quitar esta línea
                } else {
                    $arrayFuentes[$fuenteid] = $cols[3 * $i];
                    $contespecial = $i * 3 + 3;
                }
            }

            //Dando estilos a los encabezados de las fuentes de financiamiento
            // (Eliminada la combinación de celdas aquí para evitar conflicto con la combinación final)
            $sheet->getStyle("K" . ($cntrows + 2) . ":{$cols[$i - 1]}" . ($cntrows + 2))->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle("K" . ($cntrows + 2) . ":{$cols[$i - 1]}" . ($cntrows + 2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K" . ($cntrows + 2) . ":{$cols[$i - 1]}" . ($cntrows + 2))->getAlignment()->setWrapText(true);
        }
    }
    //FIN - Bloque de insericón de los encabezados dinámicos (Cambian de acuerdo a la relación detalle_financiamiento que exista en la DB)
    /*FIN - Bloque del encabezado*/

    //Considerar borrar si no se va a usar
    // Creando nueva instancia de ReportePoaRubros, sincronizando con los datos recibidos y combinando celdas
    // $repbien = new ReportePoaRubros();
    //Considerar borrar si no se va a usar

    //Almacenamos la fila de inserción inicial de datos en cada reporte.
    $rowinicial = $ultcont;
    $newcntrow = ReportePoaRubros::insertarCeldasReportePOA($sheet, $respoas, $tcdolar, $tceuro, $rowinicial, $rendiciones, $arrayFuentes, $cols) ?? 0;
    $cntrows = $newcntrow; // Actualizar $cntrows con el valor retornado por la función
    //Insertamos las sumas de todas las columnas
    //Tomamos el rango de las columnas que están llenas
    $ultimacolumna = $cols[$contespecial - 1];

    //Combinamos el encabezado fuentes de financiamiento
    $columnassumar = ['D', 'F', 'G', 'H', 'I', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    $columnasumarendi = ['K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

    $columnassoles = ['D', 'F', 'G', 'K', 'N', 'Q', 'T', 'W', 'Z', 'AC', 'AF', 'AI'];
    $columnasdolares = ['H', 'L', 'O', 'R', 'U', 'X', 'AA', 'AD', 'AG', 'AJ', 'AM'];
    $columnaseuros =    ['I', 'M', 'P', 'S', 'V', 'Y', 'AB', 'AE', 'AH', 'AK', 'AN'];

    $columnassolesrendi = ['K', 'N', 'Q', 'T', 'W', 'Z', 'AC', 'AF', 'AI', 'AL'];
    $columnasdolaresrendi = ['L', 'O', 'R', 'U', 'X', 'AA', 'AD', 'AG', 'AJ', 'AM'];
    $columnaseurosrendi =    ['M', 'P', 'S', 'V', 'Y', 'AB', 'AE', 'AH', 'AK', 'AN'];

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


    //SECCIÓN DE SUMAS DE FILAS
    //Limpiamos las variables
    $sumasoles = [];
    $sumadolares = [];
    $sumaeuros = [];
    //LLENAMOS ARRAYS CON LAS COLUMNAS A SUMAR
    $limiterendi = array_search($ultimacolumna, $columnasumarendi);
    for ($i = 0; $i <= $limiterendi; $i++) {
        if (in_array($columnasumarendi[$i], $columnassolesrendi)) {
            $sumasoles[] = $columnasumarendi[$i] ?? null;
        } else if (in_array($columnasumarendi[$i], $columnasdolaresrendi)) {
            $sumadolares[] = $columnasumarendi[$i] ?? null;
        } else if (in_array($columnasumarendi[$i], $columnaseurosrendi)) {
            $sumaeuros[] = $columnasumarendi[$i] ?? null;
        }
    }

    $filainicial = $ultcont;
    $cadenaformula = "=";
    //NUEVA SECCIÓN
    for ($i = $rowinicial; $i < $newcntrow; $i++) {
        //RECORREMOS TODOS LOS VALORES DE SUMASOLES
        foreach ($sumasoles as $sumasol) {
            //Tenemos cada columna de soles para poder sumarlas
            //Creamos la fórmula a insertar
            $cadenaformula .= "{$sumasol}{$i}+";
        }
        $cadenaformula = rtrim($cadenaformula, "+");
        //Insertamos la función en la celda siguiente
        $sheet->setCellValue("{$cols[$limiterendi + 1]}{$i}", $cadenaformula);
        //RECORREMOS TODOS LOS VALORES DE SUMADÓLARES
        $cadenaformula = "=";
        foreach ($sumadolares as $sumadolar) {
            //Tenemos cada columna de soles para poder sumarlas
            //Creamos la fórmula a insertar
            $cadenaformula .= "{$sumadolar}{$i}+";
        }
        $cadenaformula = rtrim($cadenaformula, "+");
        //Insertamos la función en la celda siguiente
        $sheet->setCellValue("{$cols[$limiterendi + 2]}{$i}", $cadenaformula);
        //RECORREMOS TODOS LOS VALORES DE SUMAEUROS
        $cadenaformula = "=";
        foreach ($sumaeuros as $sumaeuro) {
            //Tenemos cada columna de soles para poder sumarlas
            //Creamos la fórmula a insertar
            $cadenaformula .= "{$sumaeuro}{$i}+";
        }
        $cadenaformula = rtrim($cadenaformula, "+");
        //Insertamos la función en la celda siguiente
        $sheet->setCellValue("{$cols[$limiterendi + 3]}{$i}", $cadenaformula);
        //Ubicamos la columna a insertar el resultado de suma
        $cadenaformula = "=";
    }

    //NUEVA SECCIÓN

    //Marca para que phpinteliphense no marque error
    /** @var int $newcntrow */
    $newcntrow += 3; // Incrementar en 3 el valor de $newcntrow
    $colorIndex++; // Incrementar el índice del color
}

//Combinamos las celdas repetidas
ReportePoaRubros::combinarCeldasRepetidas($sheet, $columnas);

/*SECCION DE ALMACENAMIENTO EN EL SERVIDOR*/
// Guardando el archivo Excel
$directory = __DIR__ . "/storage/reports/";
if (!is_dir($directory)) {
    mkdir($directory, 0777, true); // Crea la carpeta con permisos de escritura
}
$filename = "reporte_poa_rendicion_{$usrcod}.xlsx";
$file = $directory . $filename;
$writer = new Xlsx($spreadsheet);
$writer->save($file);

// Genera el nombre del archivo
$filename = "reporte_poa_rendicion_{$usrcod}.xlsx";
echo "<a href='../descargar?rprt={$filename}' target='_blank' class='btn btn-success' id='descargarReporte'>
        <i class='bi bi-file-earmark-excel'></i> Ver Rendiciones
      </a>";
/*SECCION DE ALMACENAMIENTO EN EL SERVIDOR*/

// === INSERCIÓN DEL ENCABEZADO 'FUENTES DE FINANCIAMIENTO' AL FINAL ===
// Buscar la primera fila donde aparece el encabezado de fuentes (K?) y la última columna utilizada para fuentes
$fuentesMax = 0;
foreach ($fuentesPrograma as $fuentesArr) {
    if (count($fuentesArr) > $fuentesMax) {
        $fuentesMax = count($fuentesArr);
    }
}
if ($fuentesMax > 0) {
    $colInicio = 'K';
    $colFin = $cols[$fuentesMax * 3 - 1]; // Cada fuente ocupa 3 columnas
    // Buscar la fila donde se encuentra el encabezado de fuentes (la primera vez que se usó K...)
    $filaEncabezado = null;
    foreach ($sheet->toArray() as $filaNum => $fila) {
        if (isset($fila[10]) && $fila[10] === 'FUENTES DE FINANCIAMIENTO') { // K = índice 10
            $filaEncabezado = $filaNum + 1; // +1 porque toArray es base 0
            break;
        }
    }
    if ($filaEncabezado) {
        $sheet->mergeCells("{$colInicio}{$filaEncabezado}:{$colFin}{$filaEncabezado}");
        $sheet->getStyle("{$colInicio}{$filaEncabezado}:{$colFin}{$filaEncabezado}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("{$colInicio}{$filaEncabezado}:{$colFin}{$filaEncabezado}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$colInicio}{$filaEncabezado}:{$colFin}{$filaEncabezado}")->getAlignment()->setWrapText(true);
    }
}
