<?php

namespace Model;

class SaldoSobreVista extends ActiveRecord
{
    protected static $tabla = 'vista_saldo_sobre';
    protected static $columnasDB = [
        'sobre_id', 'programa_id', 'programa_codigo', 'programa_nombre',
        'fuente_financiamiento_id', 'fuente_codigo', 'fuente_nombre',
        'monto_asignado', 'ingresos', 'egresos', 'rendiciones_aprobadas', 'saldo'
    ];

    public $sobre_id;
    public $programa_id;
    public $programa_codigo;
    public $programa_nombre;
    public $fuente_financiamiento_id;
    public $fuente_codigo;
    public $fuente_nombre;
    public $monto_asignado;
    public $ingresos;
    public $egresos;
    public $rendiciones_aprobadas;
    public $saldo;

    public function __construct($args = [])
    {
        $this->sobre_id                 = $args['sobre_id'] ?? null;
        $this->programa_id              = $args['programa_id'] ?? null;
        $this->programa_codigo          = $args['programa_codigo'] ?? '';
        $this->programa_nombre          = $args['programa_nombre'] ?? '';
        $this->fuente_financiamiento_id = $args['fuente_financiamiento_id'] ?? null;
        $this->fuente_codigo            = $args['fuente_codigo'] ?? '';
        $this->fuente_nombre            = $args['fuente_nombre'] ?? '';
        $this->monto_asignado           = $args['monto_asignado'] ?? 0.0;
        $this->ingresos                 = $args['ingresos'] ?? 0.0;
        $this->egresos                  = $args['egresos'] ?? 0.0;
        $this->rendiciones_aprobadas    = $args['rendiciones_aprobadas'] ?? 0.0;
        $this->saldo                    = $args['saldo'] ?? 0.0;
    }
}
