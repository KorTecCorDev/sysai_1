<main>
    <h1>Agregar Actividad al Producto</h1>
    <?php if (!empty($errores)) { ?>
        <div class="modal fade show" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true" style="display: block;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="errorModalLabel">Error!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php foreach ($errores as $error) { ?>
                            <p><?php echo $error; ?></p>
                        <?php } ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeModal()">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    <?php } ?>
    <div class="row">
        <div class="col">
            <form method="POST">
                <?php include __DIR__ . '/formulario.php'; ?>
                <div class="espacio-btn-crear">
                    <div>
                        <a href="/actividad/admin?producto_id=<?php echo $idproducto ?>" class="btn btn-primary btn-volver"><i class="bi bi-arrow-bar-left"></i> Volver</a>
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