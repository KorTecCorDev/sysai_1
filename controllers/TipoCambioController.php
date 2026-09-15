<?php

namespace Controllers;

use MVC\Router;
use Model\TipoCambio;

/**
 * Tipos de cambio (migr. 028, plan de montos Fase 2). La moneda es un DATO
 * (?moneda=USD|EUR), no un nombre de método: un solo CRUD para USD y EUR.
 * Solo Admin/Contador (las rutas viven en iadmin.php e iconta.php).
 *
 * Fix heredado: el módulo viejo redirigía con resultado=1 aunque el guardado
 * fallara; aquí solo se redirige con éxito cuando el guardado ocurrió.
 */
class TipoCambioController
{
    /** Moneda pedida (GET/POST), acotada al catálogo. Default USD. */
    private static function moneda(): string
    {
        $moneda = strtoupper(trim($_POST['moneda'] ?? $_GET['moneda'] ?? 'USD'));
        return in_array($moneda, TipoCambio::MONEDAS, true) ? $moneda : 'USD';
    }

    public static function index(Router $router)
    {
        exigirRol([1, 2]);
        $moneda = self::moneda();
        $router->render('tcambio/admin', [
            'moneda'       => $moneda,
            'tiposcambio'  => TipoCambio::porMoneda($moneda),
            'resultado'    => $_GET['resultado'] ?? null
        ]);
    }

    public static function crear(Router $router)
    {
        exigirRol([1, 2]);
        $moneda = self::moneda();
        // Pre-llenado opcional vía GET (botón "traer de SBS", Fase 6): solo puebla el
        // formulario — el Contador revisa y GUARDA; nada se registra automáticamente.
        $tipocambio = new TipoCambio([
            'moneda'         => $moneda,
            'fecha_vigencia' => $_GET['fecha_vigencia'] ?? date('Y-m-d'),
            'compra'         => $_GET['compra'] ?? '',
            'venta'          => $_GET['venta'] ?? '',
        ]);
        $errores = TipoCambio::getErrores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipocambio = new TipoCambio($_POST);
            $tipocambio->moneda = $moneda;           // la moneda la fija la URL/route, no el usuario
            $tipocambio->usuario_id = $_SESSION['id'];
            // origen SBS solo cuando el formulario vino pre-llenado por la consulta;
            // cualquier otro valor cae a MANUAL.
            $tipocambio->origen = (($_POST['origen'] ?? '') === 'SBS') ? 'SBS' : 'MANUAL';
            $errores = $tipocambio->validar();
            if (empty($errores)) {
                $vali = TipoCambio::setUsuarioActual();
                if ($vali) {
                    $guardado = $tipocambio->guardarsinRedireccion();
                    if ($guardado) {
                        header('Location: /tcambio/admin?moneda=' . $moneda . '&resultado=1');
                        exit();
                    }
                    $errores[] = 'No se pudo guardar el tipo de cambio.';
                } else {
                    $errores[] = 'Error al asignar el usuario actual.';
                }
            }
        }

        $router->render('tcambio/crear', [
            'moneda'     => $moneda,
            'tipocambio' => $tipocambio,
            'sbs'        => $_GET['sbs'] ?? null,   // ok | fail (resultado de la consulta SBS)
            'errores'    => $errores
        ]);
    }

    /**
     * Consulta la tasa SBS (Fase 6 — informativo) y redirige al formulario de crear
     * con los campos PRE-LLENADOS. La dispara el Contador con el botón; nunca corre
     * en la ruta de un reporte y nunca guarda sola. Si el servicio no responde o no
     * está configurado, se degrada limpio: formulario vacío + aviso "ingresa manual".
     */
    public static function sbs(Router $router)
    {
        exigirRol([1, 2]);
        $moneda = self::moneda();
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $fecha)) {
            $fecha = date('Y-m-d');
        }
        $tasa = TipoCambio::consultarSbs($moneda, $fecha);
        if ($tasa) {
            header('Location: /tcambio/crear?moneda=' . $moneda
                . '&fecha_vigencia=' . rawurlencode($fecha)
                . '&compra=' . rawurlencode((string) $tasa['compra'])
                . '&venta=' . rawurlencode((string) $tasa['venta'])
                . '&sbs=ok');
            exit();
        }
        header('Location: /tcambio/crear?moneda=' . $moneda
            . '&fecha_vigencia=' . rawurlencode($fecha) . '&sbs=fail');
        exit();
    }

    public static function actualizar(Router $router)
    {
        exigirRol([1, 2]);
        $moneda = self::moneda();
        $id = validarORedireccionar('/tcambio/admin?moneda=' . $moneda);
        $tipocambio = TipoCambio::find($id);
        if (!$tipocambio) {
            header('Location: /tcambio/admin?moneda=' . $moneda);
            exit();
        }
        $moneda = strtoupper((string) $tipocambio->moneda);
        $errores = TipoCambio::getErrores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Solo los datos editables; moneda e id no se reasignan vía POST.
            $tipocambio->fecha_vigencia = $_POST['fecha_vigencia'] ?? $tipocambio->fecha_vigencia;
            $tipocambio->compra = $_POST['compra'] ?? $tipocambio->compra;
            $tipocambio->venta = $_POST['venta'] ?? $tipocambio->venta;
            $errores = $tipocambio->validar();
            if (empty($errores)) {
                $vali = TipoCambio::setUsuarioActual();
                if ($vali) {
                    $guardado = $tipocambio->guardarsinRedireccion();
                    if ($guardado) {
                        header('Location: /tcambio/admin?moneda=' . $moneda . '&resultado=2');
                        exit();
                    }
                    $errores[] = 'No se pudo actualizar el tipo de cambio.';
                } else {
                    $errores[] = 'Error al asignar el usuario actual.';
                }
            }
        }

        $router->render('tcambio/actualizar', [
            'moneda'     => $moneda,
            'tipocambio' => $tipocambio,
            'errores'    => $errores
        ]);
    }

    public static function eliminar(Router $router)
    {
        exigirRol([1, 2]);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $tipocambio = $id ? TipoCambio::find($id) : null;
            if ($tipocambio) {
                $moneda = strtoupper((string) $tipocambio->moneda);
                TipoCambio::setUsuarioActual();
                // Congelamiento (§2.6): borrar un TC no toca las conversiones ya copiadas
                // en rendiciones/OIE — por eso se permite eliminar sin más guarda.
                $tipocambio->eliminarsinRedireccion();
                header('Location: /tcambio/admin?moneda=' . $moneda . '&resultado=3');
                exit();
            }
        }
        header('Location: /tcambio/admin');
        exit();
    }
}
