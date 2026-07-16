<?php

namespace Controllers;

use MVC\Router;
use Model\Programa;
use Model\Rendicion;
use Model\TipoCambio;
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
use Model\Usuario;
use Model\Poa;
use Model\ReporteEgresosRendiciones;

class ReportePoaRubrosController
{
    /**
     * Identificador corto del usuario para nombrar los archivos de reporte.
     * (El código de usuario `descripcion` se retiró en la migr. 024: se usa la
     * parte local del email.)
     */
    private static function codigoUsuario(): string
    {
        $usuario = Usuario::find($_SESSION['id']);
        $email = (string) ($usuario->email ?? '');
        $local = strstr($email, '@', true);
        return $local !== false && $local !== '' ? $local : 'usuario';
    }

    /**
     * TC vigentes AL CIERRE (hoy) para convertir la planificación (rubros/POA),
     * que no tiene fecha de operación (§2.5 del plan de montos). Devuelve
     * [TipoCambio|null USD, TipoCambio|null EUR] — null sin cobertura: el reporte
     * muestra "—" en vez de reventar (antes: DivisionByZeroError con 0 registros).
     */
    private static function tcCierre(): array
    {
        $hoy = date('Y-m-d');
        return [TipoCambio::vigente('USD', $hoy), TipoCambio::vigente('EUR', $hoy)];
    }
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
        $usrcod = self::codigoUsuario();
        // Obtenemos todos los IDs de programa como enteros
        $programas = Programa::all();

        // Obtenemos todos los registros de ReportePoaRubros
        $resbienes = ReportePoaRubros::all();

        // Obtenemos los demás datos requeridos
        $sumas = ReportePoaRubrosSumas::all();
        [$tcdolar, $tceuro] = self::tcCierre();

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
        $usrcod = self::codigoUsuario();
        // Obtenemos todos los IDs de programa como enteros
        $programas = Programa::all();

        // Obtenemos todos los registros de ReportePoaRubros
        $resbienes = ReportePoaRubros::all();

        // Obtenemos los demás datos requeridos
        $sumas = ReportePoaRubrosSumas::all();
        $rendiciones = RendicionFuentesVista::all();
        [$tcdolar, $tceuro] = self::tcCierre();
        // (RendicionFuentesCantidadVista retirada: consultaba la vista
        //  cantidad_fuentes_rendicion, eliminada en la migr. 009, y su resultado
        //  no se usaba en ninguna vista — solo ensuciaba el error_log.)
        $fuentes = ReporteFuentesProgramaVista::all();
        $router->render('reporte/poarendicion', [
            'tcdolar'    => $tcdolar,
            'programas'  => $programas,
            'tceuro'     => $tceuro,
            'resbienes'  => $resbienes,
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
        $usrcod = self::codigoUsuario();
        // Obtenemos todos los IDs de programa como enteros
        $programas = Programa::all();

        // Obtenemos todos los registros de ReportePoaRubros
        $resbienes = ReportePoaRubros::all();

        //Tomamos todas las fuentes de financiamiento disponibles
        $fuentes = ReporteFuentesProgramaVista::all();

        //Tomamos todas las rendiciones para el reporte
        $rendiciones = RendicionFuentesVista::all();
        // Obtenemos los demás datos requeridos
        $sumas = ReportePoaRubrosSumas::all();
        [$tcdolar, $tceuro] = self::tcCierre();

        $router->render('reporte/poarubros', [
            'tcdolar'    => $tcdolar,
            'tceuro'     => $tceuro,
            'fuentes'    => $fuentes,
            'programas'  => $programas,
            'resbienes'  => $resbienes,
            'sumas'      => $sumas,
            'rendiciones' => $rendiciones,
            'usrcod'  => $usrcod
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
        $usrcod = self::codigoUsuario();
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
        $usrcod = self::codigoUsuario();

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
        $rootPath = dirname(__DIR__);
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

    // Guardar el presupuesto calculado en el documento POA Presupuestal del programa.
    // Upsert por programa/año en estado Borrador: NO cambia el estado del documento
    // (las transiciones van por el flujo /poa/enviar|observar|aprobar) ni duplica.
    // Respeta el bloqueo: si el POA ya está Enviado/Aprobado, el coordinador no edita.
    public static function indexguardarpoa(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /poa/admin');
            exit;
        }
        $programaId = $_SESSION['programa_id'] ?? null;
        if (!$programaId) {
            header('Location: /poa/admin');
            exit;
        }
        // Bloqueo si el POA Presupuestal ya fue enviado/aprobado (solo coordinador).
        exigirPoaPresupuestalEditable($programaId);

        $anio  = date('Y');
        $monto = (float) ($_POST['monto'] ?? 0);

        $poa = Poa::porProgramaAnio($programaId, $anio);
        if (!$poa) {
            $poa = new Poa([
                'programa_id' => $programaId,
                'usuario_id'  => $_SESSION['id'],
                'anio'        => $anio,
                'presupuesto' => $monto,
                'estado'      => Poa::BORRADOR
            ]);
        } else {
            $poa->presupuesto = $monto;
        }

        $poa->validar();
        $errores = Poa::getErrores();
        if (empty($errores)) {
            Poa::setUsuarioActual();
            $poa->guardarsinRedireccion();
            header('Location: /poa/admin?resultado=2');
            exit;
        }
        header('Location: /poa/admin?resultado=12');
        exit;
    }

    // Deprecado: el cambio de estado del POA Presupuestal ahora se realiza por el flujo
    // documental (/poa/enviar, /poa/observar, /poa/aprobar) con validación de estados.
    public static function updateguardarpoa(Router $router)
    {
        header('Location: /poa/admin');
        exit;
    }
}
