<main>
    <h1>Actualizar Resultado</h1>
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
        <div class="row">
            <div class="col-2">
                <a href="/resultado/admin?programa_id=<?php echo $programaid; ?>" class="btn btn-volver"><i class="bi bi-arrow-bar-left"></i> Volver</a>
            </div>
            <div class="col-2 btn-actualizar-sbmt">
                <i class="bi bi-pen"></i>
                <input type="submit" class="btn" value="Actualizar">
            </div>
        </div>
    </form>
</main>