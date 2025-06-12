<?php

namespace Model;

class VistaTotalEgresos extends ActiveRecord
{
    protected static $tabla = 'vista_total_egresos';
    protected static $columnasDB = ['total_egresos'];

    public $total_egresos;

    public function __construct($args = [])
    {
        $this->total_egresos = $args['total_egresos'] ?? 0.00;
    }
}
