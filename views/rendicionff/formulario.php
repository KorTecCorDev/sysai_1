<fieldset class="border p-3">
    <legend class="border p-2">Fuentes de financiamiento del Programa <?php echo($resultados[0]->programa_nombre); ?></legend>
    <div class="row mb-3">
        <div class="col-12 w-auto">
            <label class="form-label" for="fuentes_financiamiento">Fuentes de financiamiento:</label>
            <select class="form-select" id="fuentes_financiamiento_combo" name="fuente_financiamiento_id">
                <option value="" disabled selected>-- Seleccione --</option>
                <?php foreach ($resultados as $resultado): ?>
                    <?php foreach ($fuentesf as $fuentef): ?>
                    <option <?php echo $fuentef->id === $resultado->fuente_financiamiento_id ? 'selected' : ''; ?> value="<?php echo s("{$fuentef->nombre}/{$fuentef->presupuesto}"); ?>">
                        <?php echo s($fuentef->nombre); ?>
                    </option>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 w-25">
            <label for="monto" class="form-label">Monto:</label>
            <input type="number" step="0.01" min="0" max="<?php echo s(MONTO_MAXIMO); ?>" class="form-control" id="monto" name="rendicion_ff[monto]" placeholder="0.00" value="<?php echo s($rendi->monto); ?>">
        </div>
    </div>

</fieldset>