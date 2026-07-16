<?php

namespace Model;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReporteEgresosVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'reporte_egresos';
    protected static $columnasDB = ['otros_ingresos_egresos_id', 'otros_ingresos_egresos_fecha', 'otros_ingresos_egresos_codigo', 'otros_ingresos_egresos_descripcion', 'oie_tipo_comprobante_id', 'oie_tipo_comprobante_codigo', 'otros_ingresos_egresos_oie_tipo_id',  'fuente_financiamiento_id', 'fuente_financiamiento_codigo', 'oie_comprobante_fecha_original', 'oie_comprobante_ruc', 'oie_comprobante_razon_social', 'oie_comprobante_serie', 'oie_comprobante_numero', 'oie_comprobante_descripcion', 'oie_comprobante_monto', 'oie_comprobante_monto_usd', 'oie_comprobante_monto_eur'];

    public $otros_ingresos_egresos_id;
    public $otros_ingresos_egresos_fecha;
    public $otros_ingresos_egresos_codigo;
    public $otros_ingresos_egresos_descripcion;
    public $oie_tipo_comprobante_id;
    public $oie_tipo_comprobante_codigo;
    public $otros_ingresos_oie_tipo_id;
    public $fuente_financiamiento_id;
    public $fuente_financiamiento_codigo;
    public $oie_comprobante_fecha_original;
    public $oie_comprobante_ruc;
    public $oie_comprobante_razon_social;
    public $oie_comprobante_serie;
    public $oie_comprobante_numero;
    public $oie_comprobante_descripcion;
    public $oie_comprobante_monto;
    // Conversión con TC congelado por fila (migr. 030). NULL = sin cobertura ("—").
    public $oie_comprobante_monto_usd;
    public $oie_comprobante_monto_eur;
}
