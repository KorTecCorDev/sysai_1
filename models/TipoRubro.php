<?php

namespace Model;

class TipoRubro extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'tipo_rubro_vista';
    protected static $columnasDB = ['id', 'nombre'];

    public $id;
    public $nombre;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? null;
    }
}
