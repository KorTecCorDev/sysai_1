<?php

namespace Model;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builder del reporte Excel de rendición POA (item 9, 2026-07-16).
 *
 * Reemplaza el layout artesanal repartido entre views/reporte/poarendicion.php,
 * poarubros.php y los helpers de ActiveRecord (insertarRendicionesFuente y cía),
 * CONSERVANDO el diseño visual: bloques apilados por programa con cabecera
 * coloreada, secciones 1. BIENES / 2. SERVICIOS, totales por actividad en G/H/I,
 * separador J y tripletas (S/, USD, EUR) por fuente desde K.
 *
 * Correcciones estructurales respecto del layout anterior:
 *  - Columnas CALCULADAS (Coordinate::stringFromColumnIndex): sin arrays K..Z
 *    hardcodeados → soporta N fuentes, sin sumas que desaparecen ni contadores
 *    compartidos entre bloques.
 *  - Rendiciones por fuente alineadas EN LA FILA DE SU RUBRO (la vista
 *    reporte_poa_rendicion agrupa por rubro×fuente — migr. 034; solo aprobadas
 *    del ejercicio vigente).
 *  - Columnas "TOTAL RENDIDO" con encabezado y posición fija tras la última
 *    fuente del bloque; fórmulas solo en filas con datos.
 *  - Fila "TRANSFERENCIA A PROGRAMA INSTITUCIONAL" (migr. 033) antes del TOTAL:
 *    el total del bloque = Σ rubros + transferencia (el cargo completo al grant).
 *  - Fila de totales al pie ETIQUETADA; merges de repetidos solo en columnas de
 *    etiquetas (A, B) y acotados al bloque (nunca montos ni entre bloques).
 */
class ReporteRendicionXlsxBuilder
{
    /** Índices fijos de columnas (1-based): A..I datos, J separador, K.. fuentes. */
    private const COL_PRODUCTO = 1;   // A
    private const COL_ACTIVIDAD = 2;  // B
    private const COL_BIEN_DET = 3;   // C
    private const COL_BIEN_IMP = 4;   // D
    private const COL_SERV_DET = 5;   // E
    private const COL_SERV_IMP = 6;   // F
    private const COL_TOT_SOL = 7;    // G
    private const COL_TOT_USD = 8;    // H
    private const COL_TOT_EUR = 9;    // I
    private const COL_SEPARADOR = 10; // J
    private const COL_FUENTES_INICIO = 11; // K

    private const COLORES = [
        'FFC000', 'FF5733', '33FF57', '3357FF', 'FF33A1',
        'C70039', '900C3F', '581845', '00FFFF', 'FFFF00',
    ];

    private ?object $tcDolar;
    private ?object $tcEuro;
    private string $titulo;

    /**
     * @param object|null $tcDolar TipoCambio vigente al cierre (o null sin cobertura)
     * @param object|null $tcEuro  ídem para EUR. Planificación → tasa de VENTA (§2.5).
     * @param string $titulo Prefijo de la cabecera del bloque (p. ej. "RENDICIÓN").
     */
    public function __construct(?object $tcDolar, ?object $tcEuro, string $titulo = 'RENDICIÓN')
    {
        $this->tcDolar = $tcDolar;
        $this->tcEuro = $tcEuro;
        $this->titulo = $titulo;
    }

    /**
     * Construye el reporte completo: un bloque por programa.
     *
     * @param Spreadsheet $spreadsheet Documento destino (hoja activa).
     * @param array $bloques Cada elemento:
     *   'programa'      => string  nombre del programa
     *   'filas'         => array   filas de reporte_poa_rubros del programa (una por rubro,
     *                              ordenadas por actividad; `id` = rubro id)
     *   'fuentes'       => array   filas de reporte_fuentes_programa_rendicion del programa
     *   'rendiciones'   => array   mapa rubro_id => [ff_id => fila de reporte_poa_rendicion]
     *   'transferencia' => float   Σ transferido al Institucional por este programa (0 = sin fila)
     */
    public function construir(Spreadsheet $spreadsheet, array $bloques): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $filaBase = 1;
        $indiceColor = 0;

        // Anchos fijos de la zona A..I + separador J (el diseño original).
        for ($c = self::COL_PRODUCTO; $c <= self::COL_TOT_EUR; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(15);
        }
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::COL_SEPARADOR))->setWidth(3);

        foreach ($bloques as $bloque) {
            $filaBase = $this->construirBloque($sheet, $bloque, $filaBase, self::COLORES[$indiceColor % count(self::COLORES)]);
            $indiceColor++;
        }
    }

    /** Construye un bloque de programa y devuelve la fila base del siguiente. */
    private function construirBloque(Worksheet $sheet, array $bloque, int $filaBase, string $color): int
    {
        $filas = $bloque['filas'];
        $fuentes = array_values($bloque['fuentes'] ?? []);
        $rendiciones = $bloque['rendiciones'] ?? [];
        $transferencia = (float) ($bloque['transferencia'] ?? 0);
        $nFuentes = count($fuentes);

        // Geometría de columnas del bloque (calculada, nunca adivinada).
        $colFuente = [];             // ff_id => índice de su primera columna (S/)
        foreach ($fuentes as $i => $f) {
            $colFuente[(int) $f->fuente_financiamiento_id] = self::COL_FUENTES_INICIO + $i * 3;
        }
        $colRendidoInicio = $nFuentes > 0 ? self::COL_FUENTES_INICIO + $nFuentes * 3 : 0; // TOTAL RENDIDO (S/, USD, EUR)
        $colFin = $nFuentes > 0 ? $colRendidoInicio + 2 : self::COL_TOT_EUR;

        // Geometría de filas.
        $filaLogo = $filaBase;
        $filaCabecera = $filaBase + 1;
        $filaSecciones = $filaBase + 2;
        $filaEncabezados = $filaBase + 3;
        $filaDatosInicio = $filaBase + 4;

        $this->encabezadoBloque($sheet, $bloque['programa'], $filaLogo, $filaCabecera, $filaSecciones, $filaEncabezados, $fuentes, $colFuente, $colRendidoInicio, $colFin, $color);

        // ---- Filas de datos: una por rubro; totales de actividad en su última fila ----
        $fila = $filaDatosInicio;
        $actividadActual = 0;
        $filaUltimaDeActividad = 0;
        $sumaActividad = 0.0;

        foreach ($filas as $dato) {
            if ($actividadActual !== 0 && (int) $dato->actividad_id !== $actividadActual) {
                // Cerrar la actividad anterior: totales G/H/I en su última fila.
                $this->totalesActividad($sheet, $filaUltimaDeActividad, $sumaActividad);
                $sumaActividad = 0.0;
            }
            $actividadActual = (int) $dato->actividad_id;
            $filaUltimaDeActividad = $fila;
            $sumaActividad += (float) $dato->monto;

            $sheet->setCellValue("A{$fila}", $dato->producto_codigo . ' ' . $dato->producto);
            $sheet->setCellValue("B{$fila}", $dato->actividad_codigo . ' ' . $dato->actividad);
            if ((int) $dato->id_tipo_rubro === 1) {
                $sheet->setCellValue("C{$fila}", $dato->rubros);
                $sheet->setCellValue("D{$fila}", $dato->monto);
            } else {
                $sheet->setCellValue("E{$fila}", $dato->rubros);
                $sheet->setCellValue("F{$fila}", $dato->monto);
            }

            // Rendiciones del RUBRO por fuente, alineadas 1:1 con su fila (migr. 034).
            $tieneRendicion = false;
            foreach ($rendiciones[(int) $dato->id] ?? [] as $ffId => $r) {
                if (!isset($colFuente[(int) $ffId])) {
                    continue; // fuente ya no vinculada al programa: sin columna
                }
                $c = $colFuente[(int) $ffId];
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($c) . $fila, $r->suma_monto_rendiciones);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($c + 1) . $fila, $r->suma_usd ?? '—');
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($c + 2) . $fila, $r->suma_eur ?? '—');
                $tieneRendicion = true;
            }
            // TOTAL RENDIDO de la fila: fórmula solo donde hay datos (sin ceros basura).
            if ($tieneRendicion && $nFuentes > 0) {
                for ($m = 0; $m < 3; $m++) { // 0=S/, 1=USD, 2=EUR
                    $partes = [];
                    foreach ($colFuente as $c) {
                        $partes[] = Coordinate::stringFromColumnIndex($c + $m) . $fila;
                    }
                    $sheet->setCellValue(
                        Coordinate::stringFromColumnIndex($colRendidoInicio + $m) . $fila,
                        '=' . implode('+', $partes)
                    );
                }
            }

            $sheet->getRowDimension($fila)->setRowHeight(53);
            $fila++;
        }
        // Totales de la última actividad del bloque.
        if ($actividadActual !== 0) {
            $this->totalesActividad($sheet, $filaUltimaDeActividad, $sumaActividad);
        }
        $filaDatosFin = $fila - 1;

        // ---- Fila de TRANSFERENCIA A PROGRAMA INSTITUCIONAL (migr. 033) ----
        if ($transferencia > 0) {
            $sheet->mergeCells("A{$fila}:F{$fila}");
            $sheet->setCellValue("A{$fila}", 'TRANSFERENCIA A PROGRAMA INSTITUCIONAL');
            $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue("G{$fila}", $transferencia);
            $usd = convertirMoneda($transferencia, $this->tasaVenta($this->tcDolar));
            $eur = convertirMoneda($transferencia, $this->tasaVenta($this->tcEuro));
            $sheet->setCellValue("H{$fila}", $usd ?? '—');
            $sheet->setCellValue("I{$fila}", $eur ?? '—');
            $fila++;
        }
        $filaTotal = $fila;

        // ---- Fila TOTAL (etiquetada) con sumas por columna ----
        $sheet->mergeCells("A{$filaTotal}:C{$filaTotal}");
        $sheet->setCellValue("A{$filaTotal}", 'TOTAL');
        $sheet->getStyle("A{$filaTotal}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("A{$filaTotal}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rangoFin = $filaTotal - 1; // incluye la fila de transferencia si existe
        $columnasSuma = [self::COL_BIEN_IMP, self::COL_SERV_IMP, self::COL_TOT_SOL, self::COL_TOT_USD, self::COL_TOT_EUR];
        foreach ($colFuente as $c) {
            array_push($columnasSuma, $c, $c + 1, $c + 2);
        }
        if ($nFuentes > 0) {
            array_push($columnasSuma, $colRendidoInicio, $colRendidoInicio + 1, $colRendidoInicio + 2);
        }
        foreach ($columnasSuma as $c) {
            $letra = Coordinate::stringFromColumnIndex($c);
            // Fórmula legítima: explícita, porque el binder del reporte trata todo "=..." como texto.
            $sheet->setCellValueExplicit("{$letra}{$filaTotal}", "=SUM({$letra}{$filaDatosInicio}:{$letra}{$rangoFin})", \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA);
            $sheet->getStyle("{$letra}{$filaDatosInicio}:{$letra}{$rangoFin}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("{$letra}{$filaTotal}")->getNumberFormat()->setFormatCode($this->formatoMoneda($c, $colFuente, $colRendidoInicio));
            $sheet->getStyle("{$letra}{$filaTotal}")->getFont()->setBold(true);
        }

        // Merges de etiquetas repetidas (SOLO A y B, acotado al bloque: nunca montos
        // ni entre bloques — corrige el merge accidental de montos iguales).
        foreach (['A', 'B'] as $letra) {
            $this->combinarRepetidos($sheet, $letra, $filaDatosInicio, $filaDatosFin);
        }
        $sheet->getStyle("A{$filaDatosInicio}:I{$filaDatosFin}")->getAlignment()->setWrapText(true);

        return $filaTotal + 2; // fila base del siguiente bloque (una fila en blanco)
    }

    /** Cabecera del bloque: logo, título coloreado, secciones y encabezados de columna. */
    private function encabezadoBloque(
        Worksheet $sheet,
        string $programa,
        int $filaLogo,
        int $filaCabecera,
        int $filaSecciones,
        int $filaEncabezados,
        array $fuentes,
        array $colFuente,
        int $colRendidoInicio,
        int $colFin,
        string $color
    ): void {
        // Logo + rótulo del proceso (diseño original).
        $logo = $_SERVER['DOCUMENT_ROOT'] . '/build/img/logo_last.png';
        if (is_file($logo)) {
            $drawing = new Drawing();
            $drawing->setPath($logo);
            $drawing->setCoordinates("A{$filaLogo}");
            $drawing->setWidth(40);
            $drawing->setHeight(40);
            $drawing->setWorksheet($sheet);
        }
        $sheet->getRowDimension($filaLogo)->setRowHeight(35);
        $sheet->mergeCells("B{$filaLogo}:C{$filaLogo}");
        $sheet->setCellValue("B{$filaLogo}", 'PROCESO DE PROYECTOS');

        // Cabecera coloreada del bloque.
        $letraFin = Coordinate::stringFromColumnIndex($colFin);
        $sheet->mergeCells("A{$filaCabecera}:{$letraFin}{$filaCabecera}");
        $sheet->setCellValue("A{$filaCabecera}", $this->titulo . ' - ' . date('Y') . ' - PROGRAMA ' . mb_strtoupper($programa, 'UTF-8'));
        $sheet->getStyle("A{$filaCabecera}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$filaCabecera}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$filaCabecera}:{$letraFin}{$filaCabecera}")->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle("A{$filaCabecera}:{$letraFin}{$filaCabecera}")->getFill()->getStartColor()->setRGB($color);

        // Secciones.
        $this->celdaTitulo($sheet, "C{$filaSecciones}", "D{$filaSecciones}", '1. BIENES');
        $this->celdaTitulo($sheet, "E{$filaSecciones}", "F{$filaSecciones}", '2. SERVICIOS');

        // Encabezados fijos A..I.
        $sheet->setCellValue("C{$filaEncabezados}", 'DETALLE');
        $sheet->setCellValue("D{$filaEncabezados}", 'Importe (moneda local)');
        $sheet->setCellValue("E{$filaEncabezados}", 'DETALLE');
        $sheet->setCellValue("F{$filaEncabezados}", 'Importe (moneda local)');
        $sheet->setCellValue("G{$filaEncabezados}", 'TOTAL (MONEDA LOCAL)');
        $sheet->setCellValue("H{$filaEncabezados}", 'TOTAL (DÓLARES)');
        $sheet->setCellValue("I{$filaEncabezados}", 'TOTAL (EUROS)');
        $sheet->getStyle("C{$filaEncabezados}:I{$filaEncabezados}")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("C{$filaEncabezados}:I{$filaEncabezados}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($filaEncabezados)->setRowHeight(52);

        if (empty($fuentes)) {
            return;
        }

        // Sección FUENTES DE FINANCIAMIENTO (K.. última fuente) + TOTAL RENDIDO.
        $letraIniF = Coordinate::stringFromColumnIndex(self::COL_FUENTES_INICIO);
        $letraFinF = Coordinate::stringFromColumnIndex($colRendidoInicio - 1);
        $this->celdaTitulo($sheet, "{$letraIniF}{$filaSecciones}", "{$letraFinF}{$filaSecciones}", 'FUENTES DE FINANCIAMIENTO');
        $letraIniR = Coordinate::stringFromColumnIndex($colRendidoInicio);
        $letraFinR = Coordinate::stringFromColumnIndex($colRendidoInicio + 2);
        $this->celdaTitulo($sheet, "{$letraIniR}{$filaSecciones}", "{$letraFinR}{$filaSecciones}", 'TOTAL RENDIDO');

        // Encabezado por fuente: nombre (S/), TOTAL DÓLARES, TOTAL EUROS.
        foreach ($fuentes as $f) {
            $c = $colFuente[(int) $f->fuente_financiamiento_id];
            $this->encabezadoColumna($sheet, $c, $filaEncabezados, $f->fuente_financiamiento_nombre);
            $this->encabezadoColumna($sheet, $c + 1, $filaEncabezados, 'TOTAL DÓLARES');
            $this->encabezadoColumna($sheet, $c + 2, $filaEncabezados, 'TOTAL EUROS');
        }
        $this->encabezadoColumna($sheet, $colRendidoInicio, $filaEncabezados, 'RENDIDO (S/)');
        $this->encabezadoColumna($sheet, $colRendidoInicio + 1, $filaEncabezados, 'RENDIDO (USD)');
        $this->encabezadoColumna($sheet, $colRendidoInicio + 2, $filaEncabezados, 'RENDIDO (EUR)');
    }

    /** Totales de la actividad (Σ rubros + conversiones al cierre) en G/H/I de su última fila. */
    private function totalesActividad(Worksheet $sheet, int $fila, float $suma): void
    {
        $usd = convertirMoneda($suma, $this->tasaVenta($this->tcDolar));
        $eur = convertirMoneda($suma, $this->tasaVenta($this->tcEuro));
        $sheet->setCellValue("G{$fila}", $suma);
        $sheet->setCellValue("H{$fila}", $usd ?? '—');
        $sheet->setCellValue("I{$fila}", $eur ?? '—');
    }

    /** Tasa de VENTA del TC (planificación, §2.5) — 0.0 sin cobertura (convertirMoneda → null). */
    private function tasaVenta(?object $tc): float
    {
        return ($tc && isset($tc->venta)) ? (float) $tc->venta : 0.0;
    }

    /** Título de sección combinado (p. ej. "1. BIENES" sobre C:D). */
    private function celdaTitulo(Worksheet $sheet, string $desde, string $hasta, string $texto): void
    {
        $sheet->mergeCells("{$desde}:{$hasta}");
        $sheet->setCellValue($desde, $texto);
        $sheet->getStyle($desde)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle($desde)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
    }

    /** Encabezado de una columna dinámica (fuentes / total rendido) con ancho fijo. */
    private function encabezadoColumna(Worksheet $sheet, int $col, int $fila, string $texto): void
    {
        $letra = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue("{$letra}{$fila}", $texto);
        $sheet->getStyle("{$letra}{$fila}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("{$letra}{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $sheet->getColumnDimension($letra)->setWidth(15);
    }

    /** Formato de moneda para la fila TOTAL según el tipo de columna. */
    private function formatoMoneda(int $col, array $colFuente, int $colRendidoInicio): string
    {
        // Posición dentro de una tripleta (S/=0, USD=1, EUR=2).
        $mod = null;
        foreach ($colFuente as $c) {
            if ($col >= $c && $col <= $c + 2) {
                $mod = $col - $c;
                break;
            }
        }
        if ($mod === null && $colRendidoInicio > 0 && $col >= $colRendidoInicio && $col <= $colRendidoInicio + 2) {
            $mod = $col - $colRendidoInicio;
        }
        if ($mod === null) {
            $mod = match ($col) {
                self::COL_TOT_USD => 1,
                self::COL_TOT_EUR => 2,
                default => 0, // D, F, G → soles
            };
        }
        return match ($mod) {
            1 => '"$ " #,##0.00',
            2 => '"€ " #,##0.00',
            default => '"S/. " #,##0.00',
        };
    }

    /** Combina celdas adyacentes con el mismo valor en UNA columna, dentro de un rango. */
    private function combinarRepetidos(Worksheet $sheet, string $letra, int $desde, int $hasta): void
    {
        $valorActual = null;
        $inicioRango = $desde;
        for ($i = $desde; $i <= $hasta + 1; $i++) {
            $valor = $i <= $hasta ? $sheet->getCell("{$letra}{$i}")->getValue() : null;
            if ($valor !== $valorActual || $i > $hasta) {
                if ($valorActual !== null && $valorActual !== '' && $i - 1 > $inicioRango) {
                    $sheet->mergeCells("{$letra}{$inicioRango}:{$letra}" . ($i - 1));
                }
                $valorActual = $valor;
                $inicioRango = $i;
            }
        }
        $sheet->getStyle("{$letra}{$desde}:{$letra}{$hasta}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }
}
