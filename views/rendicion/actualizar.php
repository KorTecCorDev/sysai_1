<main class="container">
    <h1>Actualizar Rendición — Rubro <?php echo s($rubro->codigo); ?></h1>
    <?php if (!empty($errores)) : ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errores as $error) : ?>
                    <li><?php echo s($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="border rounded-3 shadow-sm p-4 mx-auto my-4" style="max-width: 700px; background-color: #fff;">
        <h4 class="mb-4 text-primary text-uppercase"><?php echo s($rubro->nombre); ?></h4>
        <form method="POST"><?php echo csrf_input(); ?>
            <?php include __DIR__ . '/formulario.php'; ?>
            <div class="d-flex justify-content-between mt-4">
                <a href="/rendicion/admin?rubro_id=<?php echo s($rubro_id); ?>" class="btn btn-outline-danger rounded-pill px-4 py-2">
                    <i class="bi bi-arrow-left-short me-2"></i> Volver
                </a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i> Registrar
                </button>
            </div>
        </form>
</main>