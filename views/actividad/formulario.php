<fieldset>
    <legend>Actividad</legend>
    <div class="mb-3 w-25">
        <label for="codigo" class="form-label">Código</label>
        <input type="text" class="form-control" id="codigo" aria-describedby="codigoHelp" name="codigo" value="<?php echo s($actividad->codigo); ?>">
        <div class="form-text">Ingrese el código de la Actividad</div>
    </div>
    <div class="mb-3 w-auto">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" class="form-control" id="nombre" aria-describedby="nombreHelp" name="nombre" value="<?php echo s($actividad->nombre); ?>">
        <div class="form-text">Ingrese el nombre que recibirá la Actividad</div>
    </div>
    <div class="mb-3 w-auto">
        <label for="descripcion" class="form-label">Descripción</label>
        <input type="text" class="form-control" id="descripcion" aria-describedby="descripcionHelp" name="descripcion" value="<?php echo s($actividad->descripcion); ?>">
        <div class="form-text">Ingrese la descripción para la Actividad</div>
    </div>
</fieldset>