<?php

namespace Model;

class SumaRendicionVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'total_monto_rendiciones_por_actividad';
    protected static $columnasDB = ['actividad_id', 'actividad_codigo', 'actividad_nombre', 'total_monto_rendiciones'];

    public $actividad_id;
    public $actividad_codigo;
    public $actividad_nombre;
    public $total_monto_rendiciones;
}
