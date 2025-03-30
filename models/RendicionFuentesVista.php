<?php

namespace Model;

class RendicionFuentesVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'reporte_poa_rendicion';
    protected static $columnasDB = ['actividad_id', 'actividad_nombre', 'fuente_financiamiento_id', 'fuente_financiamiento_nombre' ,'suma_monto_rendiciones'];

    public $actividad_id;
    public $actividad_nombre;
    public $fuente_financiamiento_id;
    public $fuente_financiamiento_nombre;
    public $suma_monto_rendiciones;

}
