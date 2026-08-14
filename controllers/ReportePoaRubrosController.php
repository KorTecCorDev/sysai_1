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

    /**
     * Valida el rango de fechas de un reporte.
     *
     * Antes se leía $_POST['fechainicio'] a pelo (warning si faltaba) y no se
     * comprobaba nada más: un rango invertido devolvía una hoja vacía sin decir
     * por qué. Devuelve [desde, hasta] o null si el rango no sirve.
     */
    private static function rangoValido($desde, $hasta): ?array
    {
        $desde = is_string($desde) ? trim($desde) : '';
        $hasta = is_string($hasta) ? trim($hasta) : '';
        foreach ([$desde, $hasta] as $f) {
            $d = \DateTime::createFromFormat('Y-m-d', $f);
            if (!$d || $d->format('Y-m-d') !== $f) {
                return null;
            }
        }
        return $desde <= $hasta ? [$desde, $hasta] : null;
    }

    public static function indexreporterendiciones(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rango = self::rangoValido($_POST['fechainicio'] ?? null, $_POST['fechafin'] ?? null);
            if (!$rango) {
                header('Location: /reporte/rendiciones?resultado=29');
                exit;
            }
            header('Location: /reporte/rendicionesdesc?fechainicio=' . urlencode($rango[0]) . '&fechafin=' . urlencode($rango[1]));
            exit;
        }

        $router->render('reporte/rendiciones', ['resultado' => $_GET['resultado'] ?? null]);
    }

    public static function indexreporterendicionesdescargar(Router $router)
    {
        $rango = self::rangoValido($_GET['fechainicio'] ?? null, $_GET['fechafin'] ?? null);
        if (!$rango) {
            header('Location: /reporte/rendiciones?resultado=29');
            exit;
        }
        [$desde, $hasta] = $rango;

        // Solo APROBADAS (D3) y por FECHA DE OPERACIÓN (D4). Ver los modelos.
        $router->render('reporte/rendicionesdesc', [
            'rendiciones' => ReporteRendicionesVista::aprobadasEnRango($desde, $hasta),
            'egresos'     => ReporteEgresosVista::enRango($desde, $hasta),
            'desde'       => $desde,
            'hasta'       => $hasta,
            'usrcod'      => self::codigoUsuario(),
        ]);
    }

    public static function indexreporteingresos(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rango = self::rangoValido($_POST['fechainicio'] ?? null, $_POST['fechafin'] ?? null);
            if (!$rango) {
                header('Location: /reporte/ingresos?resultado=29');
                exit;
            }
            header('Location: /reporte/ingresosdesc?fechainicio=' . urlencode($rango[0]) . '&fechafin=' . urlencode($rango[1]));
            exit;
        }

        $router->render('reporte/ingresos', ['resultado' => $_GET['resultado'] ?? null]);
    }

    public static function indexreporteingresosdescargar(Router $router)
    {
        $rango = self::rangoValido($_GET['fechainicio'] ?? null, $_GET['fechafin'] ?? null);
        if (!$rango) {
            header('Location: /reporte/ingresos?resultado=29');
            exit;
        }
        [$desde, $hasta] = $rango;

        // Las fuentes van SIN filtrar por fecha (D2): la sección es contexto
        // —cuánto presupuesto hay detrás de los ingresos—, y `fuente_fecha` es
        // la fecha de alta del registro, que no significa nada contablemente.
        [$tcd, $tce] = self::tcCierre();
        $router->render('reporte/ingresosdesc', [
            'ingresos' => ReporteIngresosVista::enRango($desde, $hasta),
            'fuentes'  => ReporteFuentesVista::all(),
            'desde'    => $desde,
            'hasta'    => $hasta,
            'tcdolar'  => $tcd,
            'tceuro'   => $tce,
            'usrcod'   => self::codigoUsuario(),
        ]);
    }

    // Retirado el 2026-08-14: indexsaldos() no estaba registrado en ninguna ruta
    // -/saldos_contables/saldos lo sirve SaldoContableController::index- y ademas
    // consultaba dos vistas cuyo resultado tiraba: render() recibia un array vacio.

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
