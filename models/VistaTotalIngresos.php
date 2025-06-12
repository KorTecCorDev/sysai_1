<?php

namespace Model;

class VistaTotalIngresos extends ActiveRecord
{
    protected static $tabla = 'vista_total_ingresos';
    protected static $columnasDB = ['total_ingresos'];

    public $total_ingresos;

    public function __construct($args = [])
    {
        $this->total_ingresos = $args['total_ingresos'] ?? 0.00;
    }
}
