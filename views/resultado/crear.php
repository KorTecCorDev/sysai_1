<main>
    <h1>Agregar Resultado</h1>
    <?php foreach ($errores as $error) { ?>
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
    <div class="row">
        <div class="col">
            <form method="POST">
                <?php include __DIR__ . '/formulario.php'; ?>
                <div class="espacio-btn-crear">
                    <div>
                        <a href="/resultado/admin?programa_id=<?php echo $idprograma; ?>" class="btn btn-primary btn-volver"><i class="bi bi-arrow-bar-left"></i> Volver</a>
                    </div>
                    <div class="btn-agregar-sbmt">
                        <i class="bi bi-plus-circle"></i>
                        <input type="submit" value="Agregar" class="btn">
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>