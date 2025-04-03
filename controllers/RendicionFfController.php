<?php

namespace Controllers;

use Model\FuenteFinanciamiento;
use Model\Rendicion;
use Model\RendicionFf;
use MVC\Router;
use Model\SumaRendicionVista;
use Model\RendicionFfActividadVista;
use Model\Actividad;

class RendicionFfController
{
    public static function index(Router $router)
    {
        $resultado = validarORedireccionarDosParametros("rendicionff/admin", "actividad_id", "resultado");
        $actividad_id = (int) $_GET['actividad_id'];
        $actividad_nombre = Actividad::find($actividad_id)->nombre;
        $respt = RendicionFfActividadVista::findwithmoretables('actividad_id', $actividad_id);
        $resptrendiff = SumaRendicionVista::findwithmoretables('actividad_id', $actividad_id);
        $particiones = RendicionFf::findwithmoretables('actividad_id', $actividad_id);
        if (is_array($resultado)) {
            $resultado = $resultado[1] ?? null;
            foreach ($respt as $res) {
                $fuenteffid[] = $res->fuente_financiamiento_id;
            }
        } else {
            $resultado = null;
            foreach ($respt as $res) {
                $fuenteffid[] = $res->fuente_financiamiento_id;
            }
        }
        $router->render('rendicionff/admin', [
            'resultado' => $resultado,
            'respt' => $respt,
            'fuenteffid' => $fuenteffid,
            'actividad_id' => $actividad_id,
            'resptrendiff' => $resptrendiff,
            'particiones' => $particiones,
            'actividad_nombre' => $actividad_nombre,
        ]);
    }
    //Reorganizar el crear
    public static function crear(Router $router)
    {
        $respt = validarORedireccionarconTabla("rendicionff/admin", "actividad");
            $actividad_id = (int) $_GET['actividad_id'];
            $resultados = RendicionFfActividadVista::findwithmoretables('actividad_id', $actividad_id);
            
            $rendi = new RendicionFf();
            foreach ($resultados as $res) {
                $fuentesf[] = FuenteFinanciamiento::find($res->fuente_financiamiento_id);
            } 
            //Array con mensajes de error
            $errores = RendicionFf::getErrores();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                //Creamos una nueva instancia
                $rendi = new RendicionFf($_POST['rendi']);
                //Validamos
                $errores = $rendi->validar();
                //Si no hubiera errores...
                if (empty($errores)) {
                    //Guardando en la base de datos
                    $respt = $rendi->guardarsinRedireccion();
                    if ($respt) {
                        header('Location: /rendicionff/admin?actividad_id=' . $actividad_id . '&resultado=1');
                    }
                }
            }
 
        $router->render('rendicionff/crear', [
            'resultados' => $resultados,
            'rendi' => $rendi,
            'fuentesf' => $fuentesf,
            'errores' => $errores,
        ]);
    }
}
