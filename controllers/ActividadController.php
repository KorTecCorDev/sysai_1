<?php

namespace Controllers;


use MVC\Router;
use Model\Actividad;
use Model\Producto;
use Model\Resultado;

// Constructor
class ActividadController
{
    // LLamado a login principal
    public static function index(Router $router)
    {
        $respt = validarORedireccionarDosParametros("/resultado/admin", "producto_id", "resultado");

        if (is_array($respt)) {
            $actividades = Actividad::findxatributo("producto_id", $respt[0]);
            $resultado = $respt[1] ?? null;
            $productoid = $respt[0];
        } else {
            $actividades = Actividad::findxatributo("producto_id", $respt);
            $resultado = null;
            $productoid = $respt ?? null;
        }
        $objproducto = Producto::find($productoid);
        $resultadoid = $objproducto->resultado_id;
        $router->render('actividad/admin', [
            'actividades' => $actividades,
            'resultado' => $resultado,
            'objproducto' => $objproducto,
            'productoid' => $productoid,
            'resultadoid' => $resultadoid
        ]);
    }

    public static function crear(Router $router)
    {
        $errores = Actividad::getErrores();
        $idproducto = validarId('producto');
        // A1: el coordinador solo puede crear actividades bajo un producto de SU programa.
        exigirProgramaPropioPorProducto($idproducto);
        $actividad = new Actividad();
        $resultado = $_GET['resultado'] ?? null;
        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $actividad = new Actividad($_POST);
            $actividad->agregarIdtoObjeto($idproducto, 'producto_id');
            $actividad->validar();
            $errores = Actividad::getErrores();
            if (empty($errores)) {
                //Insertando la acción de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = Actividad::setUsuarioActual();
                //Si es true...
                //Guardando en la base de datos
                if ($vali) {
                    $resultado = $actividad->guardarsinRedireccion();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
                header("Location: /actividad/admin?producto_id=" . $idproducto . "&resultado=1");
                exit();
            } else {
                $errores = Actividad::getErrores();
            }
        }
        $router->render('actividad/crear', [
            'actividad' => $actividad,
            'errores' => $errores,
            'idproducto' => $idproducto,
            'resultado' => $resultado
        ]);
    }
    public static function actualizar(Router $router)
    {
        //Validamos el id recepcionado en el GET, si es que no tiene el id se redirecciona la URL del parámetro
        $id = validarORedireccionarDosParametros("/resultado/admin", "id", "producto_id");
        //Encontramos al resultado por el ID
        if (is_array($id)) {
            $producto = Producto::find($id[1]);
            $actividad = Actividad::find($id[0]);
            if (!$actividad) { header('Location: /resultado/admin'); exit(); }
            // A1: la actividad debe colgar de un producto de SU programa.
            exigirProgramaPropioPorProducto($actividad->producto_id);
            $errores = Actividad::getErrores();
        }
        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $productoOriginal = $actividad->producto_id;
            $argsactividad = $_POST;
            $actividad->sincronizar($argsactividad);
            // A2: el producto padre no se reasigna vía POST.
            $actividad->producto_id = $productoOriginal;
            $errores = $actividad->validar();
            if (empty($errores)) {
                //Insertando la acción de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = Actividad::setUsuarioActual();
                //Si es true...
                //Guardando en la base de datos
                if ($vali) {
                    $resultado = $actividad->guardarsinRedireccion();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
                header("Location: /actividad/admin?producto_id=" . $producto->id . "&resultado=2");
                exit();
            }
        }
        $router->render('actividad/actualizar', [
            'errores' => $errores,
            'actividad' => $actividad
        ]);
    }

    public static function eliminar(Router $router)
    {

        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $id = validarORedireccionarDosParametrosPost("resultado/admin", "producto_id", "id");

            $resultado = null;
            if (!is_array($id)) {
                header("Location: /actividad/error!!");
                exit();
            } else {
                $actividad = Actividad::find($id[1]);
                if (!$actividad) { header('Location: /resultado/admin'); exit(); }
                // A1: solo puede eliminar actividades de un producto de SU programa.
                exigirProgramaPropioPorProducto($actividad->producto_id);
                //Insertando la acción de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = Actividad::setUsuarioActual();
                //Si es true...
                //Guardando en la base de datos
                if ($vali) {
                    $resultado = $actividad->eliminarsinRedireccion();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
                $resultado = 3;
                header("Location: /actividad/admin?producto_id=" . $id[0] . "&resultado=" . $resultado);
                exit();
            }
            $router->render('actividad/eliminar', [
                'resultado' => $resultado
            ]);
        }
    }
}
