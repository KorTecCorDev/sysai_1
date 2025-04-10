<fieldset class="border p-3">
    <legend>Detalle del Rubro</legend>
    <div class="mb-3 w-auto">
        <label for="tipo_cbo">Tipo:</label>
        <select class="form-select shadow-sm w-auto" id="tipo_combo" name="tipo_rubro_id">
            <option value="" disabled selected>-- Seleccione --</option>
            <?php foreach ($tiporubros as $tiporubro): ?>
                <option <?php echo $tiporubro->id == $rubro->tipo_rubro_id ? 'selected' : ''; ?> value="<?php echo $tiporubro->id; ?>">
                    <?php echo $tiporubro->nombre; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3 w-auto">
        <label for="categoria_cbo">Categoría:</label>
        <select class="form-select shadow-sm w-auto" id="categoria_combo" name="categoria_rubro_id">
            <option value="" disabled selected>-- Seleccione --</option>
            <?php foreach ($categoriarubros as $categoriarubro): ?>
                <option <?php echo $categoriarubro->id == $rubro->categoria_rubro_id ? 'selected' : ''; ?> value="<?php echo $categoriarubro->id; ?>">
                    <?php echo $categoriarubro->codigo . ' ' . $categoriarubro->nombre; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3 w-25">
        <label for="codigo" class="form-label">Código:</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="codigo" name="codigo" placeholder="Código" value="<?php echo s($rubro->codigo); ?>">
    </div>

    <div class="mb-3 w-75">
        <label for="nombre" class="form-label">Rubro:</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="nombre" name="nombre" placeholder="Rubro" value="<?php echo s($rubro->nombre); ?>">
    </div>

    <div class="mb-3 w-auto">
        <label for="descripcion" class="form-label">Descripción:</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="descripcion" name="descripcion" placeholder="Descripción" value="<?php echo s($rubro->descripcion); ?>">
    </div>

    <div class="mb-3 w-25">
        <label for="monto" class="form-label">Monto:</label>
        <input type="float" style="text-transform: uppercase" class="form-control shadow-sm" id="monto" name="monto" placeholder="Monto" value="<?php echo s($rubro->monto); ?>">
    </div>

</fieldset>