<?php

namespace Model;

class Rubro extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'rubro';
    protected static $columnasDB = ['id', 'actividad_id', 'categoria_rubro_id', 'tipo_rubro_id', 'codigo', 'nombre', 'descripcion', 'monto', 'fecha'];

    public $id;
    public $actividad_id;
    public $categoria_rubro_id;
    public $tipo_rubro_id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $monto;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->actividad_id = $args['actividad_id'] ?? null;
        $this->categoria_rubro_id = $args['categoria_rubro_id'] ?? null;
        $this->tipo_rubro_id = $args['tipo_rubro_id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->monto = $args['monto'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->actividad_id) {
            self::$errores[] = 'Debes seleccionar una actividad válida';
        }
        if (!$this->categoria_rubro_id) {
            self::$errores[] = 'Debes seleccionar una categoría de rubro válida';
        }
        if (!$this->tipo_rubro_id) {
            self::$errores[] = 'Debes seleccionar un tipo de rubro válido';
        }
        if (!$this->codigo) {
            self::$errores[] = 'Debes añadir un código válido para este rubro';
        }
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido para este rubro';
        }
        if (!$this->monto) {
            self::$errores[] = 'Debes ingresar un monto válido para este rubro';
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
