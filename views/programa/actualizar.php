<main>
    <h1>Actualizar Programa</h1>
    <?php
    foreach ($errores as $error) { ?>
        <div class="modal fade" data-bs-key="modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Error!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><?php echo $error; ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <form class="formulario" method="POST">
        <?php include __DIR__ . '/formulario.php'; ?>
        <div class="row">
            <div class="col-2">
                <a href="/programa/admin" class="btn btn-volver"><i class="bi bi-arrow-bar-left"></i> Volver</a>
            </div>
            <div class="col-2 btn-actualizar-sbmt">
                <i class="bi bi-pen"></i>
                <input type="submit" class="btn" value="Actualizar">
            </div>
        </div>
    </form>
</main>