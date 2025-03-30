<?php

namespace Model;

class UsuarioDisponiblePrograma extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'usuario_id_disponible_programa_vista';
    protected static $columnasDB = ['id', 'persona_id', 'cargo_id', 'codigo'];

    public $id;
    public $persona_id;
    public $cargo_id;
    public $codigo;
}
