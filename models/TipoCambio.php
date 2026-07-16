<?php

namespace Model;

/**
 * Tipo de cambio unificado (migr. 028, plan de montos Fase 2). Reemplaza a
 * TipoCambioDolar, TipoCambioEuro, VistaDolar y VistaEuro: la moneda es un DATO.
 *
 * Reglas (confirmadas 2026-07-15):
 *  - El "vigente a una fecha" = registro con fecha_vigencia máxima ≤ esa fecha
 *    (convención contable para feriados/fines de semana). Nunca por id.
 *  - compra para ingresos (donativos), venta para gastos (rendiciones, rubros).
 *  - Las transacciones CONGELAN el valor (copian el número, no un FK): editar o
 *    borrar un TC después no reescribe la contabilidad ya registrada.
 */
class TipoCambio extends ActiveRecord
{
    protected static $tabla = 'tipo_cambio';
    protected static $columnasDB = ['id', 'moneda', 'fecha_vigencia', 'compra', 'venta', 'origen', 'usuario_id', 'fecha'];

    const MONEDAS = ['USD', 'EUR'];
    // Banda de cordura para una tasa PEN↔USD/EUR: fuera de esto es un dedazo.
    const TASA_MIN = 0.1;
    const TASA_MAX = 100.0;

    public $id;
    public $moneda;
    public $fecha_vigencia;
    public $compra;
    public $venta;
    public $origen;
    public $usuario_id;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->moneda = strtoupper(trim($args['moneda'] ?? ''));
        $this->fecha_vigencia = $args['fecha_vigencia'] ?? '';
        $this->compra = $args['compra'] ?? '';
        $this->venta = $args['venta'] ?? '';
        $this->origen = $args['origen'] ?? 'MANUAL';
        $this->usuario_id = $args['usuario_id'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        // Normalización única de números (plan de montos): texto inválido => null => error.
        $this->compra = montoNumerico($this->compra);
        $this->venta = montoNumerico($this->venta);

        if (!in_array($this->moneda, self::MONEDAS, true)) {
            self::$errores[] = 'Debes indicar una moneda válida (USD o EUR)';
        }
        if (!$this->usuario_id) {
            self::$errores[] = 'Debes de ingresar un usuario válido';
        }
        // fecha_vigencia: fecha real y no futura (una tasa no puede "regir" mañana).
        $ts = $this->fecha_vigencia ? strtotime((string) $this->fecha_vigencia) : false;
        if ($ts === false || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $this->fecha_vigencia)) {
            self::$errores[] = 'Debes ingresar la fecha de vigencia (AAAA-MM-DD)';
        } elseif ($ts > strtotime(date('Y-m-d'))) {
            self::$errores[] = 'La fecha de vigencia no puede ser futura';
        }
        foreach (['compra' => $this->compra, 'venta' => $this->venta] as $campo => $valor) {
            if ($valor === null || $valor <= 0) {
                self::$errores[] = "Debes ingresar un tipo de cambio de {$campo} válido (solo números, mayor a 0)";
            } elseif ($valor < self::TASA_MIN || $valor > self::TASA_MAX) {
                self::$errores[] = "El tipo de cambio de {$campo} está fuera del rango razonable ("
                    . self::TASA_MIN . ' – ' . self::TASA_MAX . ')';
            }
        }
        if ($this->compra !== null && $this->venta !== null && $this->venta < $this->compra) {
            self::$errores[] = 'El tipo de cambio de venta no puede ser menor al de compra';
        }
        // Unicidad (moneda, fecha_vigencia): pre-chequeo legible (la BD además tiene UNIQUE).
        if (empty(self::$errores) && $this->existeOtroParaFecha()) {
            self::$errores[] = 'Ya existe un tipo de cambio ' . $this->moneda
                . ' para el ' . $this->fecha_vigencia . '. Edita ese registro en vez de duplicarlo.';
        }
        return self::$errores;
    }

    /** ¿Hay OTRO registro (excluyéndose a sí mismo en edición) para (moneda, fecha_vigencia)? */
    private function existeOtroParaFecha(): bool
    {
        $sql = "SELECT id FROM " . static::$tabla . " WHERE moneda = ? AND fecha_vigencia = ?"
             . ($this->id ? " AND id <> ?" : "") . " LIMIT 1";
        $stmt = self::$db->prepare($sql);
        if ($stmt === false) {
            return false;
        }
        if ($this->id) {
            $id = (int) $this->id;
            $stmt->bind_param('ssi', $this->moneda, $this->fecha_vigencia, $id);
        } else {
            $stmt->bind_param('ss', $this->moneda, $this->fecha_vigencia);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $existe = $res && $res->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    /**
     * TC vigente de una moneda a una fecha: el registro con fecha_vigencia máxima ≤ la
     * fecha pedida. null si no hay cobertura (el llamador decide: "pendiente de TC",
     * nunca un fatal ni un cero).
     */
    public static function vigente(string $moneda, string $fecha): ?TipoCambio
    {
        $filas = self::consultarPreparado(
            "SELECT * FROM " . static::$tabla
            . " WHERE moneda = ? AND fecha_vigencia <= ? ORDER BY fecha_vigencia DESC LIMIT 1",
            'ss',
            [strtoupper($moneda), $fecha]
        );
        return $filas ? array_shift($filas) : null;
    }

    /** Listado de una moneda, más reciente primero (para el admin del módulo). */
    public static function porMoneda(string $moneda): array
    {
        return self::consultarPreparado(
            "SELECT * FROM " . static::$tabla
            . " WHERE moneda = ? ORDER BY fecha_vigencia DESC, id DESC",
            's',
            [strtoupper($moneda)]
        );
    }

    /**
     * Consulta la tasa SBS para una moneda y fecha (Fase 6 del plan de montos —
     * INFORMATIVO). Restricciones no negociables:
     *   - NUNCA se llama desde la ruta de un reporte (un reporte que depende de un
     *     HTTP externo es un reporte que se cuelga).
     *   - NUNCA guarda nada: solo devuelve datos para PRE-LLENAR el formulario o
     *     para el backfill que dispara el Contador.
     *   - Timeout corto y degradación limpia: sin servicio => null y se teclea.
     * El endpoint es configurable por .env (SBS_API_URL, con {moneda} y {fecha});
     * sin configurar => null (el hosting greenfield aún no tiene salida HTTP
     * verificada y el despliegue no debe depender de un tercero).
     *
     * @return array{compra:float, venta:float}|null
     */
    public static function consultarSbs(string $moneda, string $fecha): ?array
    {
        $plantilla = $_ENV['SBS_API_URL'] ?? getenv('SBS_API_URL') ?: '';
        if (!is_string($plantilla) || trim($plantilla) === '') {
            return null;   // servicio no configurado
        }
        $url = str_replace(
            ['{moneda}', '{fecha}'],
            [rawurlencode(strtoupper($moneda)), rawurlencode($fecha)],
            trim($plantilla)
        );
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 2,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            return null;
        }
        // Tolera los nombres de campo más comunes de las APIs de TC peruanas.
        $compra = montoNumerico($json['compra'] ?? $json['precioCompra'] ?? $json['buy'] ?? null);
        $venta  = montoNumerico($json['venta'] ?? $json['precioVenta'] ?? $json['sell'] ?? null);
        if ($compra === null || $venta === null || $compra <= 0 || $venta <= 0
            || $compra < self::TASA_MIN || $venta > self::TASA_MAX) {
            return null;
        }
        return ['compra' => $compra, 'venta' => $venta];
    }

    /**
     * De un conjunto de fechas de operación, cuáles NO tienen TC vigente que las cubra.
     * Devuelve las fechas faltantes (únicas, ordenadas). Se usa al aprobar el POA para
     * bloquear con el detalle de qué falta cargar.
     *
     * @param string[] $fechas
     * @return string[]
     */
    public static function coberturaFaltante(string $moneda, array $fechas): array
    {
        $faltantes = [];
        foreach (array_unique(array_filter($fechas)) as $fecha) {
            if (self::vigente($moneda, $fecha) === null) {
                $faltantes[] = $fecha;
            }
        }
        sort($faltantes);
        return $faltantes;
    }
}
