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
        $res = new Resultado();
        $programas = Programa::all();
        $resultado = $_GET['resultado'] ?? null;
        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $res = new Resultado($_POST);
            $res->agregarIdtoObjeto($idprograma, 'programa_id');
            $res->validar();
            $errores = Resultado::getErrores();
            if (empty($errores)) {
                $res->guardar();
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
            $errores = Resultado::getErrores();
        }
        $resultado = $_GET['resultado'] ?? null;
        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $argsresultado = $_POST;
            $res->sincronizar($argsresultado);
            $errores = $res->validar();
            if (empty($errores)) {
                $resultado = $res->guardar();
                header("Location: /resultado/admin?programa_id=" . $programaid . "&resultado=2");
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
                        $res->eliminar();
                        header("Location: /resultado/admin?resultado=3");
                    }
                }
            }
        }
    }
}
