<?php

namespace Model;

class Resultado extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'resultado';
    protected static $columnasDB = ['id', 'programa_id', 'codigo', 'nombre', 'descripcion', 'fecha'];


    public $id;
    public $programa_id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $fecha;




    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->programa_id = $args['programa_id'] ?? '';
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->programa_id) {
            self::$errores[] = 'Debe de seleccionar un programa válido';
        }
        if (!$this->codigo) {
            self::$errores[] = 'Debes ingresar un código válido';
        }
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido para el resultado';
        }
        return self::$errores;
    }
    public function agregarProvisional(string $cadena)
    {
        self::$aux[] = $cadena;
        return self::$aux;
    }
    public function quitarProvisional(int $pos)
    {
        array_splice(self::$aux, $pos, 1);
        return self::$aux;
    }
    public function agregarIdtoObjeto(int $id, string $key): object
    {
        $objeto = $this;
        $objeto->$key = $id;
        return $objeto;
    }
}
