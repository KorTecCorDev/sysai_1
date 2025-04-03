<?php

namespace Controllers;



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
    // Verificar si ya está autenticado primero
    if (isset($_SESSION['login'])) {
        header('Location: /');
        exit;
    }

    $login = new Login();
    $errores = Login::getErrores() ?? [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = new Login($_POST);
        $errores = $login->validar();

        if (empty($errores)) {
            $resultado = $login->existeUsuario();
            
            if (!$resultado) {
                $errores = Login::getErrores();
            } else {
                $login->comprobarPassword($resultado);
                
                if ($login->autenticado) {
                    // Autenticación exitosa - asignar propiedades
                    $login->id = $resultado->id;
                    $login->cargo_id = $resultado->cargo_id;
                    
                    if ($login->cargo_id == 3) {
                        $login->poa_id = $resultado->poa_id;
                        $login->programa_id = $resultado->programa_id;
                    }
                    
                    $login->email = $resultado->email;
                    $login->password = $resultado->password;
                    $login->reset_token = $resultado->reset_token;
                    $login->datos = $resultado->datos;
                    $login->cargo = $resultado->cargo;
                    
                    // Autenticar (esto establecerá la sesión)
                    $login->autenticar();
                    
                    // Redirigir al home y salir
                    header('Location: /');
                    exit;
                } else {
                    $errores = Login::getErrores();
                }
            }
        }
    }
    
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
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
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
                    // Creamos un código que irá en la propiedad reset_token
                    $respt->reset_token = generarCodigoAleatorioSimple();
                    //Actualizando el registro de usuario con el nuevo reset_token
                    $valor = $respt->guardarToken();
                    if ($valor) {
                        // configure an SMTP
                        try {
                            $mail = new PHPMailer();
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'pruebaskorteccorsmtp@gmail.com';
                            $mail->Password = 'exmjfrcmbmpkflsv';
                            $mail->SMTPSecure = 'tsl';
                            $mail->Port = 587;
                            $mail->setFrom('pruebaskorteccorsmtp@gmail.com', 'Área de TI - CRONOS SOLUCIONES');
                            $mail->addAddress($respt->email, $respt->datos);
                            $mail->Subject = 'Respuesta a Solicitud de cambio de Contraseña';
                            // Set HTML 
                            $mail->isHTML(TRUE);
                            $mail->CharSet = 'UTF-8';
                            $contenido = '<!DOCTYPE html>
                                                <html lang="en">
                                                <head>
                                                    <meta charset="UTF-8">
                                                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                                    <title>Código de Cambio de Contraseña</title>
                                                    <style>
                                                        /* Estilos generales */
                                                        body {
                                                            font-family: Arial, sans-serif;
                                                            background-color: #f4f4f4;
                                                            margin: 0;
                                                            padding: 0;
                                                        }
                                                        .email-container {
                                                            max-width: 600px;
                                                            margin: 20px auto;
                                                            background-color: #ffffff;
                                                            border-radius: 8px;
                                                            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                                                            overflow: hidden;
                                                        }
                                                        .header {
                                                            background-color: #42A5F5;
                                                            color: white;
                                                            text-align: center;
                                                            padding: 20px;
                                                        }
                                                        .header h1 {
                                                            margin: 0;
                                                            font-size: 24px;
                                                        }
                                                        .content {
                                                            padding: 20px;
                                                            text-align: center;
                                                        }
                                                        .content p {
                                                            font-size: 16px;
                                                            line-height: 1.5;
                                                            color: #333333;
                                                        }
                                                        .code {
                                                            font-size: 24px;
                                                            font-weight: bold;
                                                            color: #42A5F5;
                                                            margin: 20px 0;
                                                        }
                                                        .footer {
                                                            background-color: #f9f9f9;
                                                            color: #777777;
                                                            text-align: center;
                                                            font-size: 14px;
                                                            padding: 10px 20px;
                                                        }
                                                        .footer a {
                                                            color: #4CAF50;
                                                            text-decoration: none;
                                                        }
                                                    </style>
                                                </head>
                                                <body>
                                                    <div class="email-container">
                                                        <!-- Encabezado -->
                                                        <div class="header">
                                                            <h1>Cambio de Contraseña</h1>
                                                        </div>
                                                        <!-- Contenido -->
                                                        <div class="content">
                                                            <p>Hola, ' . $respt->datos . ' </p>
                                                            <p>Has solicitado cambiar tu contraseña. Utiliza el siguiente código para completar el proceso:</p>
                                                            <div class="code">' . $respt->reset_token . '</div>
                                                            <p>Si no solicitaste este cambio, ignora este correo electrónico.</p>
                                                        </div>
                                                        <!-- Pie de página -->
                                                        <div class="footer">
                                                            <p>Gracias por confiar en nosotros. Cronos Soluciones.</p>
                                                        </div>
                                                    </div>
                                                </body>
                                                </html>';
                            $mail->Body = $contenido;
                            $mail->AltBody = 'Esto es texto alternativo';
                            // send the message
                            if (!$mail->send()) {
                                $errores = 'Hubo un Error... intente de nuevo';
                                header('Location: /chgpsswd');
                                exit();
                            } else {
                                $errores = 'Email enviado Correctamente';
                                header('Location: /token_verify');
                                exit();
                            }
                        } catch (Exception $e) {
                            debuguear("Error " . $mail->ErrorInfo);
                        }
                    }
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
