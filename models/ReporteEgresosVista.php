<?php

namespace Model;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReporteEgresosVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'reporte_egresos';
    protected static $columnasDB = ['otros_ingresos_egresos_id', 'otros_ingresos_egresos_fecha', 'otros_ingresos_egresos_codigo', 'otros_ingresos_egresos_descripcion', 'otros_ingresos_egresos_oie_tipo_id', 'oie_tipo_comprobante_id', 'oie_tipo_comprobante_codigo', 'otros_ingresos_egresos_monto',  'fuente_financiamiento_id', 'fuente_financiamiento_codigo', 'oie_comprobante_fecha_original', 'oie_comprobante_ruc', 'oie_comprobante_razon_social', 'oie_comprobante_serie', 'oie_comprobante_numero', 'oie_comprobante_descripcion', 'oie_comprobante_monto'];

    public $otros_ingresos_egresos_id;
    public $otros_ingresos_egresos_fecha;
    public $otros_ingresos_egresos_codigo;
    public $otros_ingresos_egresos_descripcion;
    public $otros_ingresos_egresos_oie_tipo_id;
    public $oie_tipo_comprobante_id;
    public $oie_tipo_comprobante_codigo;
    public $otros_ingresos_egresos_monto;
    public $fuente_financiamiento_id;
    public $fuente_financiamiento_codigo;
    public $oie_comprobante_fecha_original;
    public $oie_comprobante_ruc;
    public $oie_comprobante_razon_social;
    public $oie_comprobante_serie;
    public $oie_comprobante_numero;
    public $oie_comprobante_descripcion;
    public $oie_comprobante_monto;
}
