<?php

namespace Controllers;

use MVC\Router;
use Model\TipoCambioDolar;
use Model\TipoCambioEuro;
use Model\Usuario;
use Model\VistaDolar;
use Model\VistaEuro;

class TipoCambioController
{
    public static function indexDolar(Router $router)
    {
        $tiposCambioDolar = VistaDolar::all();
        $resultado = $_GET['resultado'] ?? null;

        $router->render('tcambio/dolar/admin', [
            'tiposCambioDolar' => $tiposCambioDolar,
            'resultado' => $resultado
        ]);
    }

    public static function indexEuro(Router $router)
    {
        $tiposCambioEuro = VistaEuro::all();
        $resultado = $_GET['resultado'] ?? null;

        $router->render('tcambio/euro/admin', [
            'tiposCambioEuro' => $tiposCambioEuro,
            'resultado' => $resultado
        ]);
    }

    public static function crearDolar(Router $router)
    {
        $tipoCambioDolar = new TipoCambioDolar();
        $errores = TipoCambioDolar::getErrores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $tipoCambioDolar = new TipoCambioDolar();
            $tipoCambioDolar->usuario_id = $_SESSION['id'];
            $tipoCambioDolar->tipo_cambio = $_POST['tipocambio'];
            
            $errores = $tipoCambioDolar->validar();
            if (empty($errores)) {
                $resultado = $tipoCambioDolar->guardar();
                header('Location: /tcambio/dolar/admin?resultado=1');
            }
        }

        $router->render('tcambio/dolar/crear', [
            'tipocambio' => $tipoCambioDolar,
            'errores' => $errores
        ]);
    }

    public static function crearEuro(Router $router)
    {
        $tipoCambioEuro = new TipoCambioEuro();
        $errores = TipoCambioEuro::getErrores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $tipoCambioEuro = new TipoCambioEuro($_POST);
            $tipoCambioEuro->usuario_id = $_SESSION['id'];
            $tipoCambioEuro->tipo_cambio = $_POST['tipocambio'];
            
            $errores = $tipoCambioEuro->validar();
            if (empty($errores)) {
                $resultado = $tipoCambioEuro->guardarsinRedireccion();
                header('Location: /tcambio/euro/admin?resultado=1');
            }
        }

        $router->render('tcambio/euro/crear', [
            'tipocambio' => $tipoCambioEuro,
            'errores' => $errores
        ]);
    }

    public static function actualizarDolar(Router $router)
    {
        $id = validarORedireccionar('/tcambio/dolar/admin');

        $tipoCambioDolar = TipoCambioDolar::find($id);
        $errores = TipoCambioDolar::getErrores();

        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $args['tipo_cambio'] = $_POST['tipocambio'];
            $tipoCambioDolar->sincronizar($args);
            $errores = $tipoCambioDolar->validar();
            if (empty($errores)) {
                $resultado = $tipoCambioDolar->guardarsinRedireccion();
                header('Location: /tcambio/dolar/admin?resultado=2');
            }
        }

        $router->render('tcambio/dolar/actualizar', [
            'tipocambio' => $tipoCambioDolar,
            'errores' => $errores
        ]);
    }

    public static function actualizarEuro(Router $router)
    {
        $id = validarORedireccionar('/tcambio/euro/admin');
        $tipoCambioEuro = TipoCambioEuro::find($id);
        $errores = TipoCambioEuro::getErrores();

        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            //Instanciamos un array asociativo $args en blanco
            $args=[];
            //Asiganamos el valor de la propiedad tipocambio del POST a la propiedad tipo_cambio del array asociativo $args
            $args['tipo_cambio'] = $_POST['tipocambio'];
            //Sincronizamos los valores de las propiedades que si existan en el array asociativo $args
            $tipoCambioEuro->sincronizar($args);
            $errores = $tipoCambioEuro->validar();
            if (empty($errores)) {
                $resultado = $tipoCambioEuro->guardarsinRedireccion();
                header('Location: /tcambio/euro/admin?resultado=2');
            }
        }

        $router->render('tcambio/euro/actualizar', [
            'tipocambio' => $tipoCambioEuro,
            'errores' => $errores
        ]);
    }

    public static function eliminarDolar(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $idTipoCambio = filter_var($id, FILTER_VALIDATE_INT);
            if ($idTipoCambio) {
                $tipoCambio = TipoCambioDolar::find($idTipoCambio);
                if ($tipoCambio) {
                    $tipoCambio->eliminar();
                    header("Location: /tcambio/dolar/admin?resultado=3");
                }
            }
        }
    }

    public static function eliminarEuro(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $idTipoCambio = filter_var($id, FILTER_VALIDATE_INT);
            if ($idTipoCambio) {
                $tipoCambio = TipoCambioEuro::find($idTipoCambio);
                if ($tipoCambio) {
                    $tipoCambio->eliminar();
                    header("Location: /tcambio/euro/admin?resultado=3");
                }
            }
        }
    }
}
