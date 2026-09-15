<?php

namespace Model;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReporteFuentesVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'reporte_fuentes';
    protected static $columnasDB = ['fuente_fecha', 'fuente_codigo', 'fuente_descripcion', 'fuente_monto'];

    public $fuente_fecha;
    public $fuente_codigo;
    public $fuente_descripcion;
    public $fuente_monto;
}
