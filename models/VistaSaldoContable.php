<?php

namespace Model;

class VistaSaldoContable extends ActiveRecord
{
    protected static $tabla = 'vista_saldo_contable';
    protected static $columnasDB = ['saldo_contable'];

    public $saldo_contable;

    public function __construct($args = [])
    {
        $this->saldo_contable = $args['saldo_contable'] ?? 0.00;
    }
}
