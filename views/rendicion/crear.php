<main>
    <h1>Ingresar Comprobante</h1>
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
        <div class="espacio-btn-crear">
            <div>
                <a href="/rendicion/admin?actividad_id=<?php echo $actividad_id; ?>"><i class="bi bi-arrow-bar-left"></i> Volver</a>
            </div>
            <div class="btn-agregar-sbmt">
                <i class="bi bi-plus-circle"></i>
                <input type="submit" value="Agregar" class="btn">
            </div>
        </div>
    </form>
</main>