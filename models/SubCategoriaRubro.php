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
        //validamos que el codigo sea único
        if ($this->existeDato($this, ['codigo'])) {
            self::$errores[] = 'El código ingresado ya existe para otra subcategoría de rubro';
        }
        
        return self::$errores;
    }
}
