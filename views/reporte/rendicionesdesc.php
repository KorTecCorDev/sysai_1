<?php
// Reporte Excel de EGRESOS del periodo: rendiciones aprobadas + otros egresos
// (OIE), cada uno en su sección con su total. Orquestador delgado: el dibujo lo
// hace ReporteMovimientosXlsxBuilder (Fase 2-3 del plan de reportes, 2026-08-14).
//
// El título dice EGRESOS aunque la ruta se llame /reporte/rendiciones: es lo que
// contiene. Antes decía "REPORTE EGRESOS" en una pantalla rotulada "rendiciones"
// y el archivo se llamaba "poa_rendiciones_general", tres nombres para una cosa.

use Model\ReporteMovimientosXlsxBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$spreadsheet = new Spreadsheet();

$builder = new ReporteMovimientosXlsxBuilder();
$builder->construir($spreadsheet, 'REPORTE DE EGRESOS', $desde, $hasta, [
    [
        'titulo' => 'RENDICIONES APROBADAS',
        'nota'   => 'Solo rendiciones APROBADAS, con fecha de operación dentro del rango. Las pendientes no descuentan el presupuesto contable y todavía pueden observarse, así que no forman parte de la rendición de cuentas.',
        'origen' => 'rendicion',
        'estado' => 'Aprobada',
        'filas'  => $rendiciones,
    ],
    [
        'titulo' => 'OTROS EGRESOS (OIE)',
        'nota'   => 'Gastos fuera del POA registrados por el Contador, con fecha de operación dentro del rango.',
        'origen' => 'oie',
        'estado' => 'Aprobado (automático)',
        'filas'  => $egresos,
    ],
], true);

descargarXlsx($spreadsheet, "reporte_egresos_{$usrcod}");
