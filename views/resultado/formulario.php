<fieldset>
    <legend>Resultado</legend>
    <div class="mb-3 w-25">
        <label for="codigo" class="form-label">Código</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="codigo" aria-describedby="codigoHelp" name="codigo" value="<?php echo s($res->codigo); ?>">
        <div id="codigoHelp" class="form-text">Ingrese el código del Resultado</div>
    </div>
    <div class="mb-3 w-auto">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="nombre" aria-describedby="nombreHelp" name="nombre" value="<?php echo s($res->nombre); ?>">
        <div id="nombreHelp" class="form-text">Ingrese el nombre que recibirá el Resultado</div>
    </div>
    <div class="mb-3 w-auto">
        <label for="descripcion" class="form-label">Descripción</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="descripcion" aria-describedby="descripcionHelp" name="descripcion" value="<?php echo s($res->descripcion); ?>">
        <div id="descripcionHelp" class="form-text">Ingrese la descripción para el Resultado</div>
    </div>
</fieldset>