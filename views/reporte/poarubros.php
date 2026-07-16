<?php
// Reporte Excel "POA General" (admin/contador): mismo contenido que el de
// rendición — rubros + rendiciones aprobadas del año por fuente, alineadas por
// RUBRO — para TODOS los programas. Layout en ReporteRendicionXlsxBuilder.

use Model\TransferenciaInstitucional;
use Model\ReporteRendicionXlsxBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$filasPorPrograma = [];
$nombrePrograma = [];
foreach ($resbienes as $bien) {
    $filasPorPrograma[(int) $bien->id_programa][] = $bien;
    $nombrePrograma[(int) $bien->id_programa] = $bien->programa;
}
$fuentesPorPrograma = [];
foreach ($fuentes as $fuente) {
    $fuentesPorPrograma[(int) $fuente->programa_id][] = $fuente;
}
$rendicionesPorRubro = [];
foreach ($rendiciones as $r) {
    $rendicionesPorRubro[(int) $r->rubro_id][(int) $r->fuente_financiamiento_id] = $r;
}

$bloques = [];
foreach ($filasPorPrograma as $programaId => $filas) {
    $bloques[] = [
        'programa'      => $nombrePrograma[$programaId],
        'filas'         => $filas,
        'fuentes'       => $fuentesPorPrograma[$programaId] ?? [],
        'rendiciones'   => $rendicionesPorRubro,
        'transferencia' => TransferenciaInstitucional::totalPorOrigen($programaId),
    ];
}

$spreadsheet = new Spreadsheet();
$builder = new ReporteRendicionXlsxBuilder($tcdolar, $tceuro, 'RENDICIÓN');
$builder->construir($spreadsheet, $bloques);

$directory = __DIR__ . "/storage/reports/";
if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
}
$filename = "reporte_poa_rubros_{$usrcod}.xlsx";
$writer = new Xlsx($spreadsheet);
$writer->save($directory . $filename);

echo "<a href='../descargar?rprt={$filename}' target='_blank' class='btn btn-success' id='descargarReporte'>
        <i class='bi bi-file-earmark-excel'></i> Ver POA General
      </a>";
