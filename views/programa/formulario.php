<fieldset>
    <div class="mb-3">
        <label for="codigo" class="form-label">Código</label>
        <input type="text" class="form-control" id="codigo" aria-describedby="codigoHelp" name="programa[codigo]" value="<?php echo s($programa->codigo); ?>">
        <div id="codigoHelp" class="form-text">Ingrese el código que recibirá el programa</div>
    </div>

    <div class="mb-3">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" class="form-control" id="nombre" aria-describedby="codigoHelp" name="programa[nombre]" value="<?php echo s($programa->nombre); ?>">
        <div id="nombreHelp" class="form-text">Ingrese el nombre que recibirá el programa</div>
    </div>

    <!-- SELECT DE TIPOS DE PROGRAMA -->
    <label for="tipo_programa_id">Tipo</label>
    <select class="form-select w-auto" id="combo_tipo_programa_id" name="programa[tipo_programa_id]">
        <option value="" disabled selected>--Seleccione--</option>
        <?php foreach ($tiposprograma as $tprograma) { ?>
            <option value="<?php echo $tprograma->id; ?>" <?php echo $tprograma->id == $programa->tipo_programa_id ? 'selected' : ''; ?>><?php echo $tprograma->descripcion; ?></option>
        <?php } ?>
    </select>

    <!-- SELECT DE TIPOS DE PROGRAMA -->
    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea type="text" class="form-control" id="descripcion" aria-describedby="descripcionHelp" name="programa[descripcion]"><?php echo s($programa->descripcion); ?></textarea>
        <div id="descripcionHelp" class="form-text">Ingrese la descripción que recibirá el programa</div>
    </div>
</fieldset>