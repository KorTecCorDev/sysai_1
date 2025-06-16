<?php

namespace Model;

class SaldoFuenteFinanciamientoVista extends ActiveRecord
{
    protected static $tabla = 'vista_saldo_fuente_financiamiento';
    protected static $columnasDB = ['fuente_financiamiento_id', 'fuente_financiamiento_codigo', 'fuente_financiamiento_nombre', 'fuente_financiamiento_saldo'];

    public $fuente_financiamiento_id;
    public $fuente_financiamiento_codigo;
    public $fuente_financiamiento_nombre;
    public $fuente_financiamiento_saldo;

    public function __construct($args = [])
    {
        $this->fuente_financiamiento_id = $args['fuente_financiamiento_id'] ?? null;
        $this->fuente_financiamiento_codigo = $args['fuente_financiamiento_codigo'] ?? '';
        $this->fuente_financiamiento_nombre = $args['fuente_financiamiento_nombre'] ?? '';
        $this->fuente_financiamiento_saldo = $args['fuente_financiamiento_saldo'] ?? 0.0;;
    }
}
