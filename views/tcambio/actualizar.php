<main>
    <div class="header-admin">
        <h1>Editar Tipo de Cambio <?php echo s($moneda); ?></h1>
        <h4>Las conversiones ya congeladas en rendiciones y OIE no cambian al editar esta tasa</h4>
    </div>
    <div class="container">
        <?php foreach ($errores as $error) : ?>
            <div class="alert alert-danger"><?php echo s($error); ?></div>
        <?php endforeach; ?>

        <form method="POST" action="/tcambio/actualizar?id=<?php echo s($tipocambio->id); ?>&moneda=<?php echo s($moneda); ?>"><?php echo csrf_input(); ?>
            <?php include __DIR__ . '/formulario.php'; ?>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-save me-2"></i>Actualizar
                </button>
                <a href="/tcambio/admin?moneda=<?php echo s($moneda); ?>" class="btn btn-outline-danger rounded-pill px-4">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</main>
