<?php

namespace Model;

class RendicionFfActividadVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'vista_fuentes_financiamiento_por_actividad';
    protected static $columnasDB = ['actividad_id', 'actividad_codigo', 'actividad_nombre', 'fuente_financiamiento_id', 'fuente_financiamiento_codigo', 'fuente_financiamiento_nombre', 'presupuesto_fuente_financiamiento', 'programa_codigo', 'programa_nombre'];

    public $actividad_id;
    public $actividad_codigo;
    public $actividad_nombre;
    public $fuente_financiamiento_id;
    public $fuente_financiamiento_codigo;
    public $fuente_financiamiento_nombre;
    public $presupuesto_fuente_financiamiento;
    public $programa_codigo;
    public $programa_nombre;
    
}
