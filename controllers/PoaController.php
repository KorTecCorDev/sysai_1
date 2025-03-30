<?php

namespace Controllers;

use MVC\Router;

use Model\Programa;
use Model\Poa;

class PoaController
{
    public static function index(Router $router)
    {
        $programas = Programa::all();
        $poas = Poa::all();
        //Mostrando el mensaje condicional
        $resultado = $_GET['resultado'] ?? null;

        $router->render('poa/admin', [
            'programas' => $programas,
            'poas' => $poas,
            'resultado' => $resultado
        ]);
    }
}
