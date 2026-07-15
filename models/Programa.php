<?php

namespace Model;

class Programa extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'programa';
    protected static $columnasDB = ['id', 'nombre', 'codigo', 'descripcion', 'fecha','tipo_programa_id'];

    public $id;
    public $nombre;
    public $codigo;
    public $descripcion;
    public $fecha;
    public $tipo_programa_id;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->codigo = $args['codigo'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
        $this->tipo_programa_id = $args['tipo_programa_id'] ?? '';
    }

    public function validar()
    {
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido';
        }
        // El código ya no lo ingresa el usuario: se autogenera (ver siguienteCodigo()).
        if (!$this->tipo_programa_id) {
            self::$errores[] = 'Debes seleccionar un tipo de programa válido';
        }
        return self::$errores;
    }

    /**
     * Siguiente código correlativo autogenerado: PRG-001, PRG-002, ...
     * Estable con huecos: usa MAX(nº)+1 (borrar un programa no reutiliza el hueco).
     * El UNIQUE global de `codigo` es la red de seguridad ante colisiones.
     */
    public static function siguienteCodigo(): string
    {
        return static::siguienteCodigoCorrelativo('PRG');
    }
}
