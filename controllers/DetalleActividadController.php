<?php

namespace Controllers;

use MVC\Router;
use Model\Actividad;
use Model\DetalleActividad;

class DetalleActividadController
{
    // Formulario de captura del indicador de una actividad (crear o editar el único registro).
    public static function editar(Router $router)
    {
        exigirRol([1, 2, 3]);
        $idactividad = validarId('actividad');
        if (!$idactividad) {
            header('Location: /resultado/admin');
            exit();
        }
        // A1: la actividad debe pertenecer al programa del coordinador.
        exigirProgramaPropioPorActividad($idactividad);

        $actividad = Actividad::find($idactividad);
        if (!$actividad) {
            header('Location: /resultado/admin');
            exit();
        }

        // Indicador existente (si lo hay) para precargar el formulario.
        $existente = DetalleActividad::porActividad($idactividad);
        $detalle = $existente ? new DetalleActividad((array) $existente) : new DetalleActividad();
        $errores = DetalleActividad::getErrores();

        $router->render('detalle_actividad/editar', [
            'detalle'   => $detalle,
            'actividad' => $actividad,
            'errores'   => $errores
        ]);
    }

    // Upsert del indicador. Bloqueado si el POA Indicadores está Enviado/Aprobado.
    public static function guardar(Router $router)
    {
        exigirRol([1, 2, 3]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /resultado/admin');
            exit();
        }

        $idactividad = filter_var($_POST['actividad_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$idactividad) {
            header('Location: /resultado/admin');
            exit();
        }
        // A1: la actividad debe pertenecer al programa del coordinador.
        exigirProgramaPropioPorActividad($idactividad);

        $actividad = Actividad::find($idactividad);
        if (!$actividad) {
            header('Location: /resultado/admin');
            exit();
        }

        // Bloqueo por estado del documento (coordinador): no editar si está enviado/aprobado.
        $programaId = programaIdPorActividad($idactividad);
        exigirPoaIndicadoresEditable($programaId);

        // Si ya existe, se actualiza ese registro; si no, se crea uno nuevo.
        $existente = DetalleActividad::porActividad($idactividad);
        $detalle = $existente ? new DetalleActividad((array) $existente) : new DetalleActividad();
        $detalle->sincronizar([
            'indicador_medido'   => $_POST['indicador_medido'] ?? null,
            'medio_verificacion' => $_POST['medio_verificacion'] ?? '',
            'supuesto'           => $_POST['supuesto'] ?? '',
            'responsable'        => $_POST['responsable'] ?? ''
        ]);
        $detalle->actividad_id = $idactividad;

        $detalle->validar();
        $errores = DetalleActividad::getErrores();
        if (empty($errores)) {
            DetalleActividad::setUsuarioActual();
            $detalle->guardarsinRedireccion();
            // Volver al listado de actividades del producto padre.
            header('Location: /actividad/admin?producto_id=' . $actividad->producto_id . '&resultado=2');
            exit();
        }

        $router->render('detalle_actividad/editar', [
            'detalle'   => $detalle,
            'actividad' => $actividad,
            'errores'   => $errores
        ]);
    }
}
