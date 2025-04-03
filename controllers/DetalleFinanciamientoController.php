<?php

namespace Controllers;

use MVC\Router;

use Model\Programa;
use Model\DetalleFinanciamiento;
use Model\FuenteFinanciamiento;

class DetalleFinanciamientoController
{
    public static function index(Router $router)
    {
        // $programas = Programa::all();
        // $fuentes_financiamiento = FuenteFinanciamiento::all();
        // $detalles_financiamiento = DetalleFinanciamiento::all();
        // //Mostrando el mensaje condicional
        $resultado = $_GET['resultado'] ?? null;
        $router->render('dfinanciamiento/crear', [
            // 'programas' => $programas,
            // 'fuentes_financiamiento' => $fuentes_financiamiento,
            // 'detalles_financiamiento' => $detalles_financiamiento,
            'resultado' => $resultado
        ]);
    }


    public static function crear(Router $router)
    {
        //Creamos las variables para poder mostrar en las vistas sin necesidad de crear un registro con el POST
        $resuls = [];
        $resultado = $_GET['resultado'] ?? null;
        //Tomamos el valor del programa_id para cambiar los estados de las cards de fuente de financiamiento
        $prgma_id = $_GET['programa_id'] ?? null;
        //Instanciamos el objeto a crear el registro y pedimos todos los registros obtenidos por las otras tablas que se necesita
        $detallefinanciamiento = new DetalleFinanciamiento();
        $programas = Programa::all();
        $fuentesfinanciamiento = FuenteFinanciamiento::all();
        //Array con mensajes de error
        $errores = DetalleFinanciamiento::getErrores();
        //Si existen fuentes de financiamiento relacionadas en la tabla d_financiamiento se hace la consulta de cuales son...
        //Siempre se retornará el $prgma_id
        if ($prgma_id) {
            $resuls = DetalleFinanciamiento::findwithtableforanea($prgma_id);
            //Colocamos en la condición si se está enviando un POST y sí existen los resultados de relacionamiento de ff y programa.
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $detallefinanciamiento = DetalleFinanciamiento::findwithparameters("programa_id", $prgma_id, "fuente_financiamiento_id", $_POST['detalle_financiamiento']['fuente_financiamiento_id']);
                $dfid = $detallefinanciamiento[0]->id;
                if (isset($detallefinanciamiento, $dfid)) {
                    $veriftipo = $_POST['detalle_financiamiento']['tipo'];
                    //Las validaciones de datos nos permitirán ejecutar la instrucción, solamente con el dato requerido
                    $iddfinanciamiento = filter_var($dfid, FILTER_VALIDATE_INT);
                    if ($iddfinanciamiento) {
                        if (validarTipoContenido($veriftipo)) {
                            $detallefinanciamiento = DetalleFinanciamiento::find($iddfinanciamiento);
                            $detallefinanciamiento->eliminarsinRedireccion();
                            header("Location: /dfinanciamiento/crear?resultado=4");
                            exit();
                        }
                    }
                }
                if (!isset($dfid)) {
                    // Instanciamos un nuevo objeto recibiendo los datos enviados desde el formulario (cards)
                    $detallefinanciamiento = new DetalleFinanciamiento($_POST['detalle_financiamiento']);
                    //Verificamos si no tenemos errores
                    $errores = $detallefinanciamiento->validar();
                    if (empty($errores)) {
                        //Si no existen errores creamos el registro de los datos enviados por la card
                        $resultado = $detallefinanciamiento->guardarsinRedireccion();
                        //Redireccionamos con el resultado 1 que nos indica que se ha creado con éxito
                        header("Location: /dfinanciamiento/crear?resultado=1");
                        exit();
                    }
                }
            }
        }
        //Renderizamos y enviamos los objetos recibidos
        $router->render('dfinanciamiento/crear', [
            'programas' => $programas,
            'fuentesfinanciamiento' => $fuentesfinanciamiento,
            'detallefinanciamiento' => $detallefinanciamiento,
            'errores' => $errores,
            'resuls' => $resuls,
            'resultado' => $resultado
        ]);
    }
}
