<?php

namespace Controllers;


use MVC\Router;
use Model\Actividad;
use Model\CategoriaRubro;
use Model\TipoRubro;
use Model\Producto;
use Model\Resultado;
use Model\Rubro;
use Model\RubroVista;

// Constructor
class RubroController
{
    // LLamado a login principal
    public static function index(Router $router)
    {
        $respt = validarORedireccionarDosParametros("resultado/admin", "actividad_id", "resultado");
        if (is_array($respt)) {
            $rubros = RubroVista::findxatributo("actividad_id", $respt[0]);
            $resultado = $respt[1] ?? null;
            $actividadid = $respt[0];
            $objactividad = Actividad::find($actividadid);
            $productoid = $objactividad->producto_id;
        } else {
            $rubros = RubroVista::findxatributo("actividad_id", $respt);
            $resultado = null;
            $actividadid = $respt ?? null;
            $objactividad = Actividad::find($actividadid);
            $productoid = $objactividad->producto_id;
        }
        $router->render('rubro/admin', [
            'rubros' => $rubros,
            'resultado' => $resultado,
            'actividadid' => $actividadid,
            'objactividad' => $objactividad,
            'productoid' => $productoid
        ]);
    }

    public static function crear(Router $router)
    {
        $errores = Rubro::getErrores();
        $idactividad = validarId('actividad');
        $categoriarubros = CategoriaRubro::all();
        $tiporubros = TipoRubro::all();
        $rubro = new Rubro();
        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $rubro = new Rubro($_POST);
            $rubro->agregarIdtoObjeto($idactividad, 'actividad_id');
            $rubro->validar();
            $errores = Rubro::getErrores();
            if (empty($errores)) {
                $rubro->guardarsinRedireccion();
                header("Location: /rubro/admin?actividad_id=" . $idactividad . "&resultado=1");
                exit();
            } else {
                $errores = Rubro::getErrores();
            }
        }
        $router->render('rubro/crear', [
            'rubro' => $rubro,
            'tiporubros' => $tiporubros,
            'errores' => $errores,
            'idactividad' => $idactividad,
            'categoriarubros' => $categoriarubros
        ]);
    }
    public static function actualizar(Router $router)
    {
        //Validamos el id recepcionado en el GET, si es que no tiene el id se redirecciona la URL del parámetro
        $id = validarORedireccionarDosParametros("resultado/admin", "id", "actividad_id");
        //Encontramos al resultado por el ID
        $errores = Rubro::getErrores();
        if (is_array($id)) {
            $rubro = Rubro::find($id[0]);
            $actividad = Actividad::find($id[1]);
            $categoriarubros = CategoriaRubro::all();
            $tiporubros = TipoRubro::all();

            if ($_SERVER["REQUEST_METHOD"] === 'POST') {
                $argsrubro = $_POST;
                $rubro->sincronizar($argsrubro);
                $errores = $rubro->validar();
                if (empty($errores)) {
                    $rubro->guardarsinRedireccion();
                    header("Location: /rubro/admin?actividad_id=" . $actividad->id . "&resultado=2");
                    exit();
                } else {
                    $errores = Rubro::getErrores();
                }
            }
        }
        $router->render('rubro/actualizar', [
            'errores' => $errores,
            'rubro' => $rubro,
            'categoriarubros' => $categoriarubros,
            'tiporubros' => $tiporubros,
            'idactividad' => $actividad->id
        ]);
    }

    public static function eliminar(Router $router)
    {
        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $id = validarORedireccionarPost("/resultado/admin");
            $rubro = Rubro::find($id);
            $rubro->eliminarsinRedireccion();
            $resultado = 3;
            header("Location: /rubro/admin?actividad_id=" . $id . "&resultado=" . $resultado);
            exit();
            $router->render('rubro/eliminar', []);
        }
    }
}
