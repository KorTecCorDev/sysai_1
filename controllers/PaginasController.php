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
        if (empty($_SESSION)) {
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
