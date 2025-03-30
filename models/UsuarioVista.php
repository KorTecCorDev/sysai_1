<?php

namespace Model;

class UsuarioVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'usuario_admin_vista';
    protected static $columnasDB = ['id', 'descripcion', 'datos', 'email', 'cargo', 'telefono'];

    public $id;
    public $descripcion;
    public $datos;
    public $email;
    public $cargo;
    public $telefono;
}
