<?php

namespace Model;

class FuentesPoaVista extends ActiveRecord
{
    // Declarando tablas y columnas
    protected static $tabla = 'fuentes_por_poa_id';
    protected static $columnasDB = ['poa_id','fuente_financiamiento_id', 'fuente_financiamiento_codigo', 'fuente_financiamiento_nombre'];

    //Declaramos las variables
    public $poa_id;
    public $fuente_financiamiento_id;
    public $fuente_financiamiento_codigo;
    public $fuente_financiamiento_nombre;

}