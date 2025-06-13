<?php

namespace Controllers;

use MVC\Router;
use Model\Programa;
use Model\Rendicion;
use Model\TipoCambioEuro;
use Model\TipoCambioDolar;
use Model\ReportePoaRubros;
use Model\ReporteEgresosVista;
use Model\ReporteFuentesVista;
use Model\FuenteFinanciamiento;
use Model\ReporteIngresosVista;
use Model\RendicionFuentesVista;
use Model\ReportePoaRubrosSumas;
use Model\ReporteRendicionesVista;
use Model\UsuarioDisponiblePrograma;
use Model\ReporteFuentesProgramaVista;
use Model\RendicionFuentesCantidadVista;
use Model\Usuario;
use Model\Poa;
use Model\ReporteEgresosRendiciones;

class ReportePoaRubrosController
{
    public static function index(Router $router)
    {
        // Obtenemos los datos del usuario para colocar los nombres de los reportes
        $usuarioid = $_SESSION['id'];
        //En caso de ser un coordinador, captamos el id del poa
        if (isset($_SESSION['poa_id'])) {
            $poaid = $_SESSION['poa_id'];
        } else {
            $poaid = null;
        }
        $usuario = Usuario::find($usuarioid);
        $usrcod = $usuario->descripcion;
        // Obtenemos todos los IDs de programa como enteros
        $programas = Programa::all();

        // Obtenemos todos los registros de ReportePoaRubros
        $resbienes = ReportePoaRubros::all();

        // Obtenemos los demás datos requeridos
        $sumas = ReportePoaRubrosSumas::all();
        $tcdolar = TipoCambioDolar::findlast();
        $tceuro = TipoCambioEuro::findlast();

        $router->render('reporte/poa', [
            'tcdolar'    => $tcdolar,
            'tceuro'     => $tceuro,
            'programas'  => $programas,
            'resbienes'  => $resbienes,
            'usrcod'  => $usrcod,
            'sumas'      => $sumas,
            'poaid'      => $poaid
        ]);
    }

    public static function indexrendicion(Router $router)
    {
        // Obtenemos los datos del usuario para colocar los nombres de los reportes
        $usuarioid = $_SESSION['id'];
        $usuario = Usuario::find($usuarioid);
        $usrcod = $usuario->descripcion;
        // Obtenemos todos los IDs de programa como enteros
        $programas = Programa::all();

        // Obtenemos todos los registros de ReportePoaRubros
        $resbienes = ReportePoaRubros::all();

        // Obtenemos los demás datos requeridos
        $sumas = ReportePoaRubrosSumas::all();
        $rendiciones = RendicionFuentesVista::all();
        $tcdolar = TipoCambioDolar::findlast();
        $tceuro = TipoCambioEuro::findlast();
        $ffnro   = RendicionFuentesCantidadVista::all();
        $fuentes = ReporteFuentesProgramaVista::all();
        $router->render('reporte/poarendicion', [
            'tcdolar'    => $tcdolar,
            'programas'  => $programas,
            'tceuro'     => $tceuro,
            'resbienes'  => $resbienes,
            'ffnro'      => $ffnro,
            'sumas'      => $sumas,
            'fuentes'    => $fuentes,
            'usrcod'  => $usrcod,
            'rendiciones' => $rendiciones
        ]);
    }


    public static function indexrubro(Router $router)
    {
        //Esta función es para el reporte de rubros con rendiciones exclusivo para los usuarios administrador y contador
        // Obtenemos los datos del usuario para colocar los nombres de los reportes
        $usuarioid = $_SESSION['id'];
        $usuario = Usuario::find($usuarioid);
        $usrcod = $usuario->descripcion;
        // Obtenemos todos los IDs de programa como enteros
        $programas = Programa::all();

        // Obtenemos todos los registros de ReportePoaRubros
        $resbienes = ReportePoaRubros::all();

        //Tomamos todas las fuentes de financiamiento disponibles
        $fuentes = ReporteFuentesProgramaVista::all();

        //Tomamos todas las rendiciones para el reporte
        $rendiciones = RendicionFuentesVista::all();
        //Tomamos la cantidad de fuentes de financiamiento por rendición
        $ffnro   = RendicionFuentesCantidadVista::all();
        // Obtenemos los demás datos requeridos
        $sumas = ReportePoaRubrosSumas::all();
        $tcdolar = TipoCambioDolar::findlast();
        $tceuro = TipoCambioEuro::findlast();

        $router->render('reporte/poarubros', [
            'tcdolar'    => $tcdolar,
            'tceuro'     => $tceuro,
            'fuentes'    => $fuentes,
            'programas'  => $programas,
            'resbienes'  => $resbienes,
            'sumas'      => $sumas,
            'rendiciones' => $rendiciones,
            'usrcod'  => $usrcod,
            'ffnro'      => $ffnro
        ]);
    }

    public static function indexreporterendiciones(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Captamos las fechas de inicio y fin
            $fechainicio = $_POST['fechainicio'];
            $fechafin = $_POST['fechafin'];
            // Redirige con fechas como parámetros GET
            header("Location: /reporte/rendicionesdesc?fechainicio=" . urlencode($fechainicio) . "&fechafin=" . urlencode($fechafin));
            exit;
        }

        $router->render('reporte/rendiciones', []);
    }
    public static function indexreporterendicionesdescargar(Router $router)
    {
        // Validación simple de parámetros
        if (!isset($_GET['fechainicio']) || !isset($_GET['fechafin'])) {
            header("Location: /reporte/rendiciones");
            exit;
        }
        // Captamos las fechas de inicio y fin
        $fechainicio = $_GET['fechainicio'];
        $fechafin = $_GET['fechafin'];
        // Obtenemos los datos del usuario para colocar los nombres de los reportes
        $usuarioid = $_SESSION['id'];
        $usuario = Usuario::find($usuarioid);
        $usrcod = $usuario->descripcion;
        $fuentes = FuenteFinanciamiento::all();
        // Filtramos los resultados de acuerdo a las fechas
        $resreporterendiciones = ReporteRendicionesVista::findporRango('rendicion_fecha', $fechainicio, $fechafin);
        $resreporteegresos = ReporteEgresosVista::findporRango('otros_ingresos_egresos_fecha', $fechainicio, $fechafin);
        $router->render('reporte/rendicionesdesc', [
            'resreporterendiciones' => $resreporterendiciones,
            'resreporteegresos' => $resreporteegresos,
            'fuentes' => $fuentes,
            'usrcod' => $usrcod
        ]);
    }

    public static function indexreporteingresos(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fechainicio = $_POST['fechainicio'];
            $fechafin = $_POST['fechafin'];

            // Redirige con fechas como parámetros GET
            header("Location: /reporte/ingresosdesc?fechainicio=" . urlencode($fechainicio) . "&fechafin=" . urlencode($fechafin));
            exit;
        }

        // Renderiza el formulario si no hay POST
        $router->render('reporte/ingresos', []);
    }

    public static function indexreporteingresosdescargar(Router $router)
    {
        // Validación simple de parámetros
        if (!isset($_GET['fechainicio']) || !isset($_GET['fechafin'])) {
            header("Location: /reporte/ingresos");
            exit;
        }

        $fechainicio = $_GET['fechainicio'];
        $fechafin = $_GET['fechafin'];

        // Datos del usuario para nombrar el archivo
        $usuarioid = $_SESSION['id'];
        $usuario = Usuario::find($usuarioid);
        $usrcod = $usuario->descripcion;

        // Obtener los datos del reporte
        $resreportefuentes = ReporteFuentesVista::findporRango('fuente_fecha', $fechainicio, $fechafin);
        $resreporteingresos = ReporteIngresosVista::findporRango('oie_comprobante_fecha_original', $fechainicio, $fechafin);

        // Renderizar la vista que genera y guarda el archivo
        $router->render('reporte/ingresosdesc', [
            'resreportefuentes' => $resreportefuentes,
            'resreporteingresos' => $resreporteingresos,
            'usrcod' => $usrcod
        ]);
    }

    public static function indexsaldos(Router $router)
    {
        $resreportefuentes = ReporteFuentesVista::all();
        $resreporteingresos = ReporteIngresosVista::all();

        $router->render('saldos_contables/saldos', []);
    }

    public static function indexdescarga(Router $router)
    {
        if (!isset($_GET['rprt'])) {
            echo "Nombre del archivo no especificado.";
            exit;
        }
        $filename = $_GET['rprt'];
        $rootPath = dirname(__DIR__); // /SysAi_1
        $fullPath = $rootPath . "/views/reporte/storage/reports/" . $filename;
        if (file_exists($fullPath)) {
            // Limpia cualquier salida previa
            if (ob_get_length()) ob_end_clean();

            header('Content-Description: File Transfer');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fullPath));

            flush();
            readfile($fullPath);
            exit;
        } else {
            echo "Archivo no encontrado: $fullPath";
            exit;
        }
    }

    public static function indexguardarpoa(Router $router)
    {
        //Comenzamos por validar el id recibido mediante GET y verificamos que sea un id que exista
        if (isset($_GET['id'])) {
            $id = validarORedireccionar('resultado/admin');
        }
        //Capatamos el monto de presupuesto calculado para el POA, se encuentra en el POST
        $monto = $_POST['monto'];
        $poa = new Poa();
        //Creamos una variable de argumentos momentáneos del poa y le asignamos el monto
        //Debe de tener el key "presupuesto" para poder sincronizarlo con el objeto poa correctamente
        $argspoa['presupuesto'] = $monto;
        //Asignamos como argumento el id del registro de la tabla poa
        $argspoa['id'] = $id;
        //Cambiamos el estado de 0  a 1 para que el poa quede como "Completado"
        $argspoa['estado'] = 1;
        //Captamos los datos restantes necesarios para la modificación del registro poa
        $argspoa['programa_id'] = $_SESSION['programa_id'];
        $argspoa['usuario_id'] = $_SESSION['id'];
        //Sincronizamos los argspoa con el objeto poa creado previamente
        $poa->sincronizar($argspoa);

        //Validamos errores
        $poa->validar();
        $errores = Poa::getErrores();
        //Si no hay errores, procedemos a guardar el registro
        if (empty($errores)) {
            //Guardamos el registro en la base de datos
            $poa->guardarsinRedireccion();
            //Redireccionamos a la vista de resultados
            header('Location: /resultado/admin?resultado=6');
            exit;
        }
        //Si hay errores, los mostramos en la vista de guardar poa
        $router->render('reporte/guardarpoa', [
            'errores' => $errores,
            'poa' => $poa
        ]);
    }
    public static function updateguardarpoa(Router $router)
    {
        //Comenzamos por validar el id recibido mediante GET y verificamos que sea un id que exista
        if (isset($_GET['id'])) {
            $id = validarORedireccionar('resultado/admin');
        }
        //Si es un POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Creamos los argumentos momentáneos del poa antes de registrarlo
            $argspoa['id'] = $_GET['id'];
            //Captamos el cambio de estado del poa
            $argspoa['estado'] = $_POST['estado'];
            //Captamos el objeto poa a modificar
            $poa = Poa::find($argspoa['id']);
            //Sincronizamos los argspoa con el objeto poa creado previamente
            $poa->sincronizar($argspoa);
            //Validamos errores
            $poa->validar();
            $errores = Poa::getErrores();
            //Si no hay errores, procedemos a guardar el registro
            if (empty($errores)) {
                //Insertando la acción de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = ReportePoaRubros::setUsuarioActual();
                //Si es true...
                if ($vali) {
                    //Guardamos el registro en la base de datos
                    $poa->guardarsinRedireccion();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
                //Redireccionamos a la vista de resultados
                header('Location: /resultado/admin?resultado=6');
                exit;
            }
        }
        //Si hay errores, los mostramos en la vista de guardar poa
        $router->render('reporte/modificarpoa', [
            'errores' => $errores,
            'poa' => $poa
        ]);
    }
}
