<?php

namespace Model;

class Cargo extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'cargo';
    protected static $columnasDB = ['id', 'descripcion'];

    public $id;
    public $descripcion;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->descripcion = $args['descripcion'] ?? '';
    }

    public function validar(){
        if (!$this->descripcion) {
            self::$errores[] = 'Debes añadir una descripción válida para el cargo';
        }
        return self::$errores;
    }
}
