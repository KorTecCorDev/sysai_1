<main>
    <h1>Actualizar Actividad</h1>
    <?php
    foreach ($errores as $error) : ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php
    endforeach;
    ?>
    <form class="formulario" method="POST">
        <?php include __DIR__ . '/formulario.php'; ?>
        <a href="/actividad/admin?producto_id=<?php echo $actividad->producto_id; ?>" class="btn btn-primary btn-volver">Volver</a>
        <input type="submit" value="Actualizar Actividad" class="btn btn-primary btn-agregar">
    </form>
</main>