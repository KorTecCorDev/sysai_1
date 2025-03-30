<?php

namespace Model;

class TipoPrograma extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'tipo_programa';
    protected static $columnasDB = ['id', 'descripcion', 'fecha'];

    public $id;
    public $descripcion;
    public $fecha;
}
