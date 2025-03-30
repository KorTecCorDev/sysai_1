<?php

namespace Model;

class Producto extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'producto';
    protected static $columnasDB = ['id', 'resultado_id', 'codigo', 'nombre', 'descripcion', 'fecha'];

    public $id;
    public $resultado_id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $fecha;

    //Funciones 
    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->resultado_id = $args['resultado_id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->resultado_id) {
            self::$errores[] = 'Debe de seleccionar un resultado válido';
        }
        if (!$this->codigo) {
            self::$errores[] = 'Debes ingresar un código válido';
        }
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido para el resultado';
        }
        return self::$errores;
    }
    public function agregarIdtoObjeto(int $id, string $key): object
    {
        $objeto = $this;
        $objeto->$key = $id;
        return $objeto;
    }
}
