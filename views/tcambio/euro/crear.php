<main>
    <h1>Crear Tipo de Cambio Euro</h1>
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
    <div class="border rounded-3 shadow-sm p-4 mx-auto my-4" style="max-width: 700px; background-color: #fff;">
        <h4 class="mb-4 text-primary text-uppercase">Registrar Tipo de Cambio Euro</h4>
        <form method="POST">
            <?php include __DIR__ . '/formulario.php'; ?>
            <div class="d-flex justify-content-between mt-4">
                <a href="/tcambio/euro/admin" class="btn btn-outline-danger rounded-pill px-4 py-2">
                    <i class="bi bi-arrow-left-short me-2"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i> Registrar
                </button>
            </div>
        </form>
    </div>
</main>