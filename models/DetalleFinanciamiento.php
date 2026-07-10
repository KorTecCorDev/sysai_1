<?php

namespace Model;

class DetalleFinanciamiento extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'detalle_financiamiento';
    protected static $columnasDB = ['id', 'programa_id', 'fuente_financiamiento_id', 'monto_asignado', 'fecha'];

    public $id;
    public $programa_id;
    public $fuente_financiamiento_id;
    public $monto_asignado;   // "sobre": monto de la fuente reservado para el programa
    public $fecha;


    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->programa_id = $args['programa_id'] ?? '';
        $this->fuente_financiamiento_id = $args['fuente_financiamiento_id'] ?? '';
        $this->monto_asignado = $args['monto_asignado'] ?? 0;
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->programa_id) {
            self::$errores[] = 'Debes seleccionar un programa válido';
        }
        if (!$this->fuente_financiamiento_id) {
            self::$errores[] = 'Debes seleccionar una fuente de financiamiento válida';
        }
        if (!$this->monto_asignado || (float) $this->monto_asignado <= 0) {
            self::$errores[] = 'Debes ingresar un monto a asignar (sobre) válido';
        }
        return self::$errores;
    }

    /**
     * Saldo del "sobre" (programa, fuente): la capacidad disponible para gasto.
     *   capacidad    = monto_asignado + Σ ingresos OIE dirigidos a este sobre
     *   comprometido = Σ rendiciones del sobre (todos los estados) + Σ egresos OIE del sobre
     *   disponible   = capacidad − comprometido
     * Las rendiciones se derivan por la cadena rubro → actividad → producto → resultado
     * → programa; la fuente por ff_id. Permite excluir una rendición (en edición) para
     * no contarla dos veces. Devuelve el desglose para construir mensajes claros.
     *
     * @return array{asignado:float, ingresos:float, egresos:float, rendiciones:float, disponible:float}
     */
    public static function saldoSobre(int $programaId, int $ffId, ?int $excluirRendicionId = null): array
    {
        // 1) Monto asignado al sobre (0 si el vínculo no existe).
        $asignado = 0.0;
        if ($stmt = self::$db->prepare(
            "SELECT COALESCE(monto_asignado, 0) AS m FROM " . static::$tabla
            . " WHERE programa_id = ? AND fuente_financiamiento_id = ? LIMIT 1"
        )) {
            $stmt->bind_param('ii', $programaId, $ffId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $asignado = (float) ($row['m'] ?? 0);
            $stmt->close();
        }

        // 2) Rendiciones imputadas al sobre (todos los estados comprometen el sobre).
        $rendiciones = 0.0;
        $sql = "SELECT COALESCE(SUM(r.monto), 0) AS total
                FROM rendicion r
                JOIN rubro ru ON ru.id = r.rubro_id
                JOIN actividad a ON a.id = ru.actividad_id
                JOIN producto p ON p.id = a.producto_id
                JOIN resultado re ON re.id = p.resultado_id
                WHERE re.programa_id = ? AND r.ff_id = ?"
             . ($excluirRendicionId ? " AND r.id <> ?" : "");
        if ($stmt = self::$db->prepare($sql)) {
            if ($excluirRendicionId) {
                $ex = (int) $excluirRendicionId;
                $stmt->bind_param('iii', $programaId, $ffId, $ex);
            } else {
                $stmt->bind_param('ii', $programaId, $ffId);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $rendiciones = (float) ($row['total'] ?? 0);
            $stmt->close();
        }

        // 3) OIE dirigidos a este sobre: ingresos (tipo 1) suman, egresos (tipo 2) restan.
        $ingresos = 0.0;
        $egresos  = 0.0;
        if ($stmt = self::$db->prepare(
            "SELECT oie.oie_tipo_id AS tipo, COALESCE(SUM(oc.monto), 0) AS total
             FROM otros_ingresos_egresos oie
             JOIN oie_comprobante oc ON oc.id = oie.oie_comprobante_id
             WHERE oie.programa_id = ? AND oie.ff_id = ?
             GROUP BY oie.oie_tipo_id"
        )) {
            $stmt->bind_param('ii', $programaId, $ffId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && $row = $res->fetch_assoc()) {
                if ((int) $row['tipo'] === 1) {
                    $ingresos = (float) $row['total'];
                } elseif ((int) $row['tipo'] === 2) {
                    $egresos = (float) $row['total'];
                }
            }
            $stmt->close();
        }

        $disponible = $asignado + $ingresos - $egresos - $rendiciones;
        return [
            'asignado'    => $asignado,
            'ingresos'    => $ingresos,
            'egresos'     => $egresos,
            'rendiciones' => $rendiciones,
            'disponible'  => $disponible,
        ];
    }

    /**
     * Valida que la suma de los sobres de una fuente (incluido el nuevo monto que se
     * intenta asignar) no exceda el presupuesto de la fuente: Σ monto_asignado ≤
     * fuente.presupuesto. Se usará al capturar el monto del sobre en el formulario del
     * vínculo (Fase UI). Agrega el error a self::$errores; devuelve true si está OK.
     */
    public static function validarLimiteAsignacion(int $ffId, float $montoAsignado, ?int $excluirId = null): bool
    {
        // Presupuesto de la fuente.
        $presupuesto = 0.0;
        if ($stmt = self::$db->prepare("SELECT presupuesto FROM fuente_financiamiento WHERE id = ? LIMIT 1")) {
            $stmt->bind_param('i', $ffId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $presupuesto = (float) ($row['presupuesto'] ?? 0);
            $stmt->close();
        }

        // Σ de los OTROS sobres de la fuente (excluyendo el vínculo en edición).
        $otros = 0.0;
        $sql = "SELECT COALESCE(SUM(monto_asignado), 0) AS total FROM " . static::$tabla
             . " WHERE fuente_financiamiento_id = ?" . ($excluirId ? " AND id <> ?" : "");
        if ($stmt = self::$db->prepare($sql)) {
            if ($excluirId) {
                $ex = (int) $excluirId;
                $stmt->bind_param('ii', $ffId, $ex);
            } else {
                $stmt->bind_param('i', $ffId);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $otros = (float) ($row['total'] ?? 0);
            $stmt->close();
        }

        if ($otros + $montoAsignado > $presupuesto + 0.001) {
            $disponible = max(0, $presupuesto - $otros);
            self::$errores[] = 'El monto asignado excede el presupuesto disponible de la fuente. '
                . 'Disponible para asignar: S/. ' . number_format($disponible, 2, '.', ',')
                . ' (presupuesto de la fuente S/. ' . number_format($presupuesto, 2, '.', ',') . ').';
            return false;
        }
        return true;
    }
}
