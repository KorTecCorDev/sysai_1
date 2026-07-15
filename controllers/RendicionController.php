<?php

namespace Controllers;

use Model\Rubro;
use Model\Rendicion;
use Model\FuenteActividadVista;
use Model\RendicionAdminVista;
use Model\TipoComprobante;
use MVC\Router;

class RendicionController
{
    /**
     * Bloqueo: si el POA Presupuestal del programa (al que cuelga el rubro) está
     * Enviado o Aprobado, el coordinador NO puede registrar/editar/eliminar
     * rendiciones — es el mismo candado que congela los rubros. Contador/Admin
     * pasan (modifican como adenda). Si bloquea, hace el redirect y devuelve true.
     */
    private static function poaPresupuestalBloqueaRendicion(int $rubro_id, ?int $actividad_id): bool
    {
        if (esCoordinador() && !poaPresupuestalEditable(programaIdPorActividad($actividad_id))) {
            header("Location: /rendicion/admin?rubro_id={$rubro_id}&resultado=18");
            return true;
        }
        return false;
    }

    // Listado de rendiciones imputadas a un RUBRO. Muestra monto del rubro,
    // total ya rendido y saldo disponible (regla: Σ rendiciones ≤ monto del rubro).
    public static function index(Router $router)
    {
        $resultado = validarORedireccionarDosParametros("resultado/admin", "rubro_id", "resultado");
        $rubro_id = (int) ($_GET['rubro_id'] ?? 0);
        $rubro = Rubro::find($rubro_id);
        if (!$rubro) {
            header('Location: /resultado/admin');
            exit();
        }
        // A1: el coordinador solo ve rendiciones de rubros de SU programa.
        exigirProgramaPropioPorRubro($rubro_id);

        $rendiciones    = RendicionAdminVista::findxatributo('rubro_id', $rubro_id);
        $totalRendido   = Rendicion::totalImputadoAlRubro($rubro_id);
        $disponible     = max(0, (float) $rubro->monto - $totalRendido);
        $resultado      = is_array($resultado) ? ($resultado[1] ?? null) : null;
        $tipocomprobante = TipoComprobante::all();
        // Candado: el coordinador no registra rendiciones si su POA Presupuestal está Enviado/Aprobado.
        $bloqueado      = esCoordinador() && !poaPresupuestalEditable(programaIdPorActividad($rubro->actividad_id));

        $router->render('rendicion/admin', [
            'resultado'       => $resultado,
            'rendiciones'     => $rendiciones,
            'rubro'           => $rubro,
            'rubro_id'        => $rubro_id,
            'totalRendido'    => $totalRendido,
            'disponible'      => $disponible,
            'tipocomprobante' => $tipocomprobante,
            'bloqueado'       => $bloqueado
        ]);
    }

    public static function crear(Router $router)
    {
        $rubro_id = (int) ($_GET['rubro_id'] ?? 0);
        $rubro = Rubro::find($rubro_id);
        if (!$rubro) {
            header('Location: /resultado/admin');
            exit();
        }
        // A1: el coordinador solo crea rendiciones en rubros de SU programa.
        exigirProgramaPropioPorRubro($rubro_id);
        // Candado POA Presupuestal: bloquea al coordinador si el documento está Enviado/Aprobado.
        if (self::poaPresupuestalBloqueaRendicion($rubro_id, $rubro->actividad_id)) {
            exit();
        }

        $tipocomprobantes = TipoComprobante::all();
        // Fuentes disponibles: las vinculadas al programa del rubro (vía su actividad).
        $fuentesfinanciamiento = FuenteActividadVista::findxatributo('actividad_id', $rubro->actividad_id);
        $rendicion = new Rendicion;
        $errores = Rendicion::getErrores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // El rubro lo fija la URL (no se acepta vía POST).
            $_POST['rubro_id'] = $rubro_id;
            $rendicion = new Rendicion($_POST);
            $programaId = programaIdPorActividad($rubro->actividad_id);
            $errores = $rendicion->validar();
            // Tope por SOBRE: el monto no puede exceder el saldo del sub-presupuesto
            // (programa, fuente). Reemplaza al tope por rubro (migr. 020).
            if (empty($errores)) {
                $rendicion->validarLimiteSobre((int) $programaId);
                $errores = Rendicion::getErrores();
            }
            if (empty($errores)) {
                //El código se autogenera (correlativo REN###); el usuario no lo teclea.
                $rendicion->codigo = Rendicion::siguienteCodigo();
                $vali = Rendicion::setUsuarioActual();
                if ($vali) {
                    $rendicion->guardarsinRedireccion();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
                if (empty($errores)) {
                    // Item 6: si la rendición es una adenda (Contador/Admin sobre un POA ya
                    // APROBADO), nace aprobada para que el saldo contable la refleje al instante.
                    $docPoa = $programaId ? \Model\Poa::porProgramaAnio($programaId, date('Y')) : null;
                    if ($docPoa && (int) $docPoa->estado === \Model\Poa::APROBADO) {
                        Rendicion::aprobarPorPrograma($programaId);
                    }
                    header("Location: /rendicion/admin?rubro_id={$rubro_id}&resultado=1");
                    exit();
                }
            }
        }
        $router->render('rendicion/crear', [
            'rendicion' => $rendicion,
            'fuentesfinanciamiento' => $fuentesfinanciamiento,
            'tipocomprobantes' => $tipocomprobantes,
            'rubro' => $rubro,
            'rubro_id' => $rubro_id,
            'errores' => $errores
        ]);
    }

    public static function actualizar(Router $router)
    {
        $id = validarORedireccionarDosParametros("resultado/admin", "id", "rubro_id");
        $errores = Rendicion::getErrores();
        $rubro_id = (int) ($_GET['rubro_id'] ?? 0);
        $rubro = Rubro::find($rubro_id);
        if (!is_array($id) || !$rubro) {
            header('Location: /rendicion/admin?rubro_id=' . $rubro_id);
            exit();
        }
        $rendicion = Rendicion::find($id[0]);
        if (!$rendicion) {
            header('Location: /rendicion/admin?rubro_id=' . $rubro_id);
            exit();
        }
        // A1: la rendición debe pertenecer a un rubro de SU programa.
        exigirProgramaPropioPorRubro($rendicion->rubro_id);
        // Candado POA Presupuestal: bloquea al coordinador si el documento está Enviado/Aprobado.
        if (self::poaPresupuestalBloqueaRendicion($rubro_id, $rubro->actividad_id)) {
            exit();
        }

        $tipocomprobantes = TipoComprobante::all();
        $fuentesfinanciamiento = FuenteActividadVista::findxatributo('actividad_id', $rubro->actividad_id);

        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            $rubroOriginal = $rendicion->rubro_id;
            $rendicion->sincronizar($_POST);
            // A2: el rubro padre no se reasigna vía POST.
            $rendicion->rubro_id = $rubroOriginal;
            $errores = $rendicion->validar();
            // Tope por SOBRE (sub-presupuesto de la fuente para el programa) — migr. 020.
            if (empty($errores)) {
                $programaId = programaIdPorActividad($rubro->actividad_id);
                $rendicion->validarLimiteSobre((int) $programaId);
                $errores = Rendicion::getErrores();
            }
            if (empty($errores)) {
                $vali = Rendicion::setUsuarioActual();
                if ($vali) {
                    $rendicion->guardarsinRedireccion();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
                if (empty($errores)) {
                    header("Location: /rendicion/admin?rubro_id=" . $rubro_id . "&resultado=2");
                    exit();
                }
            }
        }
        $router->render('rendicion/actualizar', [
            'errores' => $errores,
            'rendicion' => $rendicion,
            'fuentesfinanciamiento' => $fuentesfinanciamiento,
            'tipocomprobantes' => $tipocomprobantes,
            'rubro' => $rubro,
            'rubro_id' => $rubro_id
        ]);
    }

    public static function eliminar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = validarORedireccionarPost("resultado/admin");
            $rendicion = Rendicion::find($id);
            if (!$rendicion) {
                header('Location: /resultado/admin');
                exit();
            }
            // A1: solo puede eliminar rendiciones de rubros de SU programa.
            exigirProgramaPropioPorRubro($rendicion->rubro_id);
            // Candado POA Presupuestal: bloquea al coordinador si el documento está Enviado/Aprobado.
            $rubro = Rubro::find($rendicion->rubro_id);
            if ($rubro && self::poaPresupuestalBloqueaRendicion($rendicion->rubro_id, $rubro->actividad_id)) {
                exit();
            }
            $vali = Rendicion::setUsuarioActual();
            if ($vali) {
                $rendicion->eliminarsinRedireccion();
            }
            header("Location: /rendicion/admin?rubro_id={$rendicion->rubro_id}&resultado=3");
            exit();
        }
    }
}
