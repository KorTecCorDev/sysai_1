<fieldset>
    <legend>Información General</legend>
    <label for="titulo">Título:</label>
    <input type="text" id="titulo" name="propiedad[titulo]" placeholder="Título Propiedad" value="<?php echo s($propiedad->titulo); ?>">

    <label for="precio">Precio:</label>
    <input type="number" id="precio" name="propiedad[precio]" placeholder="Precio Propiedad" value="<?php echo s($propiedad->precio); ?>">

    <label for="imagen">Imagen:</label>
    <input type="file" id="imagen" accept="image/jpeg, image/png" name="propiedad[imagen]">

    <?php
    if ($propiedad->imagen) { ?>
        <img src="/imagenes/<?php echo $propiedad->imagen; ?>" class="imagen-small" alt="Imagen_actual">
    <?php
    } ?>
    <label for="descripcion">Descripcion</label>
    <textarea id="descripcion" name="propiedad[descripcion]"><?php echo s($propiedad->descripcion); ?></textarea>

</fieldset>

<fieldset>
    <legend>Información de la propiedad</legend>
    <label for="habitaciones">Habitaciones:</label>
    <input type="number" id="habitaciones" name="propiedad[habitaciones]" placeholder="Ej: 2" min=1 max=9 value="<?php echo s($propiedad->habitaciones); ?>">

    <label for="wc">Baños:</label>
    <input type="number" id="wc" name="propiedad[wc]" placeholder="Ej: 2" min=1 max=9 value="<?php echo s($propiedad->wc); ?>">

    <label for="estacionamiento">Estacionamiento:</label>
    <input type="number" id="estacionamiento" name="propiedad[estacionamiento]" placeholder="Ej: 2" min=1 max=9 value="<?php echo s($propiedad->estacionamiento); ?>">
</fieldset>

<fieldset>
    <legend>Vendedor</legend>
       <select name="propiedad[vendedores_id]" id="vendedor">
                <option selected>--Seleccione--</option>
                <?php
                foreach ($vendedores as $vendedor){ ?>
                    <option
                        <?php echo $propiedad->vendedores_id === $vendedor->id ? 'selected' : ''; ?>
                        value="<?php echo S($vendedor->id); ?>"
                    ><?php echo s($vendedor->nombre.' '. $vendedor->apellido);  ?> </option>
                <?php } ?>
            </select>
</fieldset>