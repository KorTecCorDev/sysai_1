<?php

namespace Model;

class Login extends ActiveRecord
{
    //Base de datos
    protected static $tabla = 'login_session_vista';
    protected static $tbstring = "id, cargo_id, poa_id, email, password, reset_token, datos, cargo, programa_id";
    protected static $columnas = ['id', 'cargo_id', 'poa_id', 'email', 'password', 'reset_token', 'datos', 'cargo', 'programa_id', 'autenticado'];

    // Parámetros del rate-limit de login (bug C2). Se aplican por IP y por email.
    const RL_MAX_INTENTOS = 5;
    const RL_VENTANA = 300; // 5 minutos en segundos

    public $id;
    public $cargo_id;
    public $poa_id;
    public $email;
    public $password;
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
    // Búsqueda para el flujo de recuperación de contraseña. A diferencia de
    // existeUsuario(), NO agrega 'El usuario no existe' a $errores: así el
    // controlador puede responder de forma neutra y no permitir enumeración (A5).
    public function buscarPorEmailParaRecuperacion()
    {
        $query = "SELECT " . self::$tbstring . " FROM " . self::$tabla . " WHERE email = ? LIMIT 1";
        $resultado = self::consultarPreparado($query, 's', [$this->email]);
        return array_shift($resultado); // null si no existe (sin tocar $errores)
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

    // ------------------------------------------------------------------------
    // Rate-limit de login persistente en BD (bug C2). Cuenta intentos fallidos
    // por IP y por email en una ventana deslizante (RL_VENTANA). Complementa el
    // contador en $_SESSION, que es evadible si el atacante no envía cookies.
    // ------------------------------------------------------------------------

    // IP de origen de la petición. En hosting compartido sin CDN, REMOTE_ADDR es
    // la IP real del cliente. No se usa X-Forwarded-For por ser falsificable.
    public static function obtenerIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // ¿La IP o el email superaron el máximo de intentos fallidos en la ventana?
    // La ventana se evalúa con la hora de MySQL (NOW()) para no depender de que
    // el reloj/zona horaria de PHP coincida con el del servidor de BD.
    // RL_VENTANA es una constante entera del código → se interpola como int (no SQLi).
    public static function estaBloqueadoPorIntentos(string $ip, string $email): bool
    {
        $ventana = (int) self::RL_VENTANA;

        $porIp = self::consultarPreparado(
            "SELECT id FROM login_intentos WHERE ip = ? AND fecha > (NOW() - INTERVAL {$ventana} SECOND)",
            's',
            [$ip]
        );
        if (count($porIp) >= self::RL_MAX_INTENTOS) {
            return true;
        }

        if ($email !== '') {
            $porEmail = self::consultarPreparado(
                "SELECT id FROM login_intentos WHERE email = ? AND fecha > (NOW() - INTERVAL {$ventana} SECOND)",
                's',
                [$email]
            );
            if (count($porEmail) >= self::RL_MAX_INTENTOS) {
                return true;
            }
        }

        return false;
    }

    // Registra un intento fallido (IP + email intentado).
    public static function registrarIntentoFallido(string $ip, string $email): bool
    {
        return self::ejecutarPreparado(
            "INSERT INTO login_intentos (ip, email, fecha) VALUES (?, ?, NOW())",
            'ss',
            [$ip, $email]
        );
    }

    // Al autenticar con éxito se borran los intentos de esa IP y ese email.
    public static function limpiarIntentos(string $ip, string $email): bool
    {
        return self::ejecutarPreparado(
            "DELETE FROM login_intentos WHERE ip = ? OR email = ?",
            'ss',
            [$ip, $email]
        );
    }

    // Limpieza oportunista de registros más antiguos que la ventana.
    public static function purgarIntentosAntiguos(): bool
    {
        $ventana = (int) self::RL_VENTANA;
        return self::ejecutarPreparado(
            "DELETE FROM login_intentos WHERE fecha < (NOW() - INTERVAL {$ventana} SECOND)",
            '',
            []
        );
    }
}
