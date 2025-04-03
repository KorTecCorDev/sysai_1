<?php

namespace Controllers;

use Model\Actividad;
use Model\FuenteActividadVista;
use Model\FuenteFinanciamiento;
use Model\Rendicion;
use Model\Resultado;
use Model\Producto;
use Model\Programa;
use Model\RendicionAdminVista;
use Model\TipoComprobante;
use MVC\Router;

class RendicionController
{
    public static function index(Router $router)
    {
        $resultado = validarORedireccionarDosParametros("rendicion/admin", "actividad_id", "resultado");
        $actividad_id = intval($_GET['actividad_id']);

        //Tomamos el producto_id por resultado de consultar a la DB por medio de la clase Producto.
        //debuguear($actividad_id);
        //debuguear((int) RendicionAdminVista::find($actividad_id));

        $totalrendis = RendicionAdminVista::findxatributo('actividad_id', $actividad_id);
        
        //Si no hay rendiciones
        if ($totalrendis) {
            $totalproducto = array_shift($totalrendis);
            $producto_id = $totalproducto->producto_id;
        } else {
            $totalproducto = Actividad::find($actividad_id);
            $producto_id = intval($totalproducto->producto_id);
        }
        if (is_array($resultado)) {
            $rendiciones = RendicionAdminVista::findwithmoretables('actividad_id', $actividad_id);
            $resultado = $resultado[1] ?? null;
            $actividad = Actividad::find($actividad_id);
            $tipocomprobante = TipoComprobante::all();
        } else {
            $rendiciones = RendicionAdminVista::findwithmoretables('actividad_id', $actividad_id);
            $resultado = null;
            $actividad = Actividad::find($actividad_id);
            $tipocomprobante = TipoComprobante::all();
        }
        $router->render('rendicion/admin', [
            'resultado' => $resultado,
            'rendiciones' => $rendiciones,
            'actividad' => $actividad,
            'producto_id' => $producto_id,
            'actividad_id' => $actividad_id,
            'tipocomprobante' => $tipocomprobante
        ]);
    }

    public static function crear(Router $router)
    {
        $tipocomprobantes = TipoComprobante::all();
        $rendicion = new Rendicion;
        $errores = Rendicion::getErrores();
        $actividad_id = (int) $_GET['actividad_id'];
        $fuentesfinanciamiento = FuenteActividadVista::findxatributo('actividad_id',$actividad_id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Agregamos el actividad_id del URL al POST.
            $_POST['actividad_id'] = $_GET['actividad_id'];
            //Creamos un nuevo objeto rendicion con el POST
            $rendicion = new Rendicion($_POST);
            $errores = $rendicion->validar();
            if (empty($errores)) {
                $rendicion->guardarsinRedireccion();
                header("Location: /rendicion/admin?actividad_id={$rendicion->actividad_id}&resultado=1");
                exit();
            }
        }
        $router->render('rendicion/crear', [
            'rendicion' => $rendicion,
            'fuentesfinanciamiento' => $fuentesfinanciamiento,
            'tipocomprobantes' => $tipocomprobantes,
            'actividad_id' => $actividad_id,
            'errores' => $errores
        ]);
    }

    public static function actualizar(Router $router)
    {
        //Validamos el id recepcionado en el GET, si es que no tiene el id se redirecciona la URL del parámetro
        $id = validarORedireccionarDosParametros("rendicion/admin", "id", "actividad_id");
        //Buscamos errores en la clase Rendicion
        $errores = Rendicion::getErrores();
        if (is_array($id)) {
            //Encontramos el registro rendicion según el id pasado por GET
            $rendicion = Rendicion::find($id[0]);
            //Encontramos el registro actividad según el id pasado por GET
            $actividad = Actividad::find($id[1]);
            //Asignamos el id de la actividad según el key id del objeto actividad
            $actividad_id = $actividad->id;
            $tipocomprobantes = TipoComprobante::all();
            $fuentesfinanciamiento = FuenteActividadVista::findxatributo('actividad_id',$actividad_id);
            if ($_SERVER["REQUEST_METHOD"] === 'POST') {
                $argsrendicion = $_POST;
                $rendicion->sincronizar($argsrendicion);
                $errores = $rendicion->validar();
                if (empty($errores)) {
                    $rendicion->guardarsinRedireccion();
                    header("Location: /rendicion/admin?actividad_id=" . $actividad_id . "&resultado=2");
                    exit();
                } else {
                    $errores = Rendicion::getErrores();
                }
            }
        }
        $router->render('rendicion/actualizar', [
            'errores' => $errores,
            'rendicion' => $rendicion,
            'fuentesfinanciamiento' => $fuentesfinanciamiento,
            'tipocomprobantes' => $tipocomprobantes,
            'actividad_id' => $actividad_id
        ]);
    }

    public static function eliminar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $rendicion = Rendicion::find($id);
            $rendicion->eliminar();
            header("Location: /rendicion/admin?actividad_id={$rendicion->actividad_id}&resultado=3");
            exit();
        }
    }
}
