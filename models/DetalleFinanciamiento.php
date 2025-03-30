<?php

namespace Model;

class DetalleFinanciamiento extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'detalle_financiamiento';
    protected static $columnasDB = ['id', 'programa_id', 'fuente_financiamiento_id', 'fecha'];

    public $id;
    public $programa_id;
    public $fuente_financiamiento_id;
    public $fecha;


    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->programa_id = $args['programa_id'] ?? '';
        $this->fuente_financiamiento_id = $args['fuente_financiamiento_id'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->programa_id) {
            self::$errores[] = 'Debes seleccionar un programa válido';
        }
        if (!$this->fuente_financiamiento_id) {
            self::$errores[] = 'Debes seleccionar una fuente de financiamiento válida';
        }
        return self::$errores;
    }
}
