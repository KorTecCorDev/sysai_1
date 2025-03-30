<fieldset>
    <div class="mb-3">
    <label for="codigo" class="form-label">Código</label>
    <input type="text" class="form-control" id="codigo" aria-describedby="codigoHelp" name="categoria_rubro[codigo]" value="<?php echo s($categoria_rubro->codigo); ?>">
    <div id="codigoHelp" class="form-text">Ingrese el código que recibirá la categoría de rubro</div>
    </div>

    <div class="mb-3">
    <label for="nombre" class="form-label">Nombre</label>
    <input type="text" class="form-control" id="nombre" aria-describedby="nombreHelp" name="categoria_rubro[nombre]" value="<?php echo s($categoria_rubro->nombre); ?>">
    <div id="nombreHelp" class="form-text">Ingrese el nombre que recibirá la categoría de rubro</div>
    </div>

    <div class="mb-3">
    <label for="descripcion" class="form-label">Descripción</label>
    <textarea type="text" class="form-control" id="descripcion" aria-describedby="descripcionHelp" name="categoria_rubro[descripcion]"><?php echo s($categoria_rubro->descripcion); ?></textarea>
    <div id="descripcionHelp" class="form-text">Ingrese la descripción que recibirá la categoría de rubro</div>
    </div>

    <div class="mb-3">
    <label for="subcategoria_rubro" class="form-label">Sub Categoría de Rubro</label>
    <select name="categoria_rubro[subcategoria_rubro_id]" id="subcategoria_rubro_id" aria-describedby="subcategoria_rubroHelp">
        <option value="" selected>--Seleccione--</option>
        <?php
        foreach ($subcategorias_rubro as $subcategoria_rubro) { ?>
            <option <?php echo $categoria_rubro->subcategoria_rubro_id === $subcategoria_rubro->id ? 'selected' : ''; ?> value="<?php echo S($subcategoria_rubro->id); ?>"><?php echo s($subcategoria_rubro->nombre);  ?> </option>
        <?php } ?>
    </select>
    <div id="subcategoria_rubroHelp" class="form-text">Seleccione la subcategoría del rubro</div>
    </div>
</fieldset>