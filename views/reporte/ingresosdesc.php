<?php
// Reporte Excel de INGRESOS del periodo. Orquestador delgado: arma las secciones
// y delega el dibujo en ReporteMovimientosXlsxBuilder (Fase 2-3 del plan de
// reportes, 2026-08-14). El filtro por fecha de operación y la carga de datos
// viven en el controlador y los modelos.

use Model\ReporteMovimientosXlsxBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$spreadsheet = new Spreadsheet();

$tasas = [];
if ($tcdolar) { $tasas[] = 'USD ' . rtrim(rtrim((string) $tcdolar->venta, '0'), '.') . ' (' . $tcdolar->fecha_vigencia . ')'; }
if ($tceuro)  { $tasas[] = 'EUR ' . rtrim(rtrim((string) $tceuro->venta, '0'), '.') . ' (' . $tceuro->fecha_vigencia . ')'; }
$notaFuentes = 'Presupuesto inicial de cada fuente. NO depende del rango de fechas: es el contexto del periodo, no un ingreso. '
    . ($tasas
        ? 'Convertido con el tipo de cambio de venta al cierre — ' . implode(' · ', $tasas) . '.'
        : 'Sin tipo de cambio registrado: las columnas USD y EUR quedan en blanco.');

$builder = new ReporteMovimientosXlsxBuilder($tcdolar, $tceuro);
$builder->construir($spreadsheet, 'REPORTE DE INGRESOS', $desde, $hasta, [
    [
        'titulo' => 'INGRESOS DEL PERIODO',
        'nota'   => 'Otros ingresos (OIE) con fecha de operación dentro del rango. Un PROGRAMA vacío significa que el ingreso va al remanente de la fuente, sin asignar a un sobre.',
        'origen' => 'oie',
        'estado' => 'Aprobado (automático)',
        'filas'  => $ingresos,
    ],
    [
        'titulo' => 'PRESUPUESTO DE LAS FUENTES',
        'nota'   => $notaFuentes,
        'origen' => 'fuente',
        'filas'  => $fuentes,
    ],
]);

descargarXlsx($spreadsheet, "reporte_ingresos_{$usrcod}");
