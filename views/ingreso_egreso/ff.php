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
                            <p><?php echo s($error); ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    <?php } ?>
    <form method="POST" class="needs-validation" novalidate><?php echo csrf_input(); ?>
        <fieldset>
            <legend>Datos Generales</legend>
            <div class="mb-3">
                <label for="ff_programa_id" class="form-label">Fuentes de financiamiento</label>
                <div style="width: auto; min-width: 200px;">
                    <select class="form-select" id="combo_ff_programa" name="ff_id" required style="width: auto; min-width: 200px; max-width: 100%;">
                        <option value="" disabled selected>--Seleccione--</option>
                        <?php foreach ($ff_programas as $ff_programa) { ?>
                            <option value="<?php echo s($ff_programa->fuente_financiamiento_id); ?>" <?php echo $ff_programa->fuente_financiamiento_id == $oie->ff_id ? 'selected' : '' ?>><?php echo s($ff_programa->fuente_financiamiento_nombre); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-text">Seleccione la fuente de financiamiento correspondiente</div>
            </div>
        </fieldset>
        <div class="d-flex gap-2 mt-3">
            <a href="/ingreso_egreso/actualizar?id=<?php echo s($oie->id); ?>" class="btn btn-primary d-flex align-items-center">
                <i class="bi bi-arrow-bar-left me-2"></i>Volver
            </a>
            <button type="submit" class="btn btn-success d-flex align-items-center">
                <i class="bi bi-plus-circle me-2"></i>Agregar
            </button>
        </div>
    </form>
</main>