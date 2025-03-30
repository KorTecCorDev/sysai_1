<?php

namespace Model;

class Usuario extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'usuario';
    protected static $columnasDB = ['id', 'persona_id', 'cargo_id', 'descripcion', 'email', 'password', 'fecha', 'reset_token'];

    public $id;
    public $persona_id;
    public $cargo_id;
    public $descripcion;
    public $email;
    public $password;
    public $fecha;
    public $reset_token;



    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->persona_id = $args['persona_id'] ?? '';
        $this->cargo_id = $args['cargo_id'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->email = $args['email'] ?? '';
        //El password se genera solo al crear el usuario
        $this->password = $args['password'] ?? password_hash(generarCodigoAleatorioSimple(10), PASSWORD_DEFAULT);;
        $this->fecha = date('Y/m/d H:i:s');
        $this->reset_token = $args['reset_token'] ?? null;
    }

    public function validar()
    {
        if (!$this->descripcion) {
            self::$errores[] = 'Debes añadir un código de usuario válido';
        }
        if (!$this->email) {
            self::$errores[] = 'Debes añadir el correo válido del usuario';
        }
        if (!$this->cargo_id) {
            self::$errores[] = 'Debes de seleccionar un cargo válido';
        }
        return self::$errores;
    }

    public function comprobarCoordinador(int $usuario_id): int /*retorna un entero entre 0 y 1 */
    {
        //Realiza la consulta, luego verifica si el usuario es coordinador retornando valores entre cero y uno
        $query = "SELECT COUNT(*) as total FROM usuario WHERE id = $usuario_id AND cargo_id = 3";
        $resultado = self::$db->query($query);
        $total = $resultado->fetch_object()->total;
        return $total;
    }
    public function devolverIdLastInsercion()
    {
        $id = self::$db->insert_id;
        return $id;
    }
}
