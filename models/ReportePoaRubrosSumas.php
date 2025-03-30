<?php

namespace Model;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReportePoaRubrosSumas extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'reporte_poa_rubros_sumas';
    protected static $columnasDB = ['id_actividad', 'actividad', 'suma_monto'];

    public $id_actividad;
    public $actividad;
    public $suma_monto;

}
