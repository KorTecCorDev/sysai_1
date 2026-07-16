<?php

namespace Model;

class SumaRendicionVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'total_monto_rendiciones_por_actividad';
    protected static $columnasDB = ['actividad_id', 'actividad_codigo', 'actividad_nombre', 'total_monto_rendiciones', 'total_usd', 'total_eur', 'rendiciones_sin_tc'];

    public $actividad_id;
    public $actividad_codigo;
    public $actividad_nombre;
    public $total_monto_rendiciones;
    // Conversión con TC congelado por fila (migr. 030). NULL = sin cobertura ("—").
    public $total_usd;
    public $total_eur;
    public $rendiciones_sin_tc;
}
