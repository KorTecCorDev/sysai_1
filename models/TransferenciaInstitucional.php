<?php

namespace Model;

/**
 * Transferencia al Programa Institucional (migr. 033, decisiones 2026-07-16).
 *
 * Al asignar el sobre de una fuente a un programa, el Contador puede destinar un
 * monto fijo al programa Institucional (gastos administrativos). Es una PARTICIÓN
 * en el origen: el monto transferido NO vive en el sobre del programa origen sino
 * en el sobre (Institucional, fuente), que se deriva como Σ transferencias de la
 * fuente (ver sincronizarSobreInstitucional()). Este registro es la trazabilidad:
 * alimenta la fila "TRANSFERENCIA A PROGRAMA INSTITUCIONAL" del reporte Excel del
 * programa origen. Un registro EDITABLE por par (fuente, origen) — UNIQUE uq_ff_origen.
 */
class TransferenciaInstitucional extends ActiveRecord
{
    protected static $tabla = 'transferencia_institucional';
    protected static $columnasDB = ['id', 'fuente_financiamiento_id', 'programa_origen_id', 'monto', 'fecha'];

    public $id;
    public $fuente_financiamiento_id;
    public $programa_origen_id;
    public $monto;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->fuente_financiamiento_id = $args['fuente_financiamiento_id'] ?? '';
        $this->programa_origen_id = $args['programa_origen_id'] ?? '';
        $this->monto = $args['monto'] ?? 0;
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        // Normalización única de dinero (plan de montos, Fase 0).
        $this->monto = montoNumerico($this->monto);
        if (!$this->fuente_financiamiento_id) {
            self::$errores[] = 'La transferencia requiere una fuente de financiamiento válida';
        }
        if (!$this->programa_origen_id) {
            self::$errores[] = 'La transferencia requiere un programa de origen válido';
        }
        if ($this->monto === null || $this->monto <= 0) {
            self::$errores[] = 'Debes ingresar un monto de transferencia válido (solo números, mayor a 0)';
        } elseif ($this->monto > MONTO_MAXIMO) {
            self::$errores[] = 'El monto de la transferencia excede el tope permitido (S/. '
                . number_format(MONTO_MAXIMO, 2, '.', ',') . ')';
        }
        return self::$errores;
    }

    /** La transferencia del par (fuente, programa origen), o null. */
    public static function porPar(int $ffId, int $programaOrigenId): ?TransferenciaInstitucional
    {
        $filas = self::consultarPreparado(
            "SELECT * FROM " . static::$tabla
            . " WHERE fuente_financiamiento_id = ? AND programa_origen_id = ? LIMIT 1",
            'ii',
            [$ffId, $programaOrigenId]
        );
        return $filas[0] ?? null;
    }

    /** Todas las transferencias de UN programa origen (mapa para las cards y reportes). */
    public static function porOrigen(int $programaOrigenId): array
    {
        return self::consultarPreparado(
            "SELECT * FROM " . static::$tabla . " WHERE programa_origen_id = ? ORDER BY fuente_financiamiento_id",
            'i',
            [$programaOrigenId]
        );
    }

    /** Todas las transferencias de una fuente (para las cards de /dfinanciamiento). */
    public static function porFuente(int $ffId): array
    {
        return self::consultarPreparado(
            "SELECT * FROM " . static::$tabla . " WHERE fuente_financiamiento_id = ? ORDER BY programa_origen_id",
            'i',
            [$ffId]
        );
    }

    /** Σ transferido a la fuente (= monto que debe tener el sobre del Institucional). */
    public static function totalPorFuente(int $ffId): float
    {
        $total = 0.0;
        if ($stmt = self::$db->prepare(
            "SELECT COALESCE(SUM(monto), 0) AS total FROM " . static::$tabla
            . " WHERE fuente_financiamiento_id = ?"
        )) {
            $stmt->bind_param('i', $ffId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $total = (float) ($row['total'] ?? 0);
            $stmt->close();
        }
        return $total;
    }

    /**
     * Σ transferido POR un programa origen (todas sus fuentes) — la cifra de la fila
     * "TRANSFERENCIA A PROGRAMA INSTITUCIONAL" en su bloque del reporte Excel.
     */
    public static function totalPorOrigen(int $programaOrigenId): float
    {
        $total = 0.0;
        if ($stmt = self::$db->prepare(
            "SELECT COALESCE(SUM(monto), 0) AS total FROM " . static::$tabla
            . " WHERE programa_origen_id = ?"
        )) {
            $stmt->bind_param('i', $programaOrigenId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $total = (float) ($row['total'] ?? 0);
            $stmt->close();
        }
        return $total;
    }

    /**
     * Materializa el sobre del Institucional para una fuente: RECALCULA (no incrementa)
     * Σ transferencias y upserta detalle_financiamiento (Institucional, fuente).
     * Con Σ = 0 el sobre se elimina (el Institucional no recibe sobres directos, así
     * que sin transferencias no debe existir el vínculo). Llamar SIEMPRE tras crear,
     * editar o eliminar una transferencia de esa fuente.
     */
    public static function sincronizarSobreInstitucional(int $ffId): bool
    {
        $inst = Programa::institucional();
        if (!$inst) {
            self::$errores[] = 'No existe el programa Institucional (migr. 033 / seed). Contacte al administrador.';
            return false;
        }

        $total = self::totalPorFuente($ffId);
        $sobre = DetalleFinanciamiento::porPar((int) $inst->id, $ffId);

        if ($total <= 0) {
            // Sin transferencias → el sobre derivado desaparece.
            return $sobre ? $sobre->eliminarsinRedireccion() : true;
        }

        if ($sobre) {
            $sobre->monto_asignado = $total;
            return (bool) $sobre->actualizarsinRedireccion();
        }

        $nuevo = new DetalleFinanciamiento([
            'programa_id'              => $inst->id,
            'fuente_financiamiento_id' => $ffId,
            'monto_asignado'           => $total,
        ]);
        return $nuevo->crearsinRedireccion();
    }
}
