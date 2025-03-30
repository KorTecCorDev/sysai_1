<?php

namespace Model;

class IngresoEgresoAdminVista extends ActiveRecord
{
    // Declarando variables
    protected static $tabla = 'otros_ingresos_egresos_admin_vista';
    protected static $columnasDB = ['id','codigo','tipo','tipo_comprobante_codigo','comprobante_monto','comprobante_fecha'];

    public $id;
    public $codigo;
    public $tipo;
    public $tipo_comprobante_codigo;
    public $comprobante_monto;
    public $comprobante_fecha;
}
