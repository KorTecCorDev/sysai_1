<?php

namespace Model;

class RendicionAdminVista extends ActiveRecord
{
    // Declarando variables
    protected static $tabla = 'rendicion_admin';
    protected static $columnasDB = ['id', 'producto_id', 'actividad_id', 'codigo', 'tipo_comprobante', 'serie', 'numero', 'detalle', 'ruc', 'razon_social', 'monto', 'fuente_financiamiento_id', 'fuente_financiamiento', 'fecha_comprobante'];

    public $id;
    public $producto_id;
    public $actividad_id;
    public $codigo;
    public $tipo_comprobante;
    public $serie;
    public $numero;
    public $detalle;
    public $ruc;
    public $razon_social;
    public $monto;
    public $fuente_financiamiento_id;
    public $fuente_financiamiento;
    public $fecha_comprobante;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->producto_id = $args['producto_id'] ?? null;
        $this->actividad_id = $args['actividad_id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->tipo_comprobante = $args['tipo_comprobante'] ?? '';
        $this->serie = $args['serie'] ?? '';
        $this->numero = $args['numero'] ?? '';
        $this->detalle = $args['detalle'] ?? '';
        $this->ruc = $args['ruc'] ?? '';
        $this->razon_social = $args['razon_social'] ?? '';
        $this->monto = $args['monto'] ?? '';
        $this->fuente_financiamiento_id = $args['fuente_financiamiento_id'] ?? '';
        $this->fuente_financiamiento = $args['fuente_financiamiento'] ?? '';
        $this->fecha_comprobante = $args['fecha_comprobante'] ?? '';
    }
}
