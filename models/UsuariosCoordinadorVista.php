<?php

namespace Model;

class UsuariosCoordinadorVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'usuarios_coordinador_vista';
    protected static $columnasDB = ['usuario_id', 'persona_id', 'cargo_id', 'usuario_codigo', 'nombres'];

    public $usuario_id;
    public $persona_id;
    public $cargo_id;
    public $usuario_codigo;
    public $nombres;
}
