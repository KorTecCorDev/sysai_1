<?php

namespace Model;

class Usuario extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'usuario';
    protected static $columnasDB = ['id', 'persona_id', 'cargo_id', 'email', 'password', 'fecha', 'reset_token'];

    public $id;
    public $persona_id;
    public $cargo_id;
    public $email;
    public $password;
    public $fecha;
    public $reset_token;



    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->persona_id = $args['persona_id'] ?? '';
        $this->cargo_id = $args['cargo_id'] ?? '';
        $this->email = $args['email'] ?? '';
        //El password se genera solo al crear el usuario
        $this->password = $args['password'] ?? password_hash(generarCodigoAleatorioSimple(10), PASSWORD_DEFAULT);;
        $this->fecha = date('Y/m/d H:i:s');
        $this->reset_token = $args['reset_token'] ?? null;
    }

    public function validar()
    {
        // El código de usuario fue eliminado: se identifica por email + nombre.
        if (!$this->email) {
            self::$errores[] = 'Debes añadir el correo válido del usuario';
        }
        if (!$this->cargo_id) {
            self::$errores[] = 'Debes de seleccionar un cargo válido';
        }
        //Verificar si el email ya existe para otro usuario
        //Usamos la propiedad email porque así está definido en el FRONT
        if (self::existeDato($this,['email'])) {
            self::$errores[] = 'El correo ya está registrado para otro usuario';
        }
        return self::$errores;
    }

    public function comprobarCoordinador(): int /*retorna un entero entre 0 y 1 */
    {
        //Realiza la consulta, luego verifica si el usuario es coordinador retornando valores entre cero y uno
        $stmt = self::$db->prepare("SELECT COUNT(*) AS total FROM usuario WHERE id = ? AND cargo_id = 3");
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_object()->total ?? 0);
        $stmt->close();
        //Si el total es mayor a cero retornamos 1, de lo contrario cero
        return $total > 0 ? 1 : 0;
    }
    public function devolverIdLastInsercion()
    {
        $id = self::$db->insert_id;
        return $id;
    }
}
