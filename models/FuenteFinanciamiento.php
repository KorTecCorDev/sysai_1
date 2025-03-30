<?php

namespace Model;

class FuenteFinanciamiento extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'fuente_financiamiento';
    protected static $columnasDB = ['id', 'codigo', 'nombre', 'descripcion', 'presupuesto', 'fecha'];

    public $id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $presupuesto;
    public $fecha;


    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->presupuesto = $args['presupuesto'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->codigo) {
            self::$errores[] = 'Debes añadir un código válido';
        }
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido';
        }
        if (!$this->presupuesto) {
            self::$errores[] = 'Debes añadir un monto de presupuesto válido';
        }
        return self::$errores;
    }
}
