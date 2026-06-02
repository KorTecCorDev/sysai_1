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
            //Validamos si existen errores
            $errores = $usu->validarErroresCambioPswd();
            //Consultando al DB
            if (empty($errores)) {
                $email = $usu->email;
                //Verificamos si exister el usuario
                $respt = $usu->existeUsuario($email);
                $errores = Login::getErrores();
                if ($respt) {
                    // Generamos y guardamos el token de recuperación
                    $respt->reset_token = generarCodigoAleatorioSimple();
                    $valor = $respt->guardarToken();
                    if ($valor) {
                        // Enviar el token. En producción manda el email; en desarrollo
                        // (sin SMTP) lo registra en includes/logs/mail.log.
                        enviarTokenRecuperacion($respt->email, $respt->datos, $respt->reset_token);
                        // El token ya está guardado en BD → avanzamos a la verificación del código.
                        header('Location: /token_verify');
                        exit();
                    }
                    $errores[] = 'No se pudo iniciar la recuperación. Intente nuevamente.';
                } else {
                    $errores = Login::getErrores();
                }
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
            $log = new Login($_POST);
            $token = $log->reset_token;

            if ($token) {
                $usuario = $log->tknvrfy();
                $errores = Login::getErrores();
                //Tenemos la consulta lista, con el usuario con ese token
                if ($usuario) {
                    //Permitir ingresar nueva contraseña y reemplazar a la anterior con su hasheo
                    //Pasamos el id de usuario para actualizar contraseña
                    header("Location: /updtepsswd?id=" . $usuario->id);
                    exit();
                } else {
                    //Mandar mensajes de Error
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
        $id = validarORedireccionar('/updtepsswd');
        $errores = Login::getErrores();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newusu = new Login($_POST);
            $newpssw = $newusu->password;
            if ($id && $newpssw) {
                $oldusu = Login::find($id);
                if ($oldusu) {
                    $resultado = $oldusu->updatePsswrdUser($newpssw);
                    if ($resultado) {
                        header("Location: /login");
                        exit();
                    }
                }
                $newusu->validarUpdatePassword();
                $errores = Login::getErrores();
            }
            $newusu->validarUpdatePassword();
            $errores = Login::getErrores();
        }

        $router->renderssdbr('/updtepsswd', [
            'errores' => $errores
        ]);
    }
}
