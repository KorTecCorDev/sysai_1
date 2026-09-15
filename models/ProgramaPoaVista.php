<?php

namespace Model;

class ProgramaPoaVista extends ActiveRecord
{
    //Tablas y encabezados
    protected static $tabla = 'programa_poa_vista';
    protected static $columnasDB = ['poa_id','programa_id','programa_codigo','programa_nombre','poa_anio'];

    //Variables
    public $poa_id;
    public $programa_id;
    public $programa_codigo;
    public $programa_nombre;
    public $poa_anio;

}
