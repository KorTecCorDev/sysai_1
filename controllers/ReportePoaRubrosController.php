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

        [$tcdolar, $tceuro] = self::tcCierre();

        $router->render('reporte/poa', [
            'tcdolar'    => $tcdolar,
            'tceuro'     => $tceuro,
            'programas'  => $programas,
            'resbienes'  => $resbienes,
            'usrcod'  => $usrcod,
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

        // Rendiciones APROBADAS del año, por rubro×fuente (migr. 034).
        $rendiciones = RendicionFuentesVista::all();
        [$tcdolar, $tceuro] = self::tcCierre();
        $fuentes = ReporteFuentesProgramaVista::all();
        $router->render('reporte/poarendicion', [
            'tcdolar'    => $tcdolar,
            'programas'  => $programas,
            'tceuro'     => $tceuro,
            'resbienes'  => $resbienes,
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

        //Tomamos todas las rendiciones para el reporte (aprobadas del año, por rubro — migr. 034)
        $rendiciones = RendicionFuentesVista::all();
        [$tcdolar, $tceuro] = self::tcCierre();

        $router->render('reporte/poarubros', [
            'tcdolar'    => $tcdolar,
            'tceuro'     => $tceuro,
            'fuentes'    => $fuentes,
            'programas'  => $programas,
            'resbienes'  => $resbienes,
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

    // Retirado el 2026-08-14 (Fase 0 del plan de reportes): indexdescarga()
    // (GET /descargar) servía `views/reporte/storage/reports/$_GET['rprt']`
    // concatenando el parámetro del cliente SIN SANEAR. Verificado: con
    // `?rprt=../../../../.env` devolvía el .env —App Password de Gmail y
    // credenciales de BD— a cualquier usuario autenticado; la ruta estaba
    // registrada en los tres roles, así que un coordinador también podía. De
    // paso esquivaba el bloqueo del .htaccess, porque leía el archivo PHP y no
    // Apache, y su rama de error imprimía la ruta absoluta del servidor.
    // Los cinco reportes ahora hacen streaming con descargarXlsx(): ya no se
    // escribe nada en disco, así que no hay archivo que servir ni ruta que
    // recorrer. Ver docs/plan-reportes-ingresos-y-rendiciones.md §4 Fase 0.

    // Retirados el 2026-08-13: indexguardarpoa() (POST /reporte/guardarpoa) y
    // updateguardarpoa() (POST /reporte/modificarpoa, ya deprecado a no-op). Los
    // alimentaba el modal "Guardar POA" de views/reporte/poa.php, que escribía el
    // presupuesto del documento desde un monto posteado por el navegador. Hoy esa
    // cifra la calcula el servidor: Poa::presupuestoCalculado() al iniciar el POA
    // (/poa/crear) y de nuevo al congelarlo en /poa/enviar.
}
