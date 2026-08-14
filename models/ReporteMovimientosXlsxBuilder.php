<?php

namespace Model;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builder de los reportes Excel de INGRESOS y de EGRESOS/RENDICIONES
 * (Fase 2 de docs/plan-reportes-ingresos-y-rendiciones.md, 2026-08-14).
 *
 * Reemplaza a los helpers genéricos de ActiveRecord (insertarDatosDesdeArray e
 * insertarDatosDesdeArrayEgresosRendiciones) y a la lógica de presentación que
 * vivía dentro de views/reporte/ingresosdesc.php y rendicionesdesc.php.
 *
 * Qué corrige respecto de aquel layout:
 *
 *  - SECCIONES ETIQUETADAS en vez de una hoja plana. Antes se apilaban cosas
 *    distintas bajo un mismo encabezado: el presupuesto inicial de cada fuente
 *    caía en la columna MONTO junto a los ingresos, de modo que sumar esa
 *    columna daba S/ 810 000 cuando el ingreso del periodo era S/ 10 000.
 *    Ahora cada sección tiene su cabecera, su total y, dentro, un subtotal por
 *    fuente de financiamiento.
 *  - COLUMNAS USD/EUR QUE SÍ SE ESCRIBEN. Los helpers anteriores declaraban 14
 *    encabezados y sólo llenaban 12: las dos columnas de conversión salían
 *    siempre vacías, tirando a la basura todo el TC congelado de la migr. 030.
 *    NULL (fila sin cobertura de TC) se imprime como "—", nunca como 0.
 *  - COLUMNAS CALCULADAS con Coordinate::stringFromColumnIndex, sin letras
 *    hardcodeadas — la lección de la migr. 034.
 *  - UNA SOLA FECHA, la de OPERACIÓN (decisión D4). Antes la columna FECHA
 *    mostraba la de registro: un comprobante de marzo cargado en agosto se leía
 *    como de agosto, en contra del criterio con que se congela el tipo de
 *    cambio.
 *
 * El filtro de negocio (solo rendiciones aprobadas) NO vive aquí sino en
 * ReporteRendicionesVista::aprobadasEnRango(): el builder dibuja lo que se le
 * da y no decide qué entra en el reporte.
 */
class ReporteMovimientosXlsxBuilder
{
    /** Columnas (1-based). El dinero cae SIEMPRE en MONTO/USD/EUR, en toda sección. */
    private const COL_FECHA     = 1;   // A
    private const COL_CODIGO    = 2;   // B
    private const COL_DESCRIP   = 3;   // C
    private const COL_PROGRAMA  = 4;   // D
    private const COL_FUENTE    = 5;   // E
    private const COL_TIPO      = 6;   // F
    private const COL_RUC       = 7;   // G
    private const COL_RAZON     = 8;   // H
    private const COL_SERIE     = 9;   // I
    private const COL_NUMERO    = 10;  // J
    private const COL_DETALLE   = 11;  // K
    private const COL_ESTADO    = 12;  // L
    private const COL_MONTO     = 13;  // M
    private const COL_USD       = 14;  // N
    private const COL_EUR       = 15;  // O

    private const CABECERAS = [
        self::COL_FECHA    => 'FECHA OPERACIÓN',
        self::COL_CODIGO   => 'CÓDIGO',
        self::COL_DESCRIP  => 'DESCRIPCIÓN',
        self::COL_PROGRAMA => 'PROGRAMA',
        self::COL_FUENTE   => 'FUENTE',
        self::COL_TIPO     => 'TIPO COMPROBANTE',
        self::COL_RUC      => 'RUC',
        self::COL_RAZON    => 'RAZÓN SOCIAL',
        self::COL_SERIE    => 'SERIE',
        self::COL_NUMERO   => 'NÚMERO',
        self::COL_DETALLE  => 'DETALLE',
        self::COL_ESTADO   => 'ESTADO',
        self::COL_MONTO    => 'MONTO S/',
        self::COL_USD      => 'MONTO USD',
        self::COL_EUR      => 'MONTO EUR',
    ];

    private const SIN_TC = '—';   // fila sin cobertura de tipo de cambio

    private ?object $tcDolar;
    private ?object $tcEuro;

    /**
     * @param object|null $tcDolar TipoCambio USD vigente al cierre (o null sin cobertura).
     * @param object|null $tcEuro  ídem EUR. Solo se usan para la sección de
     *                             FUENTES: un presupuesto es planificación, no
     *                             tiene fecha de operación y por tanto no lleva
     *                             TC congelado (§2.5 del plan de montos → venta
     *                             al cierre). Los movimientos usan el suyo.
     */
    public function __construct(?object $tcDolar = null, ?object $tcEuro = null)
    {
        $this->tcDolar = $tcDolar;
        $this->tcEuro = $tcEuro;
    }

    /**
     * @param Spreadsheet $spreadsheet Documento destino (hoja activa).
     * @param string $titulo    Título del reporte (p. ej. "REPORTE DE INGRESOS").
     * @param string $desde     Inicio del rango (Y-m-d), para la cabecera.
     * @param string $hasta     Fin del rango (Y-m-d).
     * @param array  $secciones Cada elemento:
     *   'titulo' => string  encabezado de la sección
     *   'nota'   => string  aclaración bajo el encabezado (opcional)
     *   'origen' => 'oie' | 'rendicion' | 'fuente'   de qué modelo vienen las filas
     *   'estado' => string  valor de la columna ESTADO (solo origen oie/rendicion)
     *   'filas'  => array   objetos del modelo correspondiente
     * @param bool $totalGeneral Añade una fila TOTAL GENERAL sumando las
     *                           secciones de movimientos (no las de fuentes:
     *                           un presupuesto no se suma con un gasto).
     */
    public function construir(
        Spreadsheet $spreadsheet,
        string $titulo,
        string $desde,
        string $hasta,
        array $secciones,
        bool $totalGeneral = false
    ): void {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte');

        $ultima = Coordinate::stringFromColumnIndex(self::COL_EUR);
        $fila = 1;

        // Título del reporte + rango
        $sheet->mergeCells("A{$fila}:{$ultima}{$fila}");
        $sheet->setCellValue("A{$fila}", $titulo);
        $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFA500');
        $fila++;

        $sheet->mergeCells("A{$fila}:{$ultima}{$fila}");
        $sheet->setCellValue("A{$fila}", "Del {$desde} al {$hasta}  ·  montos en soles; USD y EUR con el tipo de cambio congelado de cada operación");
        $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $fila += 2;

        $totalesSeccion = [];
        foreach ($secciones as $seccion) {
            [$fila, $total] = $this->construirSeccion($sheet, $seccion, $fila);
            if (($seccion['origen'] ?? '') !== 'fuente') {
                $totalesSeccion[] = $total;
            }
            $fila++; // aire entre secciones
        }

        if ($totalGeneral && count($totalesSeccion) > 1) {
            $this->escribirFilaTotal($sheet, $fila, 'TOTAL GENERAL', $totalesSeccion, 'C00000');
        }

        foreach (array_keys(self::CABECERAS) as $c) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
    }

    /**
     * Dibuja una sección completa.
     *
     * @return array{0:int,1:array} fila siguiente y [soles, usd, eur] de la sección
     */
    private function construirSeccion(Worksheet $sheet, array $seccion, int $fila): array
    {
        $origen = $seccion['origen'] ?? 'oie';
        $esFuente = $origen === 'fuente';
        $filas = $seccion['filas'] ?? [];
        $ultima = Coordinate::stringFromColumnIndex(self::COL_EUR);

        // --- Encabezado de la sección ---------------------------------------
        $sheet->mergeCells("A{$fila}:{$ultima}{$fila}");
        $sheet->setCellValue("A{$fila}", $seccion['titulo'] ?? 'SECCIÓN');
        $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
        $fila++;

        if (!empty($seccion['nota'])) {
            $sheet->mergeCells("A{$fila}:{$ultima}{$fila}");
            $sheet->setCellValue("A{$fila}", $seccion['nota']);
            $sheet->getStyle("A{$fila}")->getFont()->setItalic(true)->setSize(9);
            $fila++;
        }

        // --- Cabeceras de columna -------------------------------------------
        $cabeceras = $esFuente ? $this->cabecerasFuente() : self::CABECERAS;
        foreach ($cabeceras as $c => $texto) {
            $celda = Coordinate::stringFromColumnIndex($c) . $fila;
            $sheet->setCellValue($celda, $texto);
            $sheet->getStyle($celda)->getFont()->setBold(true);
            $sheet->getStyle($celda)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
            $sheet->getStyle($celda)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFD700');
        }
        $fila++;

        if (!$filas) {
            $sheet->mergeCells("A{$fila}:{$ultima}{$fila}");
            $sheet->setCellValue("A{$fila}", 'Sin movimientos en el periodo.');
            $sheet->getStyle("A{$fila}")->getFont()->setItalic(true);
            $fila++;
            $this->escribirFilaTotal($sheet, $fila, 'TOTAL ' . ($seccion['titulo'] ?? ''), [[0.0, 0.0, 0.0]], '9BC2E6');
            return [$fila + 1, [0.0, 0.0, 0.0]];
        }

        // --- Filas, con subtotal al cerrar cada fuente ----------------------
        $totSec = [0.0, 0.0, 0.0];
        $totSub = [0.0, 0.0, 0.0];
        $fuenteActual = null;
        $nFuentes = 0;

        foreach ($filas as $obj) {
            $d = $esFuente ? $this->datosFuente($obj) : $this->datosMovimiento($obj, $origen);

            // En la sección de fuentes no hay subtotales: cada fila YA es una
            // fuente, así que un subtotal por grupo repetiría la misma cifra.
            if (!$esFuente && $fuenteActual !== null && $d['fuente_codigo'] !== $fuenteActual) {
                $fila = $this->escribirSubtotal($sheet, $fila, $fuenteActual, $totSub);
                $totSub = [0.0, 0.0, 0.0];
                $nFuentes++;
            }
            $fuenteActual = $d['fuente_codigo'];

            $this->escribirFila($sheet, $fila, $d, $esFuente, $seccion['estado'] ?? '');
            $fila++;

            $totSub[0] += (float) $d['monto'];
            $totSec[0] += (float) $d['monto'];
            if ($d['usd'] !== null) { $totSub[1] += (float) $d['usd']; $totSec[1] += (float) $d['usd']; }
            if ($d['eur'] !== null) { $totSub[2] += (float) $d['eur']; $totSec[2] += (float) $d['eur']; }
        }

        // El subtotal de la última fuente solo aporta si hubo más de una: con una
        // sola fuente sería idéntico al total de la sección y solo añadiría ruido.
        if (!$esFuente && $nFuentes > 0) {
            $fila = $this->escribirSubtotal($sheet, $fila, $fuenteActual, $totSub);
        }

        $this->escribirFilaTotal($sheet, $fila, 'TOTAL ' . ($seccion['titulo'] ?? ''), [$totSec], '9BC2E6');

        return [$fila + 1, $totSec];
    }

    /** Cabeceras de la sección de fuentes: el dinero va en las mismas columnas. */
    private function cabecerasFuente(): array
    {
        return [
            self::COL_FECHA  => 'ALTA',
            self::COL_CODIGO => 'FUENTE',
            self::COL_DESCRIP => 'NOMBRE',
            self::COL_MONTO  => 'PRESUPUESTO S/',
            self::COL_USD    => 'PRESUPUESTO USD',
            self::COL_EUR    => 'PRESUPUESTO EUR',
        ];
    }

    private function escribirFila(Worksheet $sheet, int $fila, array $d, bool $esFuente, string $estado): void
    {
        $set = function (int $col, $valor) use ($sheet, $fila) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $fila, $valor);
        };

        if ($esFuente) {
            $set(self::COL_FECHA, $d['fecha']);
            $set(self::COL_CODIGO, $d['fuente_codigo']);
            $set(self::COL_DESCRIP, $d['descripcion']);
        } else {
            $set(self::COL_FECHA, $d['fecha']);
            $set(self::COL_CODIGO, $d['codigo']);
            $set(self::COL_DESCRIP, $d['descripcion']);
            // Vacío = movimiento al remanente de la fuente (ingreso híbrido), no un hueco.
            $set(self::COL_PROGRAMA, $d['programa'] ?? '');
            $set(self::COL_FUENTE, $d['fuente']);
            $set(self::COL_TIPO, $d['tipo']);
            $set(self::COL_RUC, $d['ruc']);
            $set(self::COL_RAZON, $d['razon']);
            $set(self::COL_SERIE, $d['serie']);
            $set(self::COL_NUMERO, $d['numero']);
            $set(self::COL_DETALLE, $d['detalle']);
            $set(self::COL_ESTADO, $estado);
        }

        $set(self::COL_MONTO, (float) $d['monto']);
        $set(self::COL_USD, $d['usd'] === null ? self::SIN_TC : (float) $d['usd']);
        $set(self::COL_EUR, $d['eur'] === null ? self::SIN_TC : (float) $d['eur']);
        $this->formatoMoneda($sheet, $fila);
    }

    private function escribirSubtotal(Worksheet $sheet, int $fila, ?string $fuente, array $tot): int
    {
        $celda = Coordinate::stringFromColumnIndex(self::COL_DETALLE) . $fila;
        $sheet->setCellValue($celda, 'Subtotal ' . ($fuente ?? ''));
        $sheet->getStyle($celda)->getFont()->setBold(true);
        $sheet->getStyle($celda)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_MONTO) . $fila, round($tot[0], 2));
        $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_USD) . $fila, round($tot[1], 2));
        $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_EUR) . $fila, round($tot[2], 2));

        $rango = Coordinate::stringFromColumnIndex(self::COL_MONTO) . $fila
               . ':' . Coordinate::stringFromColumnIndex(self::COL_EUR) . $fila;
        $sheet->getStyle($rango)->getFont()->setBold(true);
        $sheet->getStyle($rango)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $this->formatoMoneda($sheet, $fila);

        return $fila + 1;
    }

    /** Fila TOTAL etiquetada. $totales es una lista de tripletas a sumar. */
    private function escribirFilaTotal(Worksheet $sheet, int $fila, string $etiqueta, array $totales, string $color): void
    {
        $suma = [0.0, 0.0, 0.0];
        foreach ($totales as $t) {
            $suma[0] += $t[0];
            $suma[1] += $t[1];
            $suma[2] += $t[2];
        }

        $celdaEtiqueta = Coordinate::stringFromColumnIndex(self::COL_FECHA) . $fila;
        $sheet->setCellValue($celdaEtiqueta, $etiqueta);
        $sheet->mergeCells($celdaEtiqueta . ':' . Coordinate::stringFromColumnIndex(self::COL_ESTADO) . $fila);
        $sheet->getStyle($celdaEtiqueta)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_MONTO) . $fila, round($suma[0], 2));
        $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_USD) . $fila, round($suma[1], 2));
        $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_EUR) . $fila, round($suma[2], 2));

        $rango = $celdaEtiqueta . ':' . Coordinate::stringFromColumnIndex(self::COL_EUR) . $fila;
        $sheet->getStyle($rango)->getFont()->setBold(true);
        $sheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
        $this->formatoMoneda($sheet, $fila);
    }

    private function formatoMoneda(Worksheet $sheet, int $fila): void
    {
        $rango = Coordinate::stringFromColumnIndex(self::COL_MONTO) . $fila
               . ':' . Coordinate::stringFromColumnIndex(self::COL_EUR) . $fila;
        $sheet->getStyle($rango)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    /** Normaliza una fila de OIE o de rendición a un vocabulario común. */
    private function datosMovimiento(object $o, string $origen): array
    {
        if ($origen === 'rendicion') {
            return [
                'fecha'         => $o->rendicion_fecha_original,
                'codigo'        => $o->rendicion_codigo,
                'descripcion'   => $o->rendicion_descripcion,
                'programa'      => $o->programa_nombre,
                'fuente'        => $o->fuente_financiamiento_nombre,
                'fuente_codigo' => $o->fuente_financiamiento_codigo,
                'tipo'          => $o->tipo_comprobante_descripcion,
                'ruc'           => $o->rendicion_ruc,
                'razon'         => $o->rendicion_razon_social,
                'serie'         => $o->rendicion_serie,
                'numero'        => $o->rendicion_numero,
                'detalle'       => $o->rendicion_detalle,
                'monto'         => $o->rendicion_comprobante_monto,
                'usd'           => $o->rendicion_monto_usd,
                'eur'           => $o->rendicion_monto_eur,
            ];
        }

        return [
            'fecha'         => $o->oie_comprobante_fecha_original,
            'codigo'        => $o->otros_ingresos_egresos_codigo,
            'descripcion'   => $o->otros_ingresos_egresos_descripcion,
            'programa'      => $o->programa_nombre,
            'fuente'        => $o->fuente_financiamiento_nombre,
            'fuente_codigo' => $o->fuente_financiamiento_codigo,
            'tipo'          => $o->oie_tipo_comprobante_nombre,
            'ruc'           => $o->oie_comprobante_ruc,
            'razon'         => $o->oie_comprobante_razon_social,
            'serie'         => $o->oie_comprobante_serie,
            'numero'        => $o->oie_comprobante_numero,
            'detalle'       => $o->oie_comprobante_descripcion,
            'monto'         => $o->oie_comprobante_monto,
            'usd'           => $o->oie_comprobante_monto_usd,
            'eur'           => $o->oie_comprobante_monto_eur,
        ];
    }

    /**
     * Fila de la sección de fuentes. El presupuesto es planificación: no tiene
     * fecha de operación ni TC congelado, así que se convierte con el TC de
     * VENTA vigente al cierre (§2.5 del plan de montos). Sin cobertura → "—".
     */
    private function datosFuente(object $f): array
    {
        $monto = (float) $f->fuente_monto;
        $venta = fn(?object $tc) => $tc && (float) $tc->venta > 0 ? round($monto / (float) $tc->venta, 2) : null;

        return [
            'fecha'         => $f->fuente_fecha,
            'fuente_codigo' => $f->fuente_codigo,
            'descripcion'   => $f->fuente_descripcion,
            'monto'         => $monto,
            'usd'           => $venta($this->tcDolar),
            'eur'           => $venta($this->tcEuro),
        ];
    }
}
