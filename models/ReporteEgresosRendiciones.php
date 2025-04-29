<?php

namespace Model;

class ReporteEgresosRendiciones extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'reporte_egresos_rendiciones_apci';
    protected static $columnasDB = ['rendicion_id', 'rendicion_fecha', 'rendicion_codigo', 'rendicion_descripcion' ,'tipo_comprobante_id','tipo_comprobante_codigo','actividad_id','actividad_codigo','rendicion_ff_id','rendicion_fecha_original','rendicion_ruc','rendicion_razon_social','rendicion_serie','rendicion_numero','rendicion_detalle','rendicion_monto'];

    public $rendicion_id;
    public $rendicion_fecha;//
    public $rendicion_codigo;//
    public $rendicion_descripcion;//
    public $tipo_comprobante_id;
    public $tipo_comprobante_codigo;//
    public $actividad_id;//
    public $actividad_codigo;
    public $rendicion_ff_id;//
    public $rendicion_fecha_original;//
    public $rendicion_ruc;//
    public $rendicion_razon_social;//
    public $rendicion_serie;//
    public $rendicion_numero;//
    public $rendicion_detalle;//
    public $rendicion_monto;//

    

}
