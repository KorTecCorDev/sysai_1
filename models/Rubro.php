<?php

namespace Model;

class Rubro extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'rubro';
    protected static $columnasDB = ['id', 'actividad_id', 'categoria_rubro_id', 'tipo_rubro_id', 'codigo', 'nombre', 'descripcion', 'monto', 'fecha'];

    public $id;
    public $actividad_id;
    public $categoria_rubro_id;
    public $tipo_rubro_id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $monto;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->actividad_id = $args['actividad_id'] ?? null;
        $this->categoria_rubro_id = $args['categoria_rubro_id'] ?? null;
        $this->tipo_rubro_id = $args['tipo_rubro_id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->monto = $args['monto'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        // Normalización única de dinero (plan de montos, Fase 0). El rubro no tiene tope
        // de negocio propio desde la enmienda 2026-07-09 (no limita el gasto): solo tope
        // de cordura MONTO_MAXIMO.
        $this->monto = montoNumerico($this->monto);
        if (!$this->actividad_id) {
            self::$errores[] = 'Debes seleccionar una actividad válida';
        }
        if (!$this->categoria_rubro_id) {
            self::$errores[] = 'Debes seleccionar una categoría de rubro válida';
        }
        if (!$this->tipo_rubro_id) {
            self::$errores[] = 'Debes seleccionar un tipo de rubro válido';
        }
        // El código ya no lo ingresa el usuario: se autogenera (ver siguienteCodigo()).
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido para este rubro';
        }
        if ($this->monto === null || $this->monto <= 0) {
            self::$errores[] = 'Debes ingresar un monto válido para este rubro (solo números, mayor a 0)';
        } elseif ($this->monto > MONTO_MAXIMO) {
            self::$errores[] = 'El monto excede el tope permitido (S/. '
                . number_format(MONTO_MAXIMO, 2, '.', ',') . ')';
        }
        return self::$errores;
    }

    /** Código jerárquico autogenerado dentro de la actividad: "<cod_actividad>.<NN>" (p. ej. "1.1.1.01"). */
    public static function siguienteCodigo(int $actividadId): string
    {
        return static::siguienteCodigoJerarquico('actividad', $actividadId, 'actividad_id', 2);
    }

    /**
     * Saldos con signo de los rubros de una actividad (vista_saldo_rubro, migr. 027):
     * saldo = monto − Σ rendiciones (negativo = sobregasto; §2.3 del plan de montos).
     * Mapa rubro_id => saldo, leído con mysqli directo (columnas calculadas que
     * crearObjeto() descartaría).
     *
     * @return array<int, float>
     */
    public static function saldosPorActividad(int $actividadId): array
    {
        $map = [];
        $stmt = self::$db->prepare(
            "SELECT rubro_id, saldo FROM vista_saldo_rubro WHERE actividad_id = ?"
        );
        if ($stmt === false) {
            return $map;
        }
        $stmt->bind_param('i', $actividadId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && $row = $res->fetch_assoc()) {
            $map[(int) $row['rubro_id']] = (float) $row['saldo'];
        }
        $stmt->close();
        return $map;
    }

    public function agregarIdtoObjeto(int $id, string $key): object
    {
        $objeto = $this;
        $objeto->$key = $id;
        return $objeto;
    }
}
