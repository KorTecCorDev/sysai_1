<?php

namespace Model;

class RendicionFuentesVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'reporte_poa_rendicion';
    protected static $columnasDB = ['actividad_id', 'actividad_nombre', 'fuente_financiamiento_id', 'fuente_financiamiento_nombre', 'suma_monto_rendiciones', 'suma_usd', 'suma_eur', 'rendiciones_sin_tc'];

    public $actividad_id;
    public $actividad_nombre;
    public $fuente_financiamiento_id;
    public $fuente_financiamiento_nombre;
    public $suma_monto_rendiciones;
    // Conversión con TC congelado por fila (migr. 030). NULL = sin cobertura ("—").
    // Puede ser PARCIAL si hay rendiciones sin TC: rendiciones_sin_tc las cuenta.
    public $suma_usd;
    public $suma_eur;
    public $rendiciones_sin_tc;

}
