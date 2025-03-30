<?php

namespace Model;

class TipoCambioEuro extends ActiveRecord
{
    protected static $tabla = 'tipo_cambio_euro';
    protected static $columnasDB = ['id', 'usuario_id', 'tipo_cambio', 'fecha'];

    public $id;
    public $usuario_id;
    public $tipo_cambio;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->usuario_id = $args['usuario_id'] ?? '';
        $this->tipo_cambio = $args['tipo_cambio'] ?? 0.0;
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->usuario_id) {
            self::$errores[] = 'Debes de ingresar un usuario válido';
        }
        if (!$this->tipo_cambio) {
            self::$errores[] = 'Debes de ingresar un tipo de cambio válido';
        }
        return self::$errores;
    }
}