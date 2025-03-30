<main>
    <h1>Actualizar Producto</h1>
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
        <a href="/producto/admin" class="btn btn-primary btn-volver">Volver</a>
        <input type="submit" value="Actualizar Producto" class="btn btn-primary btn-agregar">
    </form>
</main>