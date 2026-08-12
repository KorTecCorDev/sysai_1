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

    // Parámetros de la recuperación de contraseña (bug A3).
    const RECUP_TOKEN_TTL    = 1800; // vigencia del código de recuperación: 30 min
    const RECUP_VENTANA      = 900;  // ventana del rate-limit de recuperación: 15 min
    const RECUP_MAX_IP       = 5;    // máx. solicitudes de token por IP en la ventana
    const RECUP_COOLDOWN     = 120;  // no reenviar token al mismo email antes de 2 min
    const RECUP_MAX_VERIFY   = 5;    // máx. verificaciones de código por IP en la ventana

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
    // Confirmación de la nueva contraseña (solo en el flujo de cambio; no se persiste).
    public $password_confirm = '';

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
        $this->password_confirm = $args['password_confirm'] ?? '';
    }

    // Longitud mínima de una contraseña nueva (política de fuerza del cambio).
    const PSSWD_MIN_LEN = 8;

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
            self::$errores[] = "Debe ingresar una nueva contraseña";
        } elseif (strlen($this->password) < self::PSSWD_MIN_LEN) {
            self::$errores[] = "La contraseña debe tener al menos " . self::PSSWD_MIN_LEN . " caracteres";
        }
        if ($this->password !== $this->password_confirm) {
            self::$errores[] = "Las contraseñas no coinciden";
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

    // Guarda el token (ya hasheado por quien llama) y fija su expiración.
    // RECUP_TOKEN_TTL es constante del código → se interpola como int (no SQLi).
    public function guardarToken()
    {
        $ttl = (int) self::RECUP_TOKEN_TTL;
        $query = "UPDATE usuario SET reset_token = ?, reset_token_expira = (NOW() + INTERVAL {$ttl} SECOND) WHERE id = ?";
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

    // ------------------------------------------------------------------------
    // Rate-limit de recuperación de contraseña (bug A3). Tabla recuperacion_intentos
    // con `tipo` = 'solicitud' (/chgpsswd) | 'verificacion' (/token_verify).
    // ------------------------------------------------------------------------

    // Registra un evento de recuperación (solicitud de token o verificación de código).
    public static function registrarIntentoRecuperacion(string $ip, ?string $email, string $tipo): bool
    {
        return self::ejecutarPreparado(
            "INSERT INTO recuperacion_intentos (ip, email, tipo, fecha) VALUES (?, ?, ?, NOW())",
            'sss',
            [$ip, $email, $tipo]
        );
    }

    // ¿La IP superó el máximo de SOLICITUDES de token en la ventana?
    public static function excedidoSolicitudesIp(string $ip): bool
    {
        $ventana = (int) self::RECUP_VENTANA;
        $filas = self::consultarPreparado(
            "SELECT id FROM recuperacion_intentos WHERE ip = ? AND tipo = 'solicitud' AND fecha > (NOW() - INTERVAL {$ventana} SECOND)",
            's',
            [$ip]
        );
        return count($filas) >= self::RECUP_MAX_IP;
    }

    // ¿Se solicitó un token para este email hace menos del cooldown? (anti-reenvío)
    public static function enCooldownReenvio(string $email): bool
    {
        if ($email === '') {
            return false;
        }
        $cooldown = (int) self::RECUP_COOLDOWN;
        $filas = self::consultarPreparado(
            "SELECT id FROM recuperacion_intentos WHERE email = ? AND tipo = 'solicitud' AND fecha > (NOW() - INTERVAL {$cooldown} SECOND)",
            's',
            [$email]
        );
        return count($filas) > 0;
    }

    // ¿La IP superó el máximo de VERIFICACIONES de código en la ventana? (anti-fuerza bruta)
    public static function excedidoVerificacionesIp(string $ip): bool
    {
        $ventana = (int) self::RECUP_VENTANA;
        $filas = self::consultarPreparado(
            "SELECT id FROM recuperacion_intentos WHERE ip = ? AND tipo = 'verificacion' AND fecha > (NOW() - INTERVAL {$ventana} SECOND)",
            's',
            [$ip]
        );
        return count($filas) >= self::RECUP_MAX_VERIFY;
    }

    // Limpieza oportunista de registros más antiguos que la ventana.
    public static function purgarIntentosRecuperacion(): bool
    {
        $ventana = (int) self::RECUP_VENTANA;
        return self::ejecutarPreparado(
            "DELETE FROM recuperacion_intentos WHERE fecha < (NOW() - INTERVAL {$ventana} SECOND)",
            '',
            []
        );
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
        // El usuario ingresa el código en claro; en BD se guarda su hash sha256.
        // Solo es válido si no ha expirado (reset_token_expira > NOW()).
        $hash = hash('sha256', (string) $this->reset_token);
        $query = "SELECT id, email, password, reset_token, persona_id FROM usuario WHERE reset_token = ? AND reset_token_expira > NOW()";
        $resultado = self::consultarPreparado($query, 's', [$hash]);
        $obj = array_shift($resultado);
        if ($obj) {
            return $obj;
        }
        self::$errores[] = 'El código de verificación ingresado no es correcto o ha expirado';
    }

    public function updatePsswrdUser(string $newpssw): bool
    {
        // Al cambiar la contraseña invalidamos el token y su expiración (un solo uso).
        $hash = password_hash($newpssw, PASSWORD_DEFAULT);
        $query = "UPDATE usuario SET password = ?, reset_token = NULL, reset_token_expira = NULL WHERE id = ?";
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
