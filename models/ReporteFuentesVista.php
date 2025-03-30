<?php

namespace Model;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReporteFuentesVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'reporte_fuentes';
    protected static $columnasDB = ['fecha','codigo','descripcion','monto'];

    public $fecha;
    public $codigo;
    public $descripcion;
    public $monto;
}
