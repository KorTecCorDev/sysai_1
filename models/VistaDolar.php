<?php

namespace Model;

class VistaDolar extends ActiveRecord
{
    protected static $tabla = 'vista_dolar';
    protected static $columnasDB = ['id','usuario', 'tipo_cambio', 'fecha'];

    public $id;
    public $usuario;
    public $tipo_cambio;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->usuario = $args['usuario'] ?? '';
        $this->tipo_cambio = $args['tipo_cambio'] ?? 0.0;
        $this->fecha = date('Y/m/d H:i:s');
    }
}
