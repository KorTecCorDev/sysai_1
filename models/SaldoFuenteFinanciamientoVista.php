<?php

namespace Model;

class SaldoFuenteFinanciamientoVista extends ActiveRecord
{
    protected static $tabla = 'vista_saldo_fuente_financiamiento';
    protected static $columnasDB = ['fuente_financiamiento_id', 'fuente_financiamiento_codigo', 'fuente_financiamiento_nombre', 'presupuesto_inicial', 'presupuesto_vigente', 'capacidad_asignable', 'fuente_financiamiento_saldo'];

    public $fuente_financiamiento_id;
    public $fuente_financiamiento_codigo;
    public $fuente_financiamiento_nombre;
    // Las tres cifras diferenciadas (migr. 027, plan de montos §2.4):
    public $presupuesto_inicial;   // compromiso original (inmutable)
    public $presupuesto_vigente;   // inicial + TODOS los ingresos OIE
    public $capacidad_asignable;   // inicial + ingresos sin programa − Σ sobres
    public $fuente_financiamiento_saldo;

    public function __construct($args = [])
    {
        $this->fuente_financiamiento_id = $args['fuente_financiamiento_id'] ?? null;
        $this->fuente_financiamiento_codigo = $args['fuente_financiamiento_codigo'] ?? '';
        $this->fuente_financiamiento_nombre = $args['fuente_financiamiento_nombre'] ?? '';
        $this->presupuesto_inicial = $args['presupuesto_inicial'] ?? 0.0;
        $this->presupuesto_vigente = $args['presupuesto_vigente'] ?? 0.0;
        $this->capacidad_asignable = $args['capacidad_asignable'] ?? 0.0;
        $this->fuente_financiamiento_saldo = $args['fuente_financiamiento_saldo'] ?? 0.0;
    }

    /**
     * Desglose de los componentes que forman el saldo de cada fuente, mapeado
     * por id de fuente: presupuesto inicial, ingresos (OIE), otros egresos (OIE)
     * y rendiciones aprobadas. Replica la misma fórmula que la vista
     * `vista_saldo_fuente_financiamiento` (saldo = presupuesto + ingresos
     * − egresos − rendiciones_aprobadas), para alimentar la vista sin tocar el
     * esquema SQL. Se usa conexión directa (mysqli) porque son columnas
     * calculadas que crearObjeto() descartaría.
     *
     * Incluye `comprometido` = Σ monto_asignado de los sobres de la fuente (el
     * presupuesto_comprometido, ahora definido como la suma de las reservas por
     * programa tras la migración 020) y, desde la migr. 027 (plan de montos §2.4):
     *   vigente             = presupuesto + TODOS los ingresos
     *   ingresos_libres     = ingresos con programa_id NULL (van al total de la fuente)
     *   capacidad_asignable = presupuesto + ingresos_libres − comprometido
     * Los ingresos dirigidos a un sobre NO amplían la capacidad asignable (ya están
     * asignados: son la asignación) — evita contar el mismo dinero dos veces.
     *
     * @return array<int, array{presupuesto:float, ingresos:float, ingresos_libres:float, egresos:float, rendiciones:float, comprometido:float, vigente:float, capacidad_asignable:float}>
     */
    public static function desglosePorFuente(): array
    {
        $sql = "SELECT ff.id AS fuente_id,
                       ff.presupuesto AS presupuesto,
                       COALESCE((SELECT SUM(oc.monto)
                                 FROM otros_ingresos_egresos oie
                                 JOIN oie_comprobante oc ON oc.id = oie.oie_comprobante_id
                                 WHERE oie.ff_id = ff.id AND oie.oie_tipo_id = 1), 0) AS ingresos,
                       COALESCE((SELECT SUM(oc.monto)
                                 FROM otros_ingresos_egresos oie
                                 JOIN oie_comprobante oc ON oc.id = oie.oie_comprobante_id
                                 WHERE oie.ff_id = ff.id AND oie.oie_tipo_id = 1
                                   AND oie.programa_id IS NULL), 0) AS ingresos_libres,
                       COALESCE((SELECT SUM(oc.monto)
                                 FROM otros_ingresos_egresos oie
                                 JOIN oie_comprobante oc ON oc.id = oie.oie_comprobante_id
                                 WHERE oie.ff_id = ff.id AND oie.oie_tipo_id = 2), 0) AS egresos,
                       COALESCE((SELECT SUM(r.monto)
                                 FROM rendicion r
                                 WHERE r.ff_id = ff.id AND r.estado = 1), 0) AS rendiciones,
                       COALESCE((SELECT SUM(df.monto_asignado)
                                 FROM detalle_financiamiento df
                                 WHERE df.fuente_financiamiento_id = ff.id), 0) AS comprometido
                FROM fuente_financiamiento ff";

        $resultado = self::$db->query($sql);
        if ($resultado === false) {
            error_log('desglosePorFuente falló: ' . self::$db->error);
            return [];
        }

        $map = [];
        while ($row = $resultado->fetch_assoc()) {
            $map[(int) $row['fuente_id']] = [
                'presupuesto'         => (float) $row['presupuesto'],
                'ingresos'            => (float) $row['ingresos'],
                'ingresos_libres'     => (float) $row['ingresos_libres'],
                'egresos'             => (float) $row['egresos'],
                'rendiciones'         => (float) $row['rendiciones'],
                'comprometido'        => (float) $row['comprometido'], // Σ sobres asignados de la fuente
                'vigente'             => (float) $row['presupuesto'] + (float) $row['ingresos'],
                'capacidad_asignable' => (float) $row['presupuesto'] + (float) $row['ingresos_libres'] - (float) $row['comprometido'],
            ];
        }
        $resultado->free();
        return $map;
    }
}
