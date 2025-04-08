<fieldset>
    <legend>Datos Personales</legend>
    <div class="mb-3 w-25">
        <label for="nro_documento" class="form-label">Número de documento de identificación</label>
        <input type="number" style="text-transform: uppercase" class="form-control shadow-sm" id="nro_documento" name="persona[nro_documento]" aria-describedby="nro_documentoHelp" value="<?php echo s($persona->nro_documento); ?>">
        <div id="nro_documentoHelp" class="form-text">Ingrese un DNI válido</div>
    </div>

    <div class="mb-3 w-50">
        <label for="apellido_paterno" class="form-label">Apellido Paterno</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="apellido_paterno" name="persona[apellido_paterno]" aria-describedby="apellido_paternoHelp" value="<?php echo s($persona->apellido_paterno); ?>">
        <div id="apellido_paternoHelp" class="form-text">Ingrese el apellido paterno del usuario</div>
    </div>

    <div class="mb-3 w-50">
        <label for="apellido_materno" class="form-label">Apellido Materno</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="apellido_materno" name="persona[apellido_materno]" aria-describedby="apellido_maternoHelp" value="<?php echo s($persona->apellido_materno); ?>">
        <div id="apellido_maternoHelp" class="form-text">Ingrese el apellido materno del usuario</div>
    </div>
    <div class="mb-3 w-75">
        <label for="nombres" class="form-label">Nombres</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="nombres" name="persona[nombres]" aria-describedby="nombresHelp" value="<?php echo s($persona->nombres); ?>">
        <div id="nombresHelp" class="form-text">Ingrese los nombres del usuario</div>
    </div>
    <div class="mb-3 w-25">
        <label for="telefono" class="form-label">Teléfono</label>
        <input type="number" style="text-transform: uppercase" class="form-control shadow-sm" id="telefono" name="persona[telefono]" aria-describedby="telefonoHelp" value="<?php echo s($persona->telefono); ?>">
        <div id="telefonoHelp" class="form-text">Ingrese el teléfono de contacto del usuario</div>
    </div>
</fieldset>

<fieldset>
    <legend>Datos del acceso de usuario</legend>
    <div class="mb-3 w-25">
        <label for="descripcion" class="form-label">Usuario</label>
        <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="descripcion" aria-describedby="descripcionHelp" name="usuario[descripcion]" value="<?php echo s($usuario->descripcion); ?>">
        <div id="descripcionHelp" class="form-text">Ingrese el código de usuario</div>
    </div>

    <div class="mb-3 w-50">
        <label for="email" class="form-label">Email</label>
        <input type="email" style="text-transform: uppercase" class="form-control shadow-sm" id="email" aria-describedby="emailHelp" name="usuario[email]" value="<?php echo s($usuario->email); ?>">
        <div id="emailHelp" class="form-text">Ingrese el email para el acceso</div>
    </div>
    <div class="mb-3">
        <label for="cargo" class="form-label">Cargo</label>
        <select class="form-select shadow-sm w-auto" name="usuario[cargo_id]" id="cargo" aria-describedby="cargoHelp">
            <option value="" selected disabled>--Seleccione--</option>
            <?php
            foreach ($cargos as $cargo) { ?>
                <option <?php echo $usuario->cargo_id === $cargo->id ? 'selected' : ''; ?> value="<?php echo S($cargo->id); ?>"><?php echo s($cargo->descripcion);  ?> </option>
            <?php } ?>
        </select>
        <div id="cargoHelp" class="form-text">Seleccione el cargo del usuario</div>
    </div>

    <!-- Sección del combo select para usuarios coordinadores (TODOS LOS USUARIOS) -->
    <div class="mb-3">
        <label for="programas_coordinador" id="programas_coordinador_label" class="form-label <?php echo !$cmbstatus ? 'oculto':'' ?>">Seleccionar Programa</label>
        <select name="poa[programa_id]" id="programas_coordinador" class="form-select shadow-sm <?php echo !$cmbstatus ? 'oculto':'' ?>" aria-describedby="usuarioHelp">
            <option value="0" selected>--Seleccione--</option>
            <?php
            if (!empty($programas)) {
                foreach ($programas as $programa) { ?>
                    <option value="<?php echo s($programa->programa_id); ?>" <?php echo $objpoa->programa_id === $programa->programa_id ? 'selected' : ''; ?>>
                        <?php echo s("{$programa->programa_codigo} - {$programa->programa_nombre}"); ?>
                    </option>
                <?php } ?>
            <?php } ?>

        </select>
        <div id="programas_coordinador_help" class="form-text <?php echo !$cmbstatus ? 'oculto':'' ?>">Seleccione el programa</div>
    </div>

</fieldset>