<?php

namespace Model;

class RendicionFf extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'rendicion_ff';
    protected static $columnasDB = ['id', 'actividad_id', 'ff_id', 'descripcion', 'monto', 'fecha'];

    public $id;
    public $actividad_id;
    public $ff_id;
    public $descripcion;
    public $monto;
    public $fecha;



    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->actividad_id = $args['actividad_id'] ?? null;
        $this->ff_id = $args['ff_id'] ?? null;
        $this->descripcion = $args['descripcion'] ?? '';
        $this->monto = $args['monto'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->actividad_id) {
            self::$errores[] = 'Debes de seleccionar una actividad válida';
        }
        if (!$this->ff_id) {
            self::$errores[] = 'Debe de seleccionar una fuente de financiamiento válida';
        }
        if (!$this->monto) {
            self::$errores[] = 'Debes de ingresar un monto válido';
        }
    }
}
