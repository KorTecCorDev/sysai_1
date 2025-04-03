<?php

namespace Model;

class SubCategoriaRubro extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'subcategoria_rubro';
    protected static $columnasDB = ['id', 'codigo', 'nombre', 'descripcion', 'fecha'];

    public $id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $fecha;


    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
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
        
        return self::$errores;
    }
}
