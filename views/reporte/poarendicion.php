<?php
// Reporte Excel "Rendición POA": rubros del POA + rendiciones aprobadas del
// ejercicio vigente por fuente, alineadas por RUBRO (migr. 034), y fila de
// TRANSFERENCIA A PROGRAMA INSTITUCIONAL (migr. 033). El layout vive en
// ReporteRendicionXlsxBuilder; esta vista solo agrupa datos, invoca y guarda.

use Model\Programa;
use Model\TransferenciaInstitucional;
use Model\ReporteRendicionXlsxBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Coordinador: solo su programa. Contador/Admin: todos.
$soloPrograma = ($_SESSION['cargo_id'] == 3) ? (int) ($_SESSION['programa_id'] ?? 0) : null;

// Agrupar rubros y fuentes por programa (el orden de la vista ya es por actividad).
$filasPorPrograma = [];
$nombrePrograma = [];
foreach ($resbienes as $bien) {
    if ($soloPrograma !== null && (int) $bien->id_programa !== $soloPrograma) continue;
    $filasPorPrograma[(int) $bien->id_programa][] = $bien;
    $nombrePrograma[(int) $bien->id_programa] = $bien->programa;
}
$fuentesPorPrograma = [];
foreach ($fuentes as $fuente) {
    if ($soloPrograma !== null && (int) $fuente->programa_id !== $soloPrograma) continue;
    $fuentesPorPrograma[(int) $fuente->programa_id][] = $fuente;
}
// El orden de las columnas de fuente se fija AQUÍ (por id = orden de alta) porque la
// vista se lee con `all()`, sin ORDER BY: MySQL puede devolver las filas en cualquier
// orden y el mismo reporte salía con las fuentes en columnas distintas de una
// generación a otra (detectado 2026-08-12: la fuente 1 caía unas veces en K y otras
// en N). Los datos nunca fueron incorrectos —cada columna lleva su encabezado— pero
// el contador compara reportes entre sí y las columnas deben quedarse quietas.
foreach ($fuentesPorPrograma as &$listaFuentes) {
    usort($listaFuentes, fn($a, $b) => (int) $a->fuente_financiamiento_id <=> (int) $b->fuente_financiamiento_id);
}
unset($listaFuentes);
// Rendiciones (aprobadas, año vigente) indexadas por rubro y fuente (migr. 034).
$rendicionesPorRubro = [];
foreach ($rendiciones as $r) {
    $rendicionesPorRubro[(int) $r->rubro_id][(int) $r->fuente_financiamiento_id] = $r;
}

// Bloques para el builder (transferencia = Σ transferido por el programa origen;
// para el Institucional es 0 por definición: nunca es origen).
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

// Guardado en el servidor + botón de descarga (mismo archivo y ruta de siempre).
$directory = __DIR__ . "/storage/reports/";
if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
}
$filename = "reporte_poa_rendicion_{$usrcod}.xlsx";
$writer = new Xlsx($spreadsheet);
$writer->save($directory . $filename);

echo "<a href='../descargar?rprt={$filename}' target='_blank' class='btn btn-success' id='descargarReporte'>
        <i class='bi bi-file-earmark-excel'></i> Ver Rendiciones
      </a>";
