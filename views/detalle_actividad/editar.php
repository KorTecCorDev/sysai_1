<main>
    <h1>Indicadores de la Actividad <?php echo s($actividad->codigo); ?></h1>
    <?php foreach ($errores as $error) { ?>
        <div class="alert alert-danger"><?php echo s($error); ?></div>
    <?php } ?>
    <div class="border rounded-3 shadow-sm p-4 mx-auto my-4" style="max-width: 700px; background-color: #fff;">
        <h4 class="mb-4 text-primary text-uppercase">Registrar / Editar Indicador</h4>
        <p class="text-muted"><strong>Actividad:</strong> <?php echo s($actividad->nombre); ?></p>
        <form method="POST" action="/detalle_actividad/guardar"><?php echo csrf_input(); ?>
            <input type="hidden" name="actividad_id" value="<?php echo s($actividad->id); ?>">
            <?php include __DIR__ . '/formulario.php'; ?>
            <div class="d-flex justify-content-between mt-4">
                <a href="/actividad/admin?producto_id=<?php echo s($actividad->producto_id); ?>"
                    class="btn btn-outline-danger rounded-pill px-4 py-2">
                    <i class="bi bi-arrow-left-short me-2"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
                    <i class="bi bi-save me-2"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</main>
