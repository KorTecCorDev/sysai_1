<?php

namespace Model;

class Poa extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'poa';
    protected static $columnasDB = ['id', 'programa_id', 'usuario_id', 'anio', 'presupuesto', 'estado', 'fecha', 'usuario_id'];

    public $id;
    public $programa_id;
    public $usuario_id;
    public $anio;
    public $presupuesto;
    public $estado;
    public $fecha;
    


    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->programa_id = $args['programa_id'] ?? '';
        $this->usuario_id = $args['usuario_id'] ?? '';
        $this->anio = $args['anio'] ?? date('Y');
        $this->presupuesto = $args['presupuesto'] ?? 0.00;
        $this->estado = $args['estado'] ?? 0;
        $this->fecha = date('Y/m/d H:i:s');
        $this->usuario_id = $args['usuario_id'] ?? null;
    }

    public function validar()
    {
        if (!$this->programa_id) {
            self::$errores[] = 'Debe de seleccionar un programa válido';
        }
        //Quitamos la validación de usurio_id, puesto que lo insertaremos en la misma VISTA
        // if (!$this->usuario_id) {
        //     self::$errores[] = 'Debes ingresar un usuario válido';
        // }
        // if (!$this->anio) {
        //     self::$errores[] = 'Debes ingresar un año válido';
        // }
        return self::$errores;
    }

    public function findProgramaxUsuario(int $usuario_id)
    {
        $query = "SELECT * FROM poa WHERE usuario_id = {$usuario_id}";
        $resultado = self::consultarSQL($query);
        return $resultado;
    }
}
