<main>
    <h1>Crear Rubro</h1>
    <?php
    foreach ($errores as $error) : ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php
    endforeach;
    ?>
    <div class="row">
        <div class="col">
            <form method="POST">
                <?php include __DIR__ . '/formulario.php'; ?>
                <div class="espacio-btn-crear">
                    <div>
                        <a href="/rubro/admin?actividad_id=<?php echo $idactividad; ?>" class="btn btn-primary btn-volver"><i class="bi bi-arrow-bar-left"></i> Volver</a>
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