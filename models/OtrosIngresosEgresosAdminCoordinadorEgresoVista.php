<?php

namespace Model;

class OtrosIngresosEgresosAdminCoordinadorEgresoVista extends ActiveRecord
{
    //Tablas y encabezados
    protected static $tabla = 'otros_ingresos_egresos_admin_coordinador_egreso_vista';
    protected static $columnasDB = ['id','poa_id','codigo','tipo','tipo_comprobante_codigo','comprobante_monto','comprobante_fecha'];

    //Variables
    public $id;
    public $poa_id;
    public $codigo;
    public $tipo;
    public $tipo_comprobante_codigo;
    public $comprobante_monto;
    public $comprobante_fecha;
}
