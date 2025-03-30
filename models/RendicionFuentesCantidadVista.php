<?php

namespace Model;

class RendicionFuentesCantidadVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'cantidad_fuentes_rendicion';
    protected static $columnasDB = ['numero'];

    public $numero;
}
