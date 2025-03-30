<?php

namespace Model;

class Persona extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'persona';
    protected static $columnasDB = ['id', 'nro_documento', 'apellido_paterno', 'apellido_materno', 'nombres', 'telefono', 'fecha'];

    public $id;
    public $nro_documento;
    public $apellido_paterno;
    public $apellido_materno;
    public $nombres;
    public $telefono;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nro_documento = $args['nro_documento'] ?? '';
        $this->apellido_paterno = $args['apellido_paterno'] ?? '';
        $this->apellido_materno = $args['apellido_materno'] ?? '';
        $this->nombres = $args['nombres'] ?? '';
        $this->telefono = $args['telefono'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->nro_documento) {
            self::$errores[] = 'Debes de ingresar un número de documento de identificación válido';
        }
        if (!$this->apellido_paterno) {
            self::$errores[] = 'Debes añadir un apellido paterno válido';
        }
        if (!$this->apellido_materno) {
            self::$errores[] = 'Debes añadir un apellido materno válido';
        }
        if (!$this->nombres) {
            self::$errores[] = 'Debes añadir un nombre válido';
        }
        if (!$this->telefono) {
            self::$errores[] = 'Debes añadir un número de contacto válido';
        }

        return self::$errores;
    }

    public function devolverIdLastInsercion(){
        $id = self::$db->insert_id;
        return $id;
    }
}
