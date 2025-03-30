<?php

namespace Model;

class Login extends ActiveRecord
{
    //Base de datos
    protected static $tabla = 'login_session_vista';
    protected static $tbstring = "id, cargo_id, poa_id, email, password, reset_token, datos, cargo, programa_id";
    protected static $columnas = ['id', 'cargo_id', 'poa_id', 'email', 'password', 'reset_token', 'datos', 'cargo', 'programa_id'];

    public $id;
    public $cargo_id;
    public $poa_id;
    public $email;
    public $password;
    public $reset_token;
    public $datos;
    public $cargo;
    public $programa_id;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->cargo_id = $args['cargo_id'] ?? null;
        $this->poa_id = $args['poa_id'] ?? null;
        $this->email = $args['email'] ?? '';
        $this->password = $args['password'] ?? '';
        $this->reset_token = $args['reset_token'] ?? null;
        $this->datos = $args['datos'] ?? '';
        $this->cargo = $args['cargo'] ?? '';
        $this->programa_id = $args['programa_id'] ?? null;
    }
    public function validar()
    {
        if (!$this->email) {
            self::$errores[] = "El Email del usuario es obligatorio";
        }
        if (!$this->password) {
            self::$errores[] = "El Password del usuario es obligatorio";
        }
        return self::$errores;
    }
    public function validarErroresCambioPswd()
    {
        if (!$this->email) {
            self::$errores[] = "Ingrese su correo electrónico válido";
        }
        return self::$errores;
    }
    public function validarErroresToken()
    {
        if (!$this->reset_token) {
            self::$errores[] = "Debe ingresar un Token válido";
        }
        return self::$errores;
    }
    public function validarUpdatePassword()
    {
        if (!$this->password) {
            self::$errores[] = "Debe ingresar una nueva contraseña válida";
        }
        return self::$errores;
    }
    public function existeUsuario()
    {
        //Revisar si existe el usuario
        $query = "SELECT " . self::$tbstring . " FROM " . self::$tabla . " WHERE email='" . $this->email . "' LIMIT 1";
        $resultado = self::$db->query($query);
        if (!$resultado->num_rows) {
            self::$errores[] = 'El usuario no existe';
            return;
        }
        //Devolviendo solo el objeto
        while ($registro = $resultado->fetch_assoc()) {
            $devolver = static::crearObjeto($registro);
        }
        return $devolver;
    }
    public function comprobarPassword($resultado)
    {

        // $usuario = $resultado->fetch_object();
        $this->autenticado = password_verify($this->password, $resultado->password);
        if (!$this->autenticado) {
            self::$errores[] = 'El Password es Incorrecto';
            return;
        }
    }
    public function autenticar()
{
    // Iniciar sesión si no está activa
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);

    // Establecer datos de sesión
    $_SESSION = [
        'id' => $this->id,
        'cargo_id' => $this->cargo_id,
        'email' => $this->email,
        'datos' => $this->datos,
        'cargo' => $this->cargo,
        'login' => true
    ];

    // Datos específicos para coordinadores
    if ($this->cargo_id == 3) {
        $_SESSION['poa_id'] = $this->poa_id ?? null;
        $_SESSION['programa_id'] = $this->programa_id ?? null;
    }
}
    //Funciones para cambiar el password mediante envío de email

    public function buscarporEmail($email)
    {
        $query = "SELECT " . self::$tbstring . " FROM usuario WHERE email='" . $email . "'";
        $resultado = self::consultarSql($query);
        return $resultado;
    }

    public function guardarToken()
    {
        $query = "UPDATE usuario SET reset_token = '" . $this->reset_token . "' WHERE id=" . $this->id;
        $resultado = self::$db->query($query);
        return $resultado;
    }

    public function validarToken($token)
    {
        $query = "SELECT " . self::$tbstring . " FROM usuario WHERE reset_token=" . $token;
        $resultado = self::$db->query($query);
        return $resultado;
    }

    public function actualizarPassword($email, $password)
    {
        $query = 'UPDATE usuario SET password =' . $password . ', reset_token = NULL WHERE email=' . $email;
        $resultado = self::$db->query($query);
        return $resultado;
    }

    public function generarCodigoAleatorioSimple($longitud = 8)
    {
        $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $codigo = substr(str_shuffle($caracteres), 0, $longitud);
        return $codigo;
    }

    public function devolverPersona()
    {
        $id = $this->persona_id;
        $query = "SELECT * FROM persona WHERE id='" . $id . "'";
        $resultado = $this->consultarSql($query);
        return array_shift($resultado);
    }

    public function findUserxEmail(): object
    {
        $query = "SELECT persona_id FROM usuario WHERE email='" . $this->email . "'";
        $resultado = $this->consultarSql($query);
        return array_shift($resultado);
    }

    public function tknvrfy()
    {
        $query = "SELECT id, email, password, reset_token, persona_id FROM usuario WHERE reset_token='" . $this->reset_token . "'";
        $resultado = $this->consultarSqldvolveruno($query);
        if ($resultado) {
            return $resultado;
        }
        self::$errores[] = 'El código de verificación ingresado no es correcto';
    }

    public function updatePsswrdUser(string $newpssw): bool
    {
        $query = "UPDATE usuario SET password='" . password_hash($newpssw, PASSWORD_DEFAULT) . "' where id = " . $this->id;
        $resultado = self::ejecutarSql($query);
        return $resultado;
    }
}
