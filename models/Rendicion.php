<?php

namespace Model;

class Rendicion extends ActiveRecord
{
    // Declarando variables
    protected static $tabla = 'rendicion';
    protected static $columnasDB = ['id', 'actividad_id', 'tipo_comprobante_id','ff_id', 'codigo', 'serie', 'numero', 'detalle', 'descripcion', 'ruc', 'razon_social', 'monto', 'fecha_original','fecha' ];

    public $id;
    public $actividad_id;
    public $tipo_comprobante_id;
    public $ff_id;
    public $codigo;
    public $serie;
    public $numero;
    public $detalle;
    public $descripcion;
    public $ruc;
    public $razon_social;
    public $monto;
    public $fecha_original;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->actividad_id = $args['actividad_id'] ?? null;
        $this->tipo_comprobante_id = $args['tipo_comprobante_id'] ?? null;
        $this->ff_id = $args['ff_id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->serie = $args['serie'] ?? '';
        $this->numero = $args['numero'] ?? '';
        $this->detalle = $args['detalle'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->ruc = $args['ruc'] ?? '';
        $this->razon_social = $args['razon_social'] ?? '';
        $this->monto = $args['monto'] ?? 0.0;
        $this->fecha_original = $args['fecha_original'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->actividad_id) {
            self::$errores[] = 'Debes de seleccionar una actividad válida';
        }
        if (!$this->tipo_comprobante_id) {
            self::$errores[] = 'Debes seleccionar un tipo de comprobante válido';
        }
        if (!$this->ff_id) {
            self::$errores[] = 'Debes seleccionar una fuente de financiamiento válida';
        }
        if (!$this->codigo) {
            self::$errores[] = 'Debes de ingresar un código válido';
        }
        if (!$this->serie) {
            self::$errores[] = 'Debes de ingresar una serie de comprobante válida';
        }
        if (!$this->numero) {
            self::$errores[] = 'Debes de ingresar un número de comprobante válido';
        }
        if (!$this->detalle) {
            self::$errores[] = 'Debes de ingresar un detalle de comprobante válido';
        }
        if (!$this->descripcion) {
            self::$errores[] = 'Debes de ingresar un comentario de comprobante válido';
        }
        if (!$this->monto) {
            self::$errores[] = 'Debes de ingresar un monto de comprobante válido';
        }
        if (!$this->fecha_original) {
            self::$errores[] = 'Debes de ingresar una fecha de emisión de comprobante válida';
        }
    }
}
