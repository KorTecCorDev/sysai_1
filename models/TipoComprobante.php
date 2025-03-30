<?php

namespace Model;

class TipoComprobante extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'tipo_comprobante';
    protected static $columnasDB = ['id', 'codigo', 'descripcion'];

    public $id;
    public $codigo;
    public $descripcion;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
    }

    public function validar()
    {
        if (!$this->codigo) {
            self::$errores[] = 'Debes ingresar un código valido para el tipo de comprobante';
        }
        if (!$this->descripcion) {
            self::$errores[] = 'Debes ingresar un tipo de comprobante válido';
        }
        return self::$errores;
    }
}
