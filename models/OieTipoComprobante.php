<?php

namespace Model;

class OieTipoComprobante extends ActiveRecord
{
    // Declarando variables
    protected static $tabla = 'oie_tipo_comprobante';
    protected static $columnasDB = ['id','codigo','nombre'];

    public $id;
    public $codigo;
    public $nombre;

    //Constructor
    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
    }

    //Validamos
    public function validar()
    {
        if (!$this->codigo) {
            self::$errores[] = "Debes añadir un código válido";
        }
        if (!$this->nombre) {
            self::$errores[] = "Debes añadir un nombre válido";
        }
        return self::$errores;
    }
}
