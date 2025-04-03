<?php

namespace Controllers;

use MVC\Router;

use Model\Producto;
use Model\Programa;
use Model\Resultado;

class ProductoController
{
    public static function index(Router $router)
    {
        $respt = validarORedireccionarDosParametros("resultado/admin", "resultado_id", "resultado");

        if (is_array($respt)) {
            $productos = Producto::findxatributo("resultado_id", $respt[0]);
            $resultado = $respt[1] ?? null;
            $resultadoid = $respt[0];
            $aux = Resultado::find($resultadoid);
            $programaid = $aux->programa_id;
        } else {
            $productos = Producto::findxatributo("resultado_id", $respt);
            $resultado = null;
            $resultadoid = $respt ?? null;
            if ($respt) {
                $aux = Resultado::find($resultadoid);
                $programaid = $aux->programa_id;
            }
        }
        $router->render('producto/admin', [
            'productos' => $productos,
            'resultado' => $resultado,
            'programaid' => $programaid,
            'resultadoid' => $resultadoid
        ]);
    }
    public static function crear(Router $router)
    {
        $errores = Producto::getErrores();
        $resultadoid = validarId('resultado');
        $producto = new Producto();
        $resultado = $_GET['resultado'] ?? null;
        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $producto = new Producto($_POST);
            $producto->agregarIdtoObjeto($resultadoid, 'resultado_id');
            $producto->validar();
            $errores = Producto::getErrores();
            if (empty($errores)) {
                $producto->guardarsinRedireccion();
                header("Location: /producto/admin?resultado_id=" . $resultadoid);
                exit();
            } else {
                $errores = Producto::getErrores();
            }
        }
        $router->render('producto/crear', [
            'producto' => $producto,
            'errores' => $errores,
            'resultadoid' => $resultadoid,
            'resultado' => $resultado
        ]);
    }

    public static function actualizar(Router $router)
    {
        //Validamos el id recepcionado en el GET, si es que no tiene el id se redirecciona la URL del parámetro
        $id = validarORedireccionarDosParametros("resultado/admin", "id", "resultado_id");
        //Encontramos al resultado por el ID
        if (is_array($id)) {
            $resultado = Resultado::find($id[1]);
            $producto = Producto::find($id[0]);
            $errores = Producto::getErrores();
        }
        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $argsproducto = $_POST;
            $producto->sincronizar($argsproducto);
            $errores = $producto->validar();
            if (empty($errores)) {
                $producto->guardarsinRedireccion();
                header("Location: /producto/admin?resultado_id=" . $resultado->id . "&resultado=2");
                exit();
            }
        }
        $router->render('producto/actualizar', [
            'errores' => $errores,
            'producto' => $producto
        ]);
    }

    public static function eliminar(Router $router)
    {

        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $id = validarORedireccionarDosParametrosPost("resultado/admin", "resultado_id", "id");
            $resultado = null;
            if (!is_array($id)) {
                header("Location: /producto/error!!");
                exit();
            } else {

                $producto = Producto::find($id[1]);

                $producto->eliminarsinRedireccion();
                $resultado = 3;
                header("Location: /producto/admin?resultado_id=" . $id[0] . "&resultado=" . $resultado);
                exit();
            }
            $router->render('producto/eliminar', [
                'resultado' => $resultado
            ]);
        }
    }
}
