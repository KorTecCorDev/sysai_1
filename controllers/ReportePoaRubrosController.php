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
        // Obtenemos todos los IDs de programa como enteros
        $programas = Programa::all();

        // Obtenemos todos los registros de ReportePoaRubros
        $resbienes = ReportePoaRubros::all();

        // Obtenemos los demás datos requeridos
        $sumas = ReportePoaRubrosSumas::all();
        $tcdolar = TipoCambioDolar::findlast();
        $tceuro = TipoCambioEuro::findlast();

        $router->render('reporte/poarubros', [
            'tcdolar'    => $tcdolar,
            'tceuro'     => $tceuro,
            'programas'  => $programas,
            'resbienes'  => $resbienes,
            'sumas'      => $sumas
        ]);
    }

    public static function indexreporterendiciones(Router $router)
    {
        // Obtenemos los datos del usuario para colocar los nombres de los reportes
        $usuarioid = $_SESSION['id'];
        $usuario = Usuario::find($usuarioid);
        $usrcod = $usuario->descripcion;
        $fuentes = FuenteFinanciamiento::all();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Captamos las fechas de inicio y fin
            $fechainicio = $_POST['fechainicio'];
            $fechafin = $_POST['fechafin'];
            // Filtramos los resultados de acuerdo a las fechas
            $resreporterendiciones = ReporteRendicionesVista::findporRango('rendicion_fecha', $fechainicio, $fechafin);
            $resreporteegresos = ReporteEgresosVista::findporRango('otros_ingresos_egresos_fecha', $fechainicio, $fechafin);
            //En este caso no existe $formulario
            $formulario = '';
        } else {
            $resreporterendiciones = ReporteRendicionesVista::all();
            $resreporteegresos = ReporteEgresosVista::all();
            $formulario = '<form method="POST" action="" class="p-4 bg-light rounded shadow-sm" style="max-width: 350px;">
                            <h5 class="fw-bold mb-3">Filtrar Reportes</h5>

                            <div class="mb-3">
                                <label for="fecha_inicio" class="form-label fw-semibold">Fecha de Inicio</label>
                                <input type="date" name="fechainicio" id="fecha_inicio" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="fecha_fin" class="form-label fw-semibold">Fecha de Fin</label>
                                <input type="date" name="fechafin" id="fecha_fin" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-file-earmark-text"></i> Generar Reporte
                            </button>
                            </form>';
        }

        $router->render('reporte/rendiciones', [
            'resreporterendiciones' => $resreporterendiciones,
            'resreporteegresos' => $resreporteegresos,
            'fuentes' => $fuentes,
            'usrcod' => $usrcod,
            'formulario' => $formulario
        ]);
    }
    public static function indexreporteingresos(Router $router)
    {
        // Obtenemos los datos del usuario para colocar los nombres de los reportes
        $usuarioid = $_SESSION['id'];
        $usuario = Usuario::find($usuarioid);
        $usrcod = $usuario->descripcion;
        // En caso haya un POST (Envío de fechas por parte del usuario)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Captamos las fechas de inicio y fin
            $fechainicio = $_POST['fechainicio'];
            $fechafin = $_POST['fechafin'];
            //En este caso no existe $formulario
            $formulario = '';
            // Filtramos los resultados de acuerdo a las fechas
            $resreportefuentes = ReporteFuentesVista::findporRango('fuente_fecha', $fechainicio, $fechafin);
            $resreporteingresos = ReporteIngresosVista::findporRango('otros_ingresos_egresos_fecha', $fechainicio, $fechafin);
        } else {
            $resreportefuentes = ReporteFuentesVista::all();
            $resreporteingresos = ReporteIngresosVista::all();
            $formulario = '<form method="POST" action="" class="p-4 bg-light rounded shadow-sm" style="max-width: 350px;">
                            <h5 class="fw-bold mb-3">Filtrar Reportes</h5>

                            <div class="mb-3">
                                <label for="fecha_inicio" class="form-label fw-semibold">Fecha de Inicio</label>
                                <input type="date" name="fechainicio" id="fecha_inicio" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="fecha_fin" class="form-label fw-semibold">Fecha de Fin</label>
                                <input type="date" name="fechafin" id="fecha_fin" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-file-earmark-text"></i> Generar Reporte
                            </button>
                            </form>';
        }
        $router->render('reporte/ingresos', [
            'resreportefuentes' => $resreportefuentes,
            'resreporteingresos' => $resreporteingresos,
            'usrcod' => $usrcod,
            'formulario' => $formulario
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
        //Seleccionamos el tipo de reporte a descargar
        $reporte = $_GET['rprt'];
        //Renderizamos el tipo de reporte (descripcion) y el código de usuario
        $router->render('descargar_reporte', [
            'rprt' => $reporte
        ]);
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
}
