<?php

namespace Controllers;

use MVC\Router;
use Model\Login;

// Constructor
class PaginasController
{
    // LLamado a login principal
    public static function index(Router $router)
    {
        // Se comprueba la BANDERA de sesión, no que la sesión esté vacía: /login
        // siembra el csrf_token, así que tras visitarlo $_SESSION ya no está vacía
        // y `empty($_SESSION)` dejaba pasar a un visitante sin autenticar (servía
        // una página en blanco, pero el criterio no era el del resto del sistema).
        // Mismo criterio que Router::comprobarRutas().
        if (empty($_SESSION['login'])) {
            header('Location: /login');
            exit();
        } else {
            $router->render('/main', []);
        }
    }

    // LLamado a error 404
    public static function error404(Router $router)
    {
        $router->renderssdbr('/error404', []);
    }
}
