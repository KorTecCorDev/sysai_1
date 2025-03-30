<?php

namespace Model;

class CategoriaRubro extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'categoria_rubro';
    protected static $columnasDB = ['id', 'subcategoria_rubro_id', 'nombre', 'descripcion', 'codigo', 'fecha'];

    public $id;
    public $nombre;
    public $descripcion;
    public $codigo;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->subcategoria_rubro_id = $args['subcategoria_rubro_id'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->codigo = $args['codigo'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->subcategoria_rubro_id) {
            self::$errores[] = 'Debes seleccionar una subcategoría válida';
        }
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido';
        }
        if (!$this->codigo) {
            self::$errores[] = 'Debes añadir un código válido';
        }
        return self::$errores;
    }
}
