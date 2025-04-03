<fieldset>
    <legend>Datos Personales</legend>
    <div class="mb-3">
        <label for="nro_documento" class="form-label">Número de documento de identificación</label>
        <input type="text" class="form-control" id="nro_documento" aria-describedby="nro_documentoHelp" name="persona[nro_documento]" value="<?php echo s($persona->nro_documento); ?>">
        <div id="nro_documentoHelp" class="form-text">Ingrese el número de documento del usuario</div>
    </div>
    <div class="mb-3">
        <label for="apellido_paterno" class="form-label">Apellido Paterno</label>
        <input type="text" class="form-control" id="apellido_paterno" aria-describedby="apellido_paternoHelp" name="persona[apellido_paterno]" value="<?php echo s($persona->apellido_paterno); ?>">
        <div id="apellido_paternoHelp" class="form-text">Ingrese el apellido paterno del usuario</div>
    </div>
    <div class="mb-3">
        <label for="apellido_materno" class="form-label">Apellido Materno</label>
        <input type="text" class="form-control" id="apellido_materno" aria-describedby="apellido_maternoHelp" name="persona[apellido_materno]" value="<?php echo s($persona->apellido_materno); ?>">
        <div id="apellido_maternoHelp" class="form-text">Ingrese el apellido materno del usuario</div>
    </div>
    <div class="mb-3">
        <label for="nombres" class="form-label">Nombres</label>
        <input type="text" class="form-control" id="nombres" aria-describedby="nombresHelp" name="persona[nombres]" value="<?php echo s($persona->nombres); ?>">
        <div id="nombresHelp" class="form-text">Ingrese los nombres del usuario</div>
    </div>
    <div class="mb-3">
        <label for="telefono" class="form-label">Teléfono de contacto</label>
        <input type="number" class="form-control" id="telefono" aria-describedby="telefonoHelp" name="persona[telefono]" value="<?php echo s($persona->telefono); ?>">
        <div id="telefonoHelp" class="form-text">Ingrese el teléfono de contacto del usuario</div>
    </div>
</fieldset>

<fieldset>
    <legend>Datos del acceso de usuario</legend>
    <div class="mb-3">
        <label for="descripcion" class="form-label">Usuario</label>
        <input type="text" class="form-control" id="descripcion" aria-describedby="descripcionHelp" name="usuario[descripcion]" value="<?php echo s($usuario->descripcion); ?>">
        <div id="descripcionHelp" class="form-text">Ingrese el nombre de usuario para el acceso</div>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="text" class="form-control" id="email" aria-describedby="emailHelp" name="usuario[email]" value="<?php echo s($usuario->email); ?>">
        <div id="emailHelp" class="form-text">Ingrese el email para el acceso</div>
    </div>
    <div class="mb-3">
        <label for="cargo" class="form-label">Cargo</label>
        <select name="usuario[cargo_id]" id="cargo" aria-describedby="cargoHelp">
            <option value="" selected disabled>--Seleccione--</option>
            <?php
            foreach ($cargos as $cargo) { ?>
                <option <?php echo $usuario->cargo_id === $cargo->id ? 'selected' : ''; ?> <?php echo $cargo->id == 3 && $cmbstatus ? 'disabled' : ''; ?> value="<?php echo S($cargo->id); ?>"><?php echo s($cargo->descripcion);  ?> </option>
            <?php } ?>
        </select>
        <div id="cargoHelp" class="form-text">Seleccione el cargo del usuario</div>
    </div>

    <!-- Sección del combo select para usuarios coordinadores (TODOS LOS USUARIOS) -->
    <div class="mb-3">
        <label for="programas_coordinador" id="programas_coordinador_label" class="form-label oculto">Seleccionar Programa</label>
        <select name="poa[programa_id]" id="programas_coordinador" class="oculto" aria-describedby="usuarioHelp">
            <option value="" selected disabled>--Seleccione--</option>
            <?php
            $first = true; // Variable para marcar el primer elemento
            if (isset($programas)) {
                foreach ($programas as $programa) { ?>
                    <option value="<?php echo s($programa->id); ?>"
                        <?php echo $first ? 'selected' : ''; ?>>
                        <?php echo s("{$programa->codigo} - {$programa->nombre}"); ?>
                    </option>

                    <?php $first = false; // Después del primer elemento, cambia la variable 
                    ?>
                <?php } ?>
            <?php } ?>

        </select>
        <div id="programas_coordinador_help" class="form-text oculto">Seleccion el programa</div>
    </div>

</fieldset>