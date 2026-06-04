<?php

namespace Controllers;

use MVC\Router;

use Model\Programa;
use Model\Resultado;

class ResultadoController
{
    public static function index(Router $router)
    {

        $idprograma = validarId('programa');
        $res = new Resultado();
        $programas = Programa::all();
        //Es un coordinador?
        $tipousuarioid = $_SESSION['cargo_id'];
        if ($tipousuarioid == 3) {
            $programas = Programa::findmany($_SESSION['programa_id']);
            $idprograma = intval($programas[0]->id);
        }
        $resultado = $_GET['resultado'] ?? null;
        $resultados = [];
        if ($idprograma) {
            $resultados = $res->findxatributo("programa_id", $idprograma);
        }
        $router->render('resultado/admin', [
            'idprograma' => $idprograma,
            'resultados' => $resultados,
            'resultado' => $resultado,
            'programas' => $programas
        ]);
    }

    public static function crear(Router $router)
    {

        $errores = Resultado::getErrores();
        $idprograma = validarId('programa');
        // A1/A2: el coordinador solo puede crear resultados en SU programa (ignora el GET).
        if (esCoordinador()) {
            $idprograma = programaIdCoordinador();
        }
        exigirProgramaPropio($idprograma);
        // Bloqueo de jerarquía si el POA de Indicadores ya fue enviado/aprobado.
        exigirPoaIndicadoresEditable($idprograma);
        $res = new Resultado();
        $programas = Programa::all();
        $resultado = $_GET['resultado'] ?? null;
        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $res = new Resultado($_POST);
            $res->agregarIdtoObjeto($idprograma, 'programa_id');
            $res->validar();
            $errores = Resultado::getErrores();
            if (empty($errores)) {
                //Insertando la acción de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = Resultado::setUsuarioActual();
                //Si es true...
                //Guardando en la base de datos
                if ($vali) {
                    $resultado = $res->guardar();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
            } else {
                $errores = Resultado::getErrores();
            }
        }
        $router->render('resultado/crear', [
            'res' => $res,
            'errores' => $errores,
            'idprograma' => $idprograma,
            'resultado' => $resultado,
            'programas' => $programas
        ]);
    }

    public static function actualizar(Router $router)
    {
        //Validamos el id recepcionado en el GET, si es que no tiene el id se redirecciona la URL del parámetro
        $id = validarORedireccionarDosParametros("resultado/admin", "programa_id", "id");
        //Encontramos al resultado por el ID
        if (is_array($id)) {
            $programaid = $id[0];
            $res = Resultado::find($id[1]);
            if (!$res) { header('Location: /resultado/admin'); exit(); }
            // A1: el resultado debe pertenecer al programa del coordinador.
            exigirProgramaPropio($res->programa_id);
            // Bloqueo de jerarquía si el POA de Indicadores ya fue enviado/aprobado.
            exigirPoaIndicadoresEditable($res->programa_id);
            $errores = Resultado::getErrores();
        }
        $resultado = $_GET['resultado'] ?? null;
        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $programaOriginal = $res->programa_id;
            $argsresultado = $_POST;
            $res->sincronizar($argsresultado);
            // A2: el programa padre no se reasigna vía POST (evita mover el registro a otro programa).
            $res->programa_id = $programaOriginal;
            $errores = $res->validar();
            if (empty($errores)) {
                //Insertando la acción de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = Resultado::setUsuarioActual();
                //Si es true...
                //Guardando en la base de datos
                if ($vali) {
                    $resultado = $res->guardar();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
                header("Location: /resultado/admin?programa_id=" . $programaid . "&resultado=2");
                exit();
            }
        }


        $router->render('resultado/actualizar', [
            'errores' => $errores,
            //Cambiamos el nombre del 'res' porque en el formulario tienen este key como dato para todos los elementos de formularios
            'res' => $res,
            'programaid' => $programaid,
            'resultado' => $resultado
        ]);
    }
    public static function eliminar(Router $router)
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = validarORedireccionarPost("admin");
            if (isset($id)) {
                $tipo = $_POST['tipo'];
                if (validarTipoContenido($tipo)) {
                    $res = Resultado::find($id);
                    if ($res) {
                        // A1: solo puede eliminar resultados de SU programa.
                        exigirProgramaPropio($res->programa_id);
                        // Bloqueo de jerarquía si el POA de Indicadores ya fue enviado/aprobado.
                        exigirPoaIndicadoresEditable($res->programa_id);
                        //Insertando la acción de audi para el usuario actual
                        //Enviamos el codigo de usuario a la base de datos
                        $vali = Resultado::setUsuarioActual();
                        //Si es true...
                        //Guardando en la base de datos
                        if ($vali) {
                            $resultado = $res->eliminar();
                        } else {
                            $errores[] = "Error al asignar el usuario actual.";
                        }
                        header("Location: /resultado/admin?resultado=3");
                        exit();
                    }
                }
            }
        }
    }
}
