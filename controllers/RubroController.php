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
            'productoid' => $productoid,
            // Saldo con signo por rubro (§2.3): negativo = sobregasto (no bloquea).
            'saldosRubros' => Rubro::saldosPorActividad((int) $actividadid)
        ]);
    }

    public static function crear(Router $router)
    {
        $errores = Rubro::getErrores();
        $idactividad = validarId('actividad');
        // A1: el coordinador solo puede crear rubros en una actividad de SU programa.
        exigirProgramaPropioPorActividad($idactividad);
        // Bloqueo de rubros si el POA Presupuestal ya fue enviado/aprobado.
        exigirPoaPresupuestalEditablePorActividad($idactividad);
        // Puerta de sobres (item 4): sin sobres asignados no se presupuesta.
        exigirSobreAsignado(programaIdPorActividad($idactividad));
        $categoriarubros = CategoriaRubro::all();
        $tiporubros = TipoRubro::all();
        $rubro = new Rubro();
        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $rubro = new Rubro($_POST);
            $rubro->agregarIdtoObjeto($idactividad, 'actividad_id');
            $rubro->validar();
            $errores = Rubro::getErrores();
            if (empty($errores)) {
                //El código se autogenera (jerárquico "<actividad>.<NN>"); el usuario no lo teclea.
                $rubro->codigo = Rubro::siguienteCodigo((int) $idactividad);
                //Insertando la acción de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = Rubro::setUsuarioActual();
                //Si es true...
                //Guardando en la base de datos
                if ($vali) {
                    $resultado = $rubro->guardarsinRedireccion();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
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
            if (!$rubro) { header('Location: /resultado/admin'); exit(); }
            // A1: el rubro debe colgar de una actividad de SU programa.
            exigirProgramaPropioPorActividad($rubro->actividad_id);
            // Bloqueo de rubros si el POA Presupuestal ya fue enviado/aprobado.
            exigirPoaPresupuestalEditablePorActividad($rubro->actividad_id);
            // Puerta de sobres (item 4): sin sobres asignados no se presupuesta.
            exigirSobreAsignado(programaIdPorActividad($rubro->actividad_id));
            $actividad = Actividad::find($id[1]);
            $categoriarubros = CategoriaRubro::all();
            $tiporubros = TipoRubro::all();

            if ($_SERVER["REQUEST_METHOD"] === 'POST') {
                $actividadOriginal = $rubro->actividad_id;
                $argsrubro = $_POST;
                $rubro->sincronizar($argsrubro);
                // A2: la actividad padre no se reasigna vía POST.
                $rubro->actividad_id = $actividadOriginal;
                $errores = $rubro->validar();
                if (empty($errores)) {
                    //Insertando la acción de audi para el usuario actual
                    //Enviamos el codigo de usuario a la base de datos
                    $vali = Rubro::setUsuarioActual();
                    //Si es true...
                    //Guardando en la base de datos
                    if ($vali) {
                        $resultado = $rubro->guardarsinRedireccion();
                    } else {
                        $errores[] = "Error al asignar el usuario actual.";
                    }
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
            if (!$rubro) { header('Location: /resultado/admin'); exit(); }
            // A1: solo puede eliminar rubros de una actividad de SU programa.
            exigirProgramaPropioPorActividad($rubro->actividad_id);
            // Bloqueo de rubros si el POA Presupuestal ya fue enviado/aprobado.
            exigirPoaPresupuestalEditablePorActividad($rubro->actividad_id);
            // Puerta de sobres (item 4): sin sobres asignados no se presupuesta.
            exigirSobreAsignado(programaIdPorActividad($rubro->actividad_id));
            //Insertando la acción de audi para el usuario actual
            //Enviamos el codigo de usuario a la base de datos
            $vali = Rubro::setUsuarioActual();
            //Si es true...
            //Guardando en la base de datos
            if($vali){
                $resultado = $rubro->eliminarsinRedireccion();
            } else {
                $errores[] = "Error al asignar el usuario actual.";
            }
            $resultado = 3;
            header("Location: /rubro/admin?actividad_id=" . $id . "&resultado=" . $resultado);
            exit();
            $router->render('rubro/eliminar', []);
        }
    }
}
