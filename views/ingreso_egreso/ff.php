<main>
    <h1>Elegir Fuente de Financiamiento</h1>

    <?php if (isset($errores)) { ?>
        <?php foreach ($errores as $error) { ?>
            <div class="modal fade" data-bs-key="modal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Error!</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><?php echo $error; ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    <?php } ?>
    <form method="POST">
        <fieldset>
            <!-- SELECT que muestra los objetos del array $ff_programas (Fuentes de Financiamiento) -->
            <label for="ff_programa_id">Fuentes de financiamiento</label>
            <select class="form-select w-auto" id="combo_ff_programa" name="ff_id">
                <option value="" disabled selected>--Seleccione--</option>
                <?php foreach ($ff_programas as $ff_programa) { ?>
                    <option value="<?php echo $ff_programa->fuente_financiamiento_id; ?>" <?php echo $ff_programa->fuente_financiamiento_id == $oie->ff_id ? 'selected' : '' ?>><?php echo $ff_programa->fuente_financiamiento_nombre; ?></option>
                <?php } ?>
            </select>
        </fieldset>
        <div class="espacio-btn-crear">
            <div>
                <a href="/ingreso_egreso/actualizar?id=<?php echo s($oie->id); ?>" class="btn btn-primary btn-volver"><i class="bi bi-arrow-bar-left"></i> Volver</a>
            </div>
            <div class="btn-agregar-sbmt">
                <i class="bi bi-plus-circle"></i>
                <input type="submit" value="Agregar" class="btn">
            </div>
        </div>
    </form>
</main>