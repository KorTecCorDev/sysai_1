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
    // Límites por IP holgados a propósito (2026-09-14): en producción toda la oficina sale
    // por UNA IP pública, y con 5 el sexto usuario que activaba su cuenta quedaba bloqueado.
    // Con 16^10 ≈ 10^12 códigos posibles, 30 intentos cada 15 min no hacen viable la fuerza bruta.
    const RECUP_MAX_IP       = 30;   // máx. solicitudes de token por IP en la ventana
    const RECUP_COOLDOWN     = 120;  // no reenviar token al mismo email antes de 2 min
    const RECUP_MAX_VERIFY   = 30;   // máx. verificaciones de código por IP en la ventana

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
        // Sin espacios alrededor: un correo pegado con un espacio no encontraba la cuenta.
        $this->email = trim((string) ($args['email'] ?? ''));
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
    // Límite de bcrypt (PASSWORD_DEFAULT): a partir de aquí trunca sin avisar.
    const PSSWD_MAX_BYTES = 72;

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
        // Se valida el FORMATO, no la existencia: el mensaje es el mismo para cualquiera
        // y no permite averiguar qué correos están registrados (A5).
        if (!$this->email || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
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
        } elseif (strlen($this->password) > self::PSSWD_MAX_BYTES) {
            // bcrypt ignora EN SILENCIO todo lo que pase de 72 bytes: dos claves distintas
            // con el mismo inicio serían la misma. Mejor decirlo que aceptarla a medias.
            self::$errores[] = "La contraseña es demasiado larga (máximo " . self::PSSWD_MAX_BYTES . " bytes)";
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
            'login' => true,
            // Huella de la contraseña ALMACENADA (el hash bcrypt, no la clave): si cambia,
            // sesionSigueValida() cierra esta sesión. Así un cambio de contraseña echa a
            // quien la estuviera usando en otro equipo (auditoría 2026-09-14, M3).
            'huella' => hash('sha256', (string) $this->password),
        ];
        // Datos específicos para coordinadores
        if ($this->cargo_id == 3) {
            $_SESSION['poa_id'] = $this->poa_id ?? null;
            $_SESSION['programa_id'] = $this->programa_id ?? null;
        }
    }

    /**
     * ¿La sesión autenticada sigue correspondiendo al usuario tal como está en la BD?
     *
     * Auditoría de seguridad 2026-09-14 (M3): el cargo y el programa se copiaban a la
     * sesión al entrar y nadie volvía a mirarlos. Un usuario eliminado, cambiado de
     * cargo o reasignado de programa seguía operando con lo de antes mientras no
     * cerrara sesión, y cambiar la contraseña no echaba a nadie. Router la llama en
     * cada petición autenticada: una lectura por clave primaria.
     *
     * No compara poa_id: cambia legítimamente cuando el coordinador crea su POA.
     * Las sesiones abiertas antes de existir la huella no la tienen: se cierran una vez.
     */
    public static function sesionSigueValida(): bool
    {
        $id = (int) ($_SESSION['id'] ?? 0);
        if ($id <= 0 || empty($_SESSION['huella'])) {
            return false;
        }
        $filas = self::consultarPreparado(
            "SELECT " . self::$tbstring . " FROM " . self::$tabla . " WHERE id = ? LIMIT 1",
            'i',
            [$id]
        );
        $fila = $filas[0] ?? null;
        if (!$fila) {
            return false;                                   // usuario eliminado
        }
        if ((int) $fila->cargo_id !== (int) ($_SESSION['cargo_id'] ?? 0)) {
            return false;                                   // cambió de cargo
        }
        if (!hash_equals((string) $_SESSION['huella'], hash('sha256', (string) $fila->password))) {
            return false;                                   // cambió la contraseña
        }
        if ((int) $fila->cargo_id === 3
            && (int) ($fila->programa_id ?? 0) !== (int) ($_SESSION['programa_id'] ?? 0)) {
            return false;                                   // coordinador reasignado
        }
        return true;
    }
    //Funciones para cambiar el password mediante envío de email

    // Guarda el token (ya hasheado por quien llama) y fija su expiración.
    // RECUP_TOKEN_TTL es constante del código → se interpola como int (no SQLi).
    public function guardarToken()
    {
        $ttl = (int) self::RECUP_TOKEN_TTL;
        $query = "UPDATE usuario SET reset_token = ?, reset_token_expira = (NOW() + INTERVAL {$ttl} SECOND) WHERE id = ?";
        return self::ejecutarPreparado($query, 'si', [$this->reset_token, $this->id]);
    }

    // Invalida el código en cuanto se verifica (2026-09-14). Antes seguía vigente hasta
    // cambiar la contraseña: el mismo código podía canjearse en otra sesión. Desde aquí,
    // la prueba de identidad vive solo en la sesión que lo verificó.
    public static function consumirToken(int $id): bool
    {
        return self::ejecutarPreparado(
            "UPDATE usuario SET reset_token = NULL, reset_token_expira = NULL WHERE id = ?",
            'i',
            [$id]
        );
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

    public function tknvrfy()
    {
        // El usuario ingresa el código en claro; en BD se guarda su hash sha256.
        // Solo es válido si no ha expirado (reset_token_expira > NOW()).
        // El código es hexadecimal en minúsculas: se normaliza lo tecleado porque el móvil
        // pone la primera letra en mayúscula y al pegar desde el correo se cuelan espacios,
        // y cualquiera de las dos cosas cambiaba el hash y rechazaba un código correcto.
        $codigo = strtolower(preg_replace('/\s+/', '', (string) $this->reset_token));
        $hash = hash('sha256', $codigo);
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
        if (!self::ejecutarPreparado($query, 'si', [$hash, $this->id])) {
            return false;
        }
        // Quien acaba de recuperar su cuenta no debe seguir bloqueado por los fallos que
        // lo llevaron a recuperarla (o por los que otro acumuló contra su correo).
        if ($this->email !== '') {
            self::ejecutarPreparado("DELETE FROM login_intentos WHERE email = ?", 's', [$this->email]);
        }
        return true;
    }

    // ------------------------------------------------------------------------
    // Rate-limit de login persistente en BD (bug C2). Cuenta intentos fallidos
    // por IP y por email en una ventana deslizante (RL_VENTANA). Complementa el
    // contador en $_SESSION, que es evadible si el atacante no envía cookies.
    // ------------------------------------------------------------------------

    // IP de origen de la petición. No se usa X-Forwarded-For por ser falsificable.
    // Verificado en producción (2026-09-15): aunque Hostinger pone su CDN (hcdn) delante
    // y no permite apagarlo, REMOTE_ADDR llega con la IP real del cliente (IPv6 incluida).
    // Si se cambia de hosting o de CDN, repetir la comprobación: pedir un código de
    // recuperación y comparar recuperacion_intentos.ip con la IP pública propia.
    public static function obtenerIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // ¿La IP o el email superaron el máximo de intentos fallidos en la ventana?
    // La ventana se evalúa con la hora de MySQL (NOW()) para no depender de que
    // el reloj/zona horaria de PHP coincida con el del servidor de BD.
    // RL_VENTANA es una constante entera del código → se interpola como int (no SQLi).
    // Tope de fallos por IP sola, contra el rociado de contraseñas (una IP que prueba
    // la misma clave contra muchos correos). Holgado a propósito: detrás de una misma
    // IP pública puede estar toda la oficina de la organización.
    const RL_MAX_POR_IP = 30;

    /**
     * ¿Se bloquea este intento de login?
     *
     * Auditoría de seguridad 2026-09-14 (M7): antes también se bloqueaba por EMAIL a
     * secas, así que cualquiera podía dejar fuera a otra persona durante 5 minutos
     * —indefinidamente, repitiendo— con 5 intentos fallidos contra su correo desde
     * su propia IP. Ahora el bloqueo fino es por la PAREJA (IP, email): quien falla
     * se bloquea a sí mismo, no al dueño de la cuenta. El límite por IP sola sigue
     * existiendo, con un tope mayor, para frenar el rociado de contraseñas.
     */
    public static function estaBloqueadoPorIntentos(string $ip, string $email): bool
    {
        $ventana = (int) self::RL_VENTANA;

        $porIp = self::consultarPreparado(
            "SELECT id FROM login_intentos WHERE ip = ? AND fecha > (NOW() - INTERVAL {$ventana} SECOND)",
            's',
            [$ip]
        );
        if (count($porIp) >= self::RL_MAX_POR_IP) {
            return true;
        }

        if ($email !== '') {
            $porPareja = self::consultarPreparado(
                "SELECT id FROM login_intentos WHERE ip = ? AND email = ? AND fecha > (NOW() - INTERVAL {$ventana} SECOND)",
                'ss',
                [$ip, $email]
            );
            if (count($porPareja) >= self::RL_MAX_INTENTOS) {
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

    // Al autenticar con éxito se borran los intentos de ESA pareja (IP, email). Antes
    // era `ip = ? OR email = ?`: con el bloqueo por pareja (M7), un login correcto
    // habría borrado también los fallos que otra IP acumulaba contra este correo, o
    // los de otros correos desde esta IP (y con ellos el rastro del rociado).
    public static function limpiarIntentos(string $ip, string $email): bool
    {
        return self::ejecutarPreparado(
            "DELETE FROM login_intentos WHERE ip = ? AND email = ?",
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
