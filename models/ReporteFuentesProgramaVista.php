<?php

namespace Model;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReporteFuentesProgramaVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'reporte_fuentes_programa_rendicion';
    protected static $columnasDB = ['programa_id', 'fuente_financiamiento_id', 'fuente_financiamiento_nombre'];

    public $programa_id;
    public $fuente_financiamiento_id;
    public $fuente_financiamiento_nombre;
}
