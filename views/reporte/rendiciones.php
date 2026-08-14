<?php
// Aviso de rango de fechas inválido (resultado=29), emitido por el controlador.
?>
<main>
    <div class="header-admin">
        <h1>Elija las fechas para el Reporte</h1>
        <?php
        if ($resultado) {
            $mensaje = mostrarNotificacion(intval($resultado));
            if ($mensaje) { ?>
                <p class="alert alert-warning"><?php echo s($mensaje); ?></p>
        <?php
            }
        }
        ?>
        <form method="POST" action="" class="p-4 bg-light rounded shadow-sm" style="max-width: 350px;"><?php echo csrf_input(); ?>
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
        </form>
    </div>
</main>