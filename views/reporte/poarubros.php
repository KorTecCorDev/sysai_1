<?php
// Reporte Excel "POA General" (admin/contador): mismo contenido que el de
// rendición — rubros + rendiciones aprobadas del año por fuente, alineadas por
// RUBRO — para TODOS los programas. Layout en ReporteRendicionXlsxBuilder.

use Model\TransferenciaInstitucional;
use Model\ReporteRendicionXlsxBuilder;

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

// nuevoLibroXlsx() y no `new Spreadsheet()`: los textos de usuario nunca se vuelven fórmula.
$spreadsheet = nuevoLibroXlsx();
$builder = new ReporteRendicionXlsxBuilder($tcdolar, $tceuro, 'RENDICIÓN');
$builder->construir($spreadsheet, $bloques);

// Descarga directa (Fase 0, 2026-08-14): antes se escribía el .xlsx dentro del
// document root y se devolvía un enlace a /descargar. Ver descargarXlsx().
descargarXlsx($spreadsheet, "reporte_poa_rubros_{$usrcod}");
