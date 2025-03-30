<main>
    <h1>Relacionar Fuentes de financiamiento</h1>
    <?php
    foreach ($errores as $error) : ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php
    endforeach;
    ?>
        <form method="POST">
            <?php include __DIR__ . '/formulario.php'; ?>
        <input type="submit" value="Enlazar F.Financiamineto" class="btn btn-primary">
        <a href="/rendicionff/admin?actividad_id=<?php echo $resultados[0]->actividad_id; ?>" class="btn btn-primary ">Volver</a>
        </form>
</main>
