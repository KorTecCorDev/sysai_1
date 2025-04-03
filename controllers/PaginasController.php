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
}
