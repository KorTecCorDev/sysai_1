<main>
    <!-- Títulos -->
    <h1>Fuentes de Financiamiento y Programas</h1>
    <h4>Relacione los programas con las Fuentes de financiamiento deseadas</h4>
    <!-- Resultados de CRUD -->
    <?php
    if ($resultado) {
        $mensaje = mostrarNotificacion(intval($resultado));
        if ($mensaje) { ?>
            <p class="alert alert-info"><?php echo s($mensaje); ?></p>
    <?php
        }
    }
    ?>
    <!-- Errores encontrados al validar -->
    <?php
    foreach ($errores as $error) : ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php
    endforeach;
    ?>
    <!-- Formulario -->
    <div class="row align-items-center">
        <div class="col">
            <?php include __DIR__ . '/formulario.php'; ?>
        </div>
    </div>
    <fieldset>
        <!-- Fuentes de Financiamiento -->
        <div class="container text-center ">
            <div class="row align-items-center"> <?php $cont = 0; ?>
                <?php foreach ($fuentesfinanciamiento as $fuentefinanciamiento) { ?>
                    <!-- Cards -->
                    <div class="tarjeta col-2 card container-fluid <?php echo $fuentefinanciamiento->id; ?>">
                        <div class="card-body">
                            <form method="POST">
                                <h5 class="card-title"><?php echo $fuentefinanciamiento->nombre; ?></h5>
                                <p class="card-text"><?php echo 'S./ ' . $fuentefinanciamiento->presupuesto; ?></p>
                                <input class="dfpgid" type="hidden" name="detalle_financiamiento[fuente_financiamiento_id]" value="<?php echo $fuentefinanciamiento->id; ?>">
                                <input class="idprogram" type="hidden" name="detalle_financiamiento[programa_id]" value="<?php echo $programa->id; ?>">
                                <?php foreach ($resuls as $resul) { ?>
                                    <input class="idquery" type="hidden" value="<?php echo $resul->fuente_financiamiento_id; ?>">
                                <?php } ?>
                                <input class="btn btn-primary addff" type="submit" value="Agregar">
                                <?php ?>
                            </form>
                        </div>
                    </div>
                    <!-- Fin de Cards -->
                <?php $cont++;
                } ?>
            </div>
        </div>
        <div class="form-text">Seleccione la fuentes de financiamiento a relacionar</div>
        <input type="hidden" name="cantidad" value="<?php echo $cont; ?>">
    </fieldset>
</main>