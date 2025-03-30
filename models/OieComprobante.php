<?php

namespace Model;

class OieComprobante extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'oie_comprobante';
    protected static $columnasDB = ['id', 'oie_tipo_comprobante_id', 'serie', 'numero', 'descripcion', 'ruc', 'razon_social', 'monto', 'fecha_original', 'fecha'];

    public $id;
    public $oie_tipo_comprobante_id;
    public $serie;
    public $numero;
    public $descripcion;
    public $ruc;
    public $razon_social;
    public $monto;
    public $fecha_original;
    public $fecha;


    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->oie_tipo_comprobante_id = $args['oie_tipo_comprobante_id'] ?? '';
        $this->serie = $args['serie'] ?? '';
        $this->numero = $args['numero'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->ruc = $args['ruc'] ?? '';
        $this->razon_social = $args['razon_social'] ?? '';
        $this->monto = $args['monto'] ?? '';
        $this->fecha_original = $args['fecha_original'] ?? '';
        $this->fecha = date('Y-m-d H:i:s');
    }

    //Validamos
    public function validar()
    {
        if (!$this->oie_tipo_comprobante_id) {
            self::$errores[] = "Debe seleccionar un tipo de comprobante";
        }

        if (!$this->serie) {
            self::$errores[] = "Debe ingresar la serie del comprobante";
        }

        if (!$this->numero) {
            self::$errores[] = "Debe ingresar el número del comprobante";
        }

        if (!$this->ruc) {
            self::$errores[] = "Debe ingresar el RUC";
        }

        if (!$this->razon_social) {
            self::$errores[] = "Debe ingresar la razón social";
        }

        if (!$this->monto) {
            self::$errores[] = "Debe ingresar el monto";
        }

        if (!$this->fecha_original) {
            self::$errores[] = "Debe ingresar la fecha del comprobante";
        }

        return self::$errores;
    }
}
