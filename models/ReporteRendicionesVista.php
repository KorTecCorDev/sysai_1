<?php

namespace Model;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReporteRendicionesVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'reporte_rendiciones';
    protected static $columnasDB = ['rendicion_id', 'rendicion_fecha', 'rendicion_codigo', 'rendicion_descripcion', 'rendicion_tipo_comprobante_id', 'tipo_comprobante_codigo', 'rendicion_monto', 'fuente_financiamiento_id', 'fuente_financiamiento_codigo', 'rendicion_fecha_original', 'rendicion_ruc', 'rendicion_razon_social', 'rendicion_serie', 'rendicion_numero', 'rendicion_detalle', 'rendicion_comprobante_monto'];

    public $rendicion_id;
    public $rendicion_fecha;
    public $rendicion_codigo;
    public $rendicion_descripcion;
    public $rendicion_tipo_comprobante_id;
    public $tipo_comprobante_codigo;
    public $rendicion_monto;
    public $fuente_financiamiento_id;
    public $fuente_financiamiento_codigo;
    public $rendicion_fecha_original;
    public $rendicion_ruc;
    public $rendicion_razon_social;
    public $rendicion_serie;
    public $rendicion_numero;
    public $rendicion_detalle;
    public $rendicion_comprobante_monto;
}
