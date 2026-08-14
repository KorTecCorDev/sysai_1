<?php

namespace Controllers;

require_once __DIR__ . '/../vendor/autoload.php';



use MVC\Router;
use Model\Login;
use Model\Persona;
use Model\Usuario;
use PHPMailer\PHPMailer\PHPMailer;
use Exception;

class LoginController
{

    

    public static function login(Router $router)
    {
        //Variables para la validación de intentos
        $maxAttempts = 5;
        $lockTime = 300; // 5 minutos
        //Creamos los objetos para renderizar sin POST
        $login = new Login();
        $errores = Login::getErrores();
        // Verificar si ya está autenticado primero
        if (isset($_SESSION['login'])) {
            header('Location: /');
            exit;
        }
        
        $attemptKey = $login->getSessionKey('attempts');
        $lockKey = $login->getSessionKey('lock');
        $ip = Login::obtenerIp();

        //Sección del POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Limpieza oportunista de intentos antiguos (mantiene chica la tabla).
            Login::purgarIntentosAntiguos();

            // 1) Capa 1 — bloqueo por sesión (rápido, pero evadible sin cookies).
            if (isset($_SESSION[$lockKey]) && $_SESSION[$lockKey] > time()) {
                $restante = (int) ceil(($_SESSION[$lockKey] - time()) / 60);
                $errores[] = "Demasiados intentos fallidos. Intente nuevamente en {$restante} minuto(s).";
                $router->renderssdbr('/login', ['errores' => $errores, 'login' => $login]);
                return;
            }

            $login = new Login($_POST);

            // 2) Capa 2 — bloqueo persistente en BD por IP y por email (no depende
            //    de la cookie de sesión). Cierra el bypass de la capa anterior.
            if (Login::estaBloqueadoPorIntentos($ip, $login->email)) {
                $errores[] = "Demasiados intentos fallidos. Intente nuevamente en unos minutos.";
                $router->renderssdbr('/login', ['errores' => $errores, 'login' => $login]);
                return;
            }

            $errores = $login->validar();
            // Si no existen errores de validación, procedemos a autenticar
            if (empty($errores)) {
                // ¿Existe el usuario y la contraseña es correcta?
                $usuario = $login->existeUsuario();
                $autenticado = false;
                if ($usuario) {
                    $login->comprobarPassword($usuario);
                    $autenticado = $login->autenticado;
                }

                if ($autenticado) {
                    // Éxito: limpiar el contador de intentos y el bloqueo (sesión + BD).
                    unset($_SESSION[$attemptKey], $_SESSION[$lockKey]);
                    Login::limpiarIntentos($ip, $login->email);
                    $login->sincronizar((array) $usuario);
                    $login->autenticar();
                    header('Location: /');
                    exit;
                }

                // Fallo (usuario inexistente o contraseña incorrecta): contar el intento
                // tanto en sesión como en BD (IP + email).
                $_SESSION[$attemptKey] = ($_SESSION[$attemptKey] ?? 0) + 1;
                Login::registrarIntentoFallido($ip, $login->email);
                if ($_SESSION[$attemptKey] >= $maxAttempts) {
                    $_SESSION[$lockKey] = time() + $lockTime;
                    $errores[] = "Ha superado el número máximo de intentos. Acceso bloqueado por 5 minutos.";
                } else {
                    // Mensaje neutro (no revela si el correo existe)
                    $errores[] = 'Las credenciales ingresadas no son correctas';
                }
            } else {
                $errores = Login::getErrores();
            }
        }
        //Sección del renderizado
        // Mostrar vista de login
        $router->renderssdbr('/login', [
            'errores' => $errores,
            'login' => $login
        ]);
    }

    public static function logout()
    {
        // Iniciar sesión si no está activa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Limpiar todos los datos de sesión
        $_SESSION = [];

        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destruir la sesión
        session_destroy();

        // Redirigir al login
        header('Location: /login');
        exit;
    }

    public static function cambiarPassword(Router $router)
    {
        //Traemos los errores encontrados para mostrarlos
        $errores = Login::getErrores();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Creamos una nueva instancia de la clase Login
            $usu = new Login($_POST);
            //Validamos el formato del correo (no si existe)
            $errores = $usu->validarErroresCambioPswd();
            if (empty($errores)) {
                $ip = Login::obtenerIp();
                Login::purgarIntentosRecuperacion();

                // A3 — Rate-limit por IP de solicitudes de token (no revela enumeración,
                // el límite es independiente de si el correo existe).
                if (Login::excedidoSolicitudesIp($ip)) {
                    $errores[] = 'Demasiadas solicitudes. Intente nuevamente en unos minutos.';
                    $router->renderssdbr('/chgpsswd', ['errores' => $errores]);
                    return;
                }

                // A5 — Comportamiento NEUTRO contra enumeración de usuarios:
                // exista o no el correo, el flujo es idéntico (mismo destino, sin
                // mensaje que delate si la cuenta existe).
                $cuenta = $usu->buscarPorEmailParaRecuperacion();
                // A3 — cooldown anti-reenvío: solo se emite un token nuevo si la cuenta
                // existe y no se le envió otro hace menos de RECUP_COOLDOWN.
                if ($cuenta && !Login::enCooldownReenvio($usu->email)) {
                    $login = new Login((array) $cuenta);
                    // El código en claro viaja al correo; en BD se guarda su hash sha256.
                    $tokenClaro = generarCodigoAleatorioSimple(10);
                    $login->reset_token = hash('sha256', $tokenClaro);
                    if ($login->guardarToken()) {
                        // En producción manda el email; en desarrollo (sin SMTP) lo
                        // registra en includes/logs/mail.log.
                        // El resultado NO cambia lo que ve el usuario (A5: respuesta
                        // neutra contra enumeración), pero un fallo de SMTP tiene que
                        // dejar rastro: si no, el usuario espera un código que nunca
                        // salió y en el servidor no hay nada que mirar.
                        if (!enviarTokenRecuperacion($login->email, $login->datos, $tokenClaro)) {
                            error_log("[RECUPERACION] Fallo el envio del token a {$login->email}; revisar includes/logs/mail.log");
                        }
                    }
                }
                // Registrar la solicitud (alimenta el límite por IP y el cooldown).
                Login::registrarIntentoRecuperacion($ip, $usu->email, 'solicitud');
                // Siempre se avanza a la verificación del código, exista o no la cuenta.
                header('Location: /token_verify');
                exit();
            }
        }
        $router->renderssdbr('/chgpsswd', [
            'errores' => $errores
        ]);
    }
    public static function token_verify(Router $router)
    {
        $errores = Login::getErrores();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ip = Login::obtenerIp();
            Login::purgarIntentosRecuperacion();

            // A3 — Rate-limit por IP de verificaciones (anti-fuerza bruta del código).
            if (Login::excedidoVerificacionesIp($ip)) {
                $errores[] = 'Demasiados intentos. Intente nuevamente en unos minutos.';
                $router->renderssdbr('/token_verify', ['errores' => $errores]);
                return;
            }

            $log = new Login($_POST);
            $token = $log->reset_token;

            if ($token) {
                $usuario = $log->tknvrfy();
                $errores = Login::getErrores();
                //Tenemos la consulta lista, con el usuario con ese token
                if ($usuario) {
                    // La identidad del usuario que superó el token se guarda en la SESIÓN
                    // (prueba de un solo uso), NO en la URL: /updtepsswd ya no confía en
                    // ningún ?id, cerrando el IDOR que permitía resetear cuentas ajenas.
                    session_regenerate_id(true); // anti-fijación de sesión
                    $_SESSION['pwd_reset_uid'] = $usuario->id;
                    $_SESSION['pwd_reset_ts']  = time();
                    header("Location: /updtepsswd");
                    exit();
                } else {
                    // Código inválido o expirado: contar la verificación fallida.
                    Login::registrarIntentoRecuperacion($ip, null, 'verificacion');
                    $errores = Login::getErrores();
                }
            }
            $errores = $log->validarErroresToken();
            $errores = Login::getErrores();
        }
        $router->renderssdbr('/token_verify', [
            'errores' => $errores
        ]);
    }

    public static function updatePassword(Router $router)
    {
        $errores = Login::getErrores();

        // La identidad proviene EXCLUSIVAMENTE del token verificado en /token_verify
        // (guardado en sesión), nunca de la URL. Sin esa prueba —o si ya caducó junto
        // con el token— no se puede cambiar ninguna contraseña: se vuelve al inicio.
        $uid     = $_SESSION['pwd_reset_uid'] ?? null;
        $emitido = $_SESSION['pwd_reset_ts']  ?? 0;
        if (!$uid || (time() - $emitido) > Login::RECUP_TOKEN_TTL) {
            unset($_SESSION['pwd_reset_uid'], $_SESSION['pwd_reset_ts']);
            header('Location: /chgpsswd');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newusu = new Login($_POST);
            $errores = $newusu->validarUpdatePassword();
            if (empty($errores)) {
                $oldusu = Login::find($uid);
                if ($oldusu && $oldusu->updatePsswrdUser($newusu->password)) {
                    // Prueba de un solo uso: se consume al cambiar la contraseña.
                    unset($_SESSION['pwd_reset_uid'], $_SESSION['pwd_reset_ts']);
                    header('Location: /login?resultado=cambio');
                    exit();
                }
                $errores[] = 'No se pudo actualizar la contraseña. Solicite un nuevo código.';
            }
        }

        $router->renderssdbr('/updtepsswd', [
            'errores' => $errores
        ]);
    }
}
