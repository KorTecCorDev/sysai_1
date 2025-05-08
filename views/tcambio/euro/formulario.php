<fieldset>
    <div class="mb-3 w-25">
        <label for="tipo_cambio" class="form-label">Tipo de Cambio</label>
        <input type="number" class="form-control" style="text-transform: uppercase" id="tipocambio" aria-describedby="tipoCambioHelp" name="tipocambio" value="<?php echo s($tipocambio->tipo_cambio); ?>" step="0.01">
        <div id="tipoCambioHelp" class="form-text">Ingrese el tipo de cambio para el euro</div>
    </div>
</fieldset>