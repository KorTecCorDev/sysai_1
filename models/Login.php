<?php

namespace Model;

class Login extends ActiveRecord
{
    //Base de datos
    protected static $tabla = 'login_session_vista';
    protected static $tbstring = "id, cargo_id, poa_id, email, password, reset_token, datos, cargo, programa_id";
    protected static $columnas = ['id', 'cargo_id', 'poa_id', 'email', 'password', 'intentos', 'estado', 'reset_token', 'datos', 'cargo', 'programa_id', 'autenticado'];
    //Contador de intentos para ingresar la contraseña en el login

    public $id;
    public $cargo_id;
    public $poa_id;
    public $email;
    public $password;
    public $intentos;
    public $estado;
    public $reset_token;
    public $datos;
    public $cargo;
    public $programa_id;
    public $autenticado = false;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->cargo_id = $args['cargo_id'] ?? null;
        $this->poa_id = $args['poa_id'] ?? null;
        $this->email = $args['email'] ?? '';
        $this->password = $args['password'] ?? '';
        $this->intentos = $args['intentos'] ?? 0;
        $this->estado = $args['estado'] ?? 0;
        $this->reset_token = $args['reset_token'] ?? null;
        $this->datos = $args['datos'] ?? '';
        $this->cargo = $args['cargo'] ?? '';
        $this->programa_id = $args['programa_id'] ?? null;
        $this->autenticado = $args['autenticado'] ?? false;
    }

    public function getSessionKey($type){
        return "login_{$type}";
    }

    public function validar()
    {
        if (!$this->email) {
            self::$errores[] = "El Email del usuario es obligatorio";
        }
        if (!$this->password) {
            self::$errores[] = "El Password del usuario es obligatorio";
        }
        if (strlen($this->password) < 6) {
            self::$errores[] = "El Password debe tener al menos 6 caracteres";
        }
        //Si el número de intentos es 3, entonces el usuario no podrá ingresar
        // if ($this->intentos = 3) {
        //     self::$errores[] = "Ha superado el número de intentos permitidos, por favor contacte con el administrador del sistema porfis";
        //     //Bloqueando al usuario
        //     $this->bloquearUsuario();
        // }
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
        // Consulta preparada: el email es entrada del usuario (evita SQLi pre-autenticación).
        // self::$tbstring y self::$tabla son constantes del modelo (no entrada del usuario).
        $query = "SELECT " . self::$tbstring . " FROM " . self::$tabla . " WHERE email = ? LIMIT 1";
        $resultado = self::consultarPreparado($query, 's', [$this->email]);
        if (empty($resultado)) {
            self::$errores[] = 'El usuario no existe';
            return;
        }
        return array_shift($resultado);
    }
    public function comprobarPassword($resultado)
    {
        //Le asignamos el estado de autenticado en caso el password sea correcto
        $this->autenticado = password_verify($this->password, $resultado->password);
        //Creamos una nueva propiedad en el objeto Login -> 'autenticado'
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
        $query = "SELECT " . self::$tbstring . " FROM usuario WHERE email = ?";
        return self::consultarPreparado($query, 's', [$email]);
    }

    public function guardarToken()
    {
        $query = "UPDATE usuario SET reset_token = ? WHERE id = ?";
        return self::ejecutarPreparado($query, 'si', [$this->reset_token, $this->id]);
    }

    public function validarToken($token)
    {
        $query = "SELECT " . self::$tbstring . " FROM usuario WHERE reset_token = ?";
        return self::consultarPreparado($query, 's', [$token]);
    }

    public function actualizarPassword($email, $password)
    {
        // El password recibido debe venir ya hasheado por quien llama.
        $query = "UPDATE usuario SET password = ?, reset_token = NULL WHERE email = ?";
        return self::ejecutarPreparado($query, 'ss', [$password, $email]);
    }

    public function generarCodigoAleatorioSimple($longitud = 8)
    {
        // Token criptográficamente seguro (reemplaza str_shuffle).
        $bytes = random_bytes((int) ceil($longitud / 2));
        return substr(bin2hex($bytes), 0, $longitud);
    }

    public function devolverPersona()
    {
        $query = "SELECT * FROM persona WHERE id = ?";
        $resultado = self::consultarPreparado($query, 'i', [$this->persona_id]);
        return array_shift($resultado);
    }

    public function findUserxEmail(): object
    {
        $query = "SELECT persona_id FROM usuario WHERE email = ?";
        $resultado = self::consultarPreparado($query, 's', [$this->email]);
        return array_shift($resultado);
    }

    public function tknvrfy()
    {
        $query = "SELECT id, email, password, reset_token, persona_id FROM usuario WHERE reset_token = ?";
        $resultado = self::consultarPreparado($query, 's', [$this->reset_token]);
        $obj = array_shift($resultado);
        if ($obj) {
            return $obj;
        }
        self::$errores[] = 'El código de verificación ingresado no es correcto';
    }

    public function updatePsswrdUser(string $newpssw): bool
    {
        // Al cambiar la contraseña invalidamos el token (un solo uso).
        $hash = password_hash($newpssw, PASSWORD_DEFAULT);
        $query = "UPDATE usuario SET password = ?, reset_token = NULL WHERE id = ?";
        return self::ejecutarPreparado($query, 'si', [$hash, $this->id]);
    }

    //Funciones para cambiar estados de los usuarios por intentos fallidos en el login
    public function restablecerIntentos()
    {
        $query = "UPDATE usuario SET intentos = 0 WHERE id = ?";
        return self::ejecutarPreparado($query, 'i', [$this->id]);
    }

    public function bloquearUsuario()
    {
        //Verificamos si el usuario existe
        $usu = $this->existeUsuario();
        //Si el usuario existe, entonces se puede bloquear
        if ($usu) {
            //El estado 1 significa que el usuario está bloqueado
            $query = "UPDATE usuario SET estado = 1 WHERE email = ?";
            return self::ejecutarPreparado($query, 's', [$this->email]);
        }
        //Si el usuario no existe, entonces no se puede bloquear
        self::$errores[] = 'El usuario no existe';
        return;
    }
    public function aumentarIntentos()
    {
        //Actualizamos el contador de intentos en la base de datos
        $query = "UPDATE usuario SET intentos = ? WHERE email = ?";
        return self::ejecutarPreparado($query, 'is', [$this->intentos, $this->email]);
    }
    public function actualizarIntentos()
    {
        //Consultamos a la base de datos el número de intentos del usuario según su email si exisitiera
        $query = "SELECT intentos FROM usuario WHERE email = ?";
        $resultado = self::consultarPreparado($query, 's', [$this->email]);
        $usuario = array_shift($resultado);
        if ($usuario) {
            //Si el usuario existe, entonces se puede actualizar el número de intentos
            $this->intentos = intval($usuario->intentos) + 1;
            return $this->aumentarIntentos();
        }
    }


    //Funciones para la validación de intentos
}
