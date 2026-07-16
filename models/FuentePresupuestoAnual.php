<?php

namespace Model;

/**
 * Snapshot anual por fuente (item 8 — cierre anual, tabla de la migr. 008).
 *
 * La fila del año se escribe SOLO al registrar el cierre (botón del Contador),
 * copiando lo que las vistas calculan en vivo — nunca con acumuladores mutados
 * por operación (el antipatrón que el plan de montos §2.4 descartó: un
 * acumulador manual se desvía en silencio y no hay forma de detectarlo).
 *
 * Semántica de columnas (definición única tras la Fase A del plan 1):
 *   monto_inicial            = presupuesto de apertura de la fuente
 *   presupuesto_comprometido = Σ sobres (detalle_financiamiento.monto_asignado)
 *   presupuesto_contable     = inicial + ingresos − rendiciones aprobadas − otros egresos
 *                              (el saldo contable sobrante al momento del cierre)
 *
 * Decisiones confirmadas (2026-07-16): disparo manual del Contador; re-cerrable
 * con aviso (upsert por UNIQUE (fuente, anio)); las rendiciones pendientes solo
 * advierten, no bloquean. El ROLLOVER (traspaso al año siguiente) queda en v1.1.
 */
class FuentePresupuestoAnual extends ActiveRecord
{
    protected static $tabla = 'fuente_presupuesto_anual';
    protected static $columnasDB = ['id', 'fuente_financiamiento_id', 'anio', 'monto_inicial', 'presupuesto_comprometido', 'presupuesto_contable', 'fecha'];

    public $id;
    public $fuente_financiamiento_id;
    public $anio;
    public $monto_inicial;
    public $presupuesto_comprometido;
    public $presupuesto_contable;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->fuente_financiamiento_id = $args['fuente_financiamiento_id'] ?? null;
        $this->anio = $args['anio'] ?? date('Y');
        $this->monto_inicial = $args['monto_inicial'] ?? 0.0;
        $this->presupuesto_comprometido = $args['presupuesto_comprometido'] ?? 0.0;
        $this->presupuesto_contable = $args['presupuesto_contable'] ?? 0.0;
        $this->fecha = date('Y/m/d H:i:s');
    }

    /**
     * Registra (o re-registra) el cierre del año: un upsert por fuente con el
     * desglose EN VIVO de SaldoFuenteFinanciamientoVista::desglosePorFuente().
     * Devuelve el número de fuentes registradas. Idempotente por diseño: el
     * UNIQUE (fuente, anio) hace que re-cerrar sobrescriba el snapshot del año.
     */
    public static function cerrarAnio(string $anio): int
    {
        $desglose = SaldoFuenteFinanciamientoVista::desglosePorFuente();
        if (empty($desglose)) {
            return 0;
        }

        $stmt = self::$db->prepare(
            "INSERT INTO " . static::$tabla . "
                (fuente_financiamiento_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable, fecha)
             VALUES (?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                monto_inicial = VALUES(monto_inicial),
                presupuesto_comprometido = VALUES(presupuesto_comprometido),
                presupuesto_contable = VALUES(presupuesto_contable),
                fecha = NOW()"
        );
        if ($stmt === false) {
            return 0;
        }

        $registradas = 0;
        foreach ($desglose as $fuenteId => $d) {
            // contable = inicial + ingresos − otros egresos − rendiciones APROBADAS
            // (las pendientes no descuentan: el cierre advierte de ellas, no bloquea).
            $contable = $d['presupuesto'] + $d['ingresos'] - $d['egresos'] - $d['rendiciones'];
            $fid = (int) $fuenteId;
            $stmt->bind_param(
                'isddd',
                $fid,
                $anio,
                $d['presupuesto'],
                $d['comprometido'],
                $contable
            );
            if ($stmt->execute()) {
                $registradas++;
            }
        }
        $stmt->close();
        return $registradas;
    }

    /**
     * Fecha del cierre ya registrado para un año (null si aún no se cerró).
     * Alimenta el aviso de re-cierre del botón.
     */
    public static function fechaCierre(string $anio): ?string
    {
        $stmt = self::$db->prepare(
            "SELECT MAX(fecha) AS f FROM " . static::$tabla . " WHERE anio = ?"
        );
        if ($stmt === false) {
            return null;
        }
        $stmt->bind_param('s', $anio);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row['f'] ?? null;
    }

    /**
     * Histórico completo para la pantalla de saldos: una fila por (año, fuente)
     * con el nombre de la fuente, del año más reciente al más antiguo. Lectura
     * con mysqli directo (columnas de otra tabla que crearObjeto() descartaría).
     *
     * @return array<int, array{anio:string, fuente_codigo:string, fuente_nombre:string, monto_inicial:float, presupuesto_comprometido:float, presupuesto_contable:float, fecha:string}>
     */
    public static function historico(): array
    {
        $sql = "SELECT fpa.anio, ff.codigo AS fuente_codigo, ff.nombre AS fuente_nombre,
                       fpa.monto_inicial, fpa.presupuesto_comprometido, fpa.presupuesto_contable, fpa.fecha
                FROM " . static::$tabla . " fpa
                JOIN fuente_financiamiento ff ON ff.id = fpa.fuente_financiamiento_id
                ORDER BY fpa.anio DESC, ff.codigo";
        $resultado = self::$db->query($sql);
        if ($resultado === false) {
            error_log('FuentePresupuestoAnual::historico falló: ' . self::$db->error);
            return [];
        }
        $filas = [];
        while ($row = $resultado->fetch_assoc()) {
            $filas[] = [
                'anio'                     => (string) $row['anio'],
                'fuente_codigo'            => (string) $row['fuente_codigo'],
                'fuente_nombre'            => (string) $row['fuente_nombre'],
                'monto_inicial'            => (float) $row['monto_inicial'],
                'presupuesto_comprometido' => (float) $row['presupuesto_comprometido'],
                'presupuesto_contable'     => (float) $row['presupuesto_contable'],
                'fecha'                    => (string) $row['fecha'],
            ];
        }
        $resultado->free();
        return $filas;
    }
}
