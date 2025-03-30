<?php

namespace Controllers;

use Model\FuenteFinanciamiento;
use MVC\Router;

use Model\Programa;

class Fuente_FinanciamientoController
{
    public static function index(Router $router)
    {
        $fuente_financiamiento = FuenteFinanciamiento::all();
        //Mostrando el mensaje condicional
        $resultado = $_GET['resultado'] ?? null;
        
        $router->render('fuente_financiamiento/admin', [
            'fuente_financiamiento' => $fuente_financiamiento,
            'resultado' => $resultado
        ]);
    }

    public static function crear(Router $router)
    {
        $fuente_financiamiento = new FuenteFinanciamiento();
        //Array con mensajes de error
        $errores = FuenteFinanciamiento::getErrores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Creamos una nueva instancia
            $fuente_financiamiento = new FuenteFinanciamiento($_POST['fuente_financiamiento']);
            //Validamos
            $errores = $fuente_financiamiento->validar();
            //Antes de insertar los datos deberemos de validar que el array de errores esté vacío
            if (empty($errores)) {
                //Guardando en la base de datos
                $resultado = $fuente_financiamiento->guardar();
            }
        }
        $router->render('fuente_financiamiento/crear', [
            'fuente_financiamiento' => $fuente_financiamiento,
            'errores' => $errores
        ]);
    }

    public static function actualizar(Router $router)
    {
        $id = validarORedireccionar('/admin');
        $fuente_financiamiento = FuenteFinanciamiento::find($id);
        $errores = FuenteFinanciamiento::getErrores();

        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            //Asignando los atributos
            $args = $_POST['fuente_financiamiento'];
            $fuente_financiamiento->sincronizar($args);
            //Validación
            $errores = $fuente_financiamiento->validar();

            if (empty($errores)) {
                $fuente_financiamiento->guardar();
            }
        }
        $router->render('fuente_financiamiento/actualizar',[
            'fuente_financiamiento' => $fuente_financiamiento,
            'errores'=> $errores
        ]);
    }
    public static function eliminar(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            //Las validaciones de datos nos permitirán ejecutar la instrucción, solamente con el dato requerido
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id) {
                
                $tipo=$_POST['tipo'];
                if (validarTipoContenido($tipo)) {
                    $fuente_financiamiento = FuenteFinanciamiento::find($id);
                    $fuente_financiamiento->eliminar();
                    header("Location: /fuente_financiamiento/admin?resultado=3");
                }  
            }
        }
    }
}
