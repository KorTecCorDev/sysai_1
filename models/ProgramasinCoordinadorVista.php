<?php

namespace Model;

class ProgramasinCoordinadorVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'programas_sin_coordinador_vista';
    protected static $columnasDB = ['programa_id', 'programa_codigo', 'programa_nombre'];

    public $programa_id;
    public $programa_codigo;
    public $programa_nombre;
}
