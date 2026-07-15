<?php

namespace Controllers;

use MVC\Router;

use Model\Programa;
use Model\OieComprobante;
use Model\OieTipoComprobante;
use Model\FuenteFinanciamiento;
use Model\OtrosIngresosEgresos;
use Model\IngresoEgresoAdminVista;

// Otros Ingresos/Egresos (item 7): SOLO Contador (y Admin). Aprobación automática:
// el registro impacta de inmediato el presupuesto contable (las vistas de saldo
// calculan en vivo). El ingreso puede ir al total de la fuente (programa NULL) o
// al sobre de un programa; el egreso siempre descuenta de un sobre y no puede
// exceder su saldo disponible (DetalleFinanciamiento::saldoSobre).
class IngresoEgresoController
{

    // Listado
    public static function index(Router $router)
    {
        exigirRol([1, 2]);

        // Creando el array con todos los oie_tipo_comprobante ($tipocomprobantes)
        $tipocomprobantes = OieTipoComprobante::all();

        // Todos los OIE registrados (vista con programa/fuente, migr. 022)
        $oies = IngresoEgresoAdminVista::all();

        $resultado = $_GET['resultado'] ?? 0;

        //Renderizando la vista
        $router->render('ingreso_egreso/admin', [
            'tipocomprobantes' => $tipocomprobantes,
            'resultado' => $resultado,
            'oies' => $oies
        ]);
    }

    //Crear (un solo paso: datos generales + destino + comprobante)
    public static function crear(Router $router)
    {
        exigirRol([1, 2]);

        // Combos: tipo de comprobante, programas (destino) y fuentes
        $tipocomprobantes = OieTipoComprobante::all();
        $programas = Programa::all();
        $fuentes = FuenteFinanciamiento::all();

        //Creamos los objetos a insertar
        $oie_comprobante = new OieComprobante();
        $oie = new OtrosIngresosEgresos();

        //Array de errores
        $errores = OtrosIngresosEgresos::getErrores();

        //En caso se hay enviado el formulario (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $oie_comprobante = new OieComprobante($_POST['oie_comprobante'] ?? []);
            $oie = new OtrosIngresosEgresos($_POST['oie'] ?? []);

            // Validamos TODO antes de escribir (evita comprobantes huérfanos):
            // comprobante + OIE + tope del sobre (el monto vive en el comprobante)
            $oie_comprobante->validar();
            $oie->validar();
            $oie->validarTopeSobre((float) $oie_comprobante->monto);
            $errores = OtrosIngresosEgresos::getErrores();

            if (empty($errores)) {
                //Insertando la accion de audi para el usuario actual
                if (OieComprobante::setUsuarioActual()) {
                    //Guardamos el comprobante y luego el OIE apuntando a él
                    if ($oie_comprobante->guardarsinRedireccion()) {
                        $ultimo = OieComprobante::findlast();
                        $oie->oie_comprobante_id = $ultimo->id;
                        if ($oie->guardarsinRedireccion()) {
                            header('Location: /ingreso_egreso/admin?resultado=1');
                            exit();
                        }
                        // El OIE no se guardó: retiramos el comprobante para no dejarlo huérfano
                        $oie_comprobante->id = $ultimo->id;
                        $oie_comprobante->eliminarsinRedireccion();
                        $errores[] = 'No se pudo registrar el ingreso/egreso. Intente nuevamente.';
                    } else {
                        $errores[] = 'No se pudo registrar el comprobante. Intente nuevamente.';
                    }
                } else {
                    $errores[] = 'Error al asignar el usuario actual.';
                }
            }
        }

        //Renderizando la vista
        $router->render('ingreso_egreso/crear', [
            'tipocomprobantes' => $tipocomprobantes,
            'oie' => $oie,
            'oie_comprobante' => $oie_comprobante,
            'programas' => $programas,
            'fuentes' => $fuentes,
            'errores' => $errores
        ]);
    }

    //Actualizar
    public static function actualizar(Router $router)
    {
        exigirRol([1, 2]);

        //Captamos el oie_id
        $oie_id = intval($_GET['id'] ?? 0);
        // Encontramos el registro oie a actualizar
        $oie = OtrosIngresosEgresos::find($oie_id);
        if (!$oie) {
            header('Location: /ingreso_egreso/admin');
            exit();
        }

        //Encontramos el registro oie_comprobante a actualizar
        $oie_comprobante = OieComprobante::find($oie->oie_comprobante_id);
        if (!$oie_comprobante) {
            header('Location: /ingreso_egreso/admin');
            exit();
        }

        // Combos: tipo de comprobante, programas (destino) y fuentes
        $tipocomprobantes = OieTipoComprobante::all();
        $programas = Programa::all();
        $fuentes = FuenteFinanciamiento::all();

        //Array de errores
        $errores = OtrosIngresosEgresos::getErrores();

        //En caso se hay enviado el formulario (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sincronizamos los valores del post con los objetos a actualizar
            $oie_comprobante->sincronizar($_POST['oie_comprobante'] ?? []);
            $oie->sincronizar($_POST['oie'] ?? []);
            // El select de programa envía '' cuando el ingreso va al total de la fuente
            if (($oie->programa_id ?? '') === '') {
                $oie->programa_id = null;
            }

            // Validamos TODO antes de escribir (el tope excluye este mismo OIE)
            $oie_comprobante->validar();
            $oie->validar();
            $oie->validarTopeSobre((float) $oie_comprobante->monto);
            $errores = OtrosIngresosEgresos::getErrores();

            if (empty($errores)) {
                //Insertando la accion de audi para el usuario actual
                if (OieComprobante::setUsuarioActual()) {
                    $resultadoComprobante = $oie_comprobante->guardarsinRedireccion();
                    $resultadoOie = $oie->guardarsinRedireccion();
                    if ($resultadoComprobante && $resultadoOie) {
                        header('Location: /ingreso_egreso/admin?resultado=2');
                        exit();
                    }
                    $errores[] = 'No se pudo actualizar el registro. Intente nuevamente.';
                } else {
                    $errores[] = 'Error al asignar el usuario actual.';
                }
            }
        }

        //Renderizando la vista
        $router->render('ingreso_egreso/actualizar', [
            'tipocomprobantes' => $tipocomprobantes,
            'oie' => $oie,
            'oie_comprobante' => $oie_comprobante,
            'programas' => $programas,
            'fuentes' => $fuentes,
            'errores' => $errores
        ]);
    }

    //Eliminar (borra el OIE y su comprobante asociado)
    public static function eliminar()
    {
        exigirRol([1, 2]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Verificamos que el dato enviado sea del tipo correcto
            $oieid = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($oieid && validarTipoContenido($_POST['tipo'] ?? '')) {
                $oie = OtrosIngresosEgresos::find($oieid);
                if ($oie) {
                    //Insertando la accion de audi para el usuario actual
                    if (OieComprobante::setUsuarioActual()) {
                        $comprobante = OieComprobante::find($oie->oie_comprobante_id);
                        $oie->eliminarsinRedireccion();
                        if ($comprobante) {
                            $comprobante->eliminarsinRedireccion();
                        }
                        header('Location: /ingreso_egreso/admin?resultado=3');
                        exit();
                    }
                }
            }
            header('Location: /ingreso_egreso/admin');
            exit();
        }
    }
}
