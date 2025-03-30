<fieldset class="border p-3">
    <legend class="border p-2">Detalle del Rubro</legend>

    <div class="row mb-3">
        <div class="col-12 w-auto">
            <label for="tipo_cbo">Tipo:</label>
            <select class="form-control" id="tipo_combo" name="tipo_rubro_id">
                <option value="" disabled selected>-- Seleccione --</option>
                <?php foreach ($tiporubros as $tiporubro): ?>
                    <option value="<?php echo $tiporubro->id; ?>" <?php echo $tiporubro->id == $rubro->tipo_rubro_id ? 'selected' : ''; ?>>
                        <?php echo $tiporubro->nombre; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 w-auto">
            <label for="categoria_cbo">Categoría:</label>
            <select class="form-control" id="categoria_combo" name="categoria_rubro_id">
                <option value="" disabled selected>-- Seleccione --</option>
                <?php foreach ($categoriarubros as $categoriarubro): ?>
                    <option value="<?php echo $categoriarubro->id; ?>">
                        <?php echo $categoriarubro->codigo . ' ' . $categoriarubro->nombre; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 w-auto">
            <label for="codigo" class="form-label">Código:</label>
            <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Código" value="<?php echo s($rubro->codigo); ?>">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 w-50">
            <label for="nombre" class="form-label">Rubro:</label>
            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Rubro" value="<?php echo s($rubro->nombre); ?>">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 w-50">
            <label for="descripcion" class="form-label">Descripción:</label>
            <input type="text" class="form-control" id="descripcion" name="descripcion" placeholder="Descripción" value="<?php echo s($rubro->descripcion); ?>">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 w-auto">
            <label for="monto" class="form-label">Monto:</label>
            <input type="float" class="form-control" id="monto" name="monto" placeholder="Monto" value="<?php echo s($rubro->monto); ?>">
        </div>
    </div>



</fieldset>