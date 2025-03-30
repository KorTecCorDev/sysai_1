<main>
    <h1>Actualizar Usuario</h1>
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
        <a href="/usuario/admin" class="btn btn-primary">Volver</a>
        <input type="submit" value="Actualizar Usuario" class="btn btn-primary">
    </form>
</main>