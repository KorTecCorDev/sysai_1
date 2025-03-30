<?php

namespace Model;

use MVC\Router;


class OtrosIngresosEgresos extends ActiveRecord
{
    //Tabla y columnas
    protected static $tabla = 'otros_ingresos_egresos';
    protected static $columnasDB = ['id', 'poa_id', 'oie_comprobante_id', 'oie_tipo_id', 'ff_id', 'codigo', 'descripcion', 'fecha'];

    //Variables
    public $id;
    public $poa_id;
    public $oie_comprobante_id;
    public $oie_tipo_id;
    public $ff_id;
    public $codigo;
    public $descripcion;
    public $fecha;

    //Constructor
    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->poa_id = $args['poa_id'] ?? '';
        $this->oie_comprobante_id = $args['oie_comprobante_id'] ?? '';
        $this->oie_tipo_id = $args['oie_tipo_id'] ?? '';
        $this->ff_id = $args['ff_id'] ?? 0;
        $this->codigo = $args['codigo'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->fecha = date('Y-m-d H:i:s');
    }

    //Validamos
    public function validar()
    {
        if (!$this->poa_id) {
            self::$errores[] = 'Debes de seleccionar un Programa con POA válido';
        }
        if (!$this->oie_comprobante_id) {
            self::$errores[] = 'Debes de seleccionar un comprobante válido';
        }
        if (!$this->oie_tipo_id) {
            self::$errores[] = 'Debes de seleccionar un tipo de comprobante válido';
        }
        if (!$this->codigo) {
            self::$errores[] = 'Debes de ingresar un código válido';
        }
        if (!$this->descripcion) {
            self::$errores[] = 'Debes de ingresar una descripción válida';
        }
        return self::$errores;
    }

    public function validarconFf()
    {
        if (!$this->poa_id) {
            self::$errores[] = 'Debes de seleccionar un Programa con POA válido';
        }
        if (!$this->oie_comprobante_id) {
            self::$errores[] = 'Debes de seleccionar un comprobante válido';
        }
        if (!$this->oie_tipo_id) {
            self::$errores[] = 'Debes de seleccionar un tipo de comprobante válido';
        }
        if (!$this->codigo) {
            self::$errores[] = 'Debes de ingresar un código válido';
        }
        if (!$this->descripcion) {
            self::$errores[] = 'Debes de ingresar una descripción válida';
        }
        if (!$this->ff_id) {
            self::$errores[] = 'Debes de seleccionar una fuente de financiamietno válida';
        }
        return self::$errores;
    }
}
