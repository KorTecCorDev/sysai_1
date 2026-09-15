<?php

namespace Model;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReportePoaRubros extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'reporte_poa_rubros';
    protected static $columnasDB = ['id_programa', 'programa', 'id', 'producto_codigo', 'producto', 'actividad_id', 'actividad_codigo', 'actividad', 'rubros', 'id_tipo_rubro', 'monto'];

    public $id_programa;
    public $programa;
    public $id;
    public $producto_codigo;
    public $producto;
    public $actividad_id;
    public $actividad_codigo;
    public $actividad;
    public $rubros;
    public $id_tipo_rubro;
    public $monto;
}
