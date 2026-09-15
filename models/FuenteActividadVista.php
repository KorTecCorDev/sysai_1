<?php

namespace Model;

class FuenteActividadVista extends ActiveRecord
{
    // Declarando tablas y columnas
    protected static $tabla = 'fuente_por_actividad_vista';
    protected static $columnasDB = ['fuente_id', 'actividad_id', 'fuente_nombre', 'fuente_presupuesto'];

    //Declaramos las variables
    public $fuente_id;
    public $actividad_id;
    public $fuente_nombre;
    public $fuente_presupuesto;
}
