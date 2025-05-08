<fieldset>
    <div class="mb-3 w-25">
        <label for="codigo" class="form-label">Código</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="codigo" name="programa[codigo]" aria-describedby="codigoHelp" value="<?php echo s($programa->codigo); ?>">
        <div id="codigoHelp" class="form-text">Ingrese un código válido</div>
    </div>

    <div class="mb-3">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="nombre" name="programa[nombre]" aria-describedby="nombreHelp" value="<?php echo s($programa->nombre); ?>">
        <div id="nombreHelp" class="form-text">Ingrese el nombre que recibirá este programa</div>
    </div>

    <!-- SELECT DE TIPOS DE PROGRAMA -->
    <div class="mb-3">
        <label for="combo_tipo_programa_id" class="form-label">Tipo de Programa</label>
        <select class="form-select shadow-sm" id="combo_tipo_programa_id" name="programa[tipo_programa_id]" aria-describedby="tipoProgramaHelp">
            <option value="" disabled selected>--Seleccione--</option>
            <?php foreach ($tiposprograma as $tprograma) { ?>
                <option value="<?php echo $tprograma->id; ?>" <?php echo $tprograma->id == $programa->tipo_programa_id ? 'selected' : ''; ?>>
                    <?php echo $tprograma->descripcion; ?>
                </option>
            <?php } ?>
        </select>
        <div id="tipoProgramaHelp" class="form-text">Seleccione el tipo de programa</div>
    </div>


    <!-- SELECT DE TIPOS DE PROGRAMA -->
    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea style="text-transform: uppercase" class="form-control shadow-sm" id="descripcion" name="programa[descripcion]" aria-describedby="descripcionHelp"><?php echo s($programa->descripcion); ?></textarea>
        <div id="descripcionHelp" class="form-text">Ingrese la descripción que recibirá este programa</div>
    </div>
</fieldset>