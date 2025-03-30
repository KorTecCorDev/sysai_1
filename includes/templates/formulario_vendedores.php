<fieldset>
    <legend>Información General</legend>
    <!-- Nombres -->
    <label for="nombre">Nombre:</label>
    <input type="text" id="nombre" name="vendedor[nombre]" placeholder="Nombre del(a) vendedor(a)" value="<?php echo s($vendedor->nombre); ?>">
    <!-- Apellidos -->
    <label for="apellido">Apellidos:</label>
    <input type="text" id="apellido" name="vendedor[apellido]" placeholder="Apellidos del(a) vendedor(a)" value="<?php echo s($vendedor->apellido); ?>">
    
    
</fieldset>
<!-- Teléfono -->
<fieldset>
    <legend>Información Extra</legend>
    <label for="telefono">Teléfono:</label>
    <input type="tel" id="telefono" name="vendedor[telefono]" placeholder="Teléfono del(a) vendedor(a)" value="<?php echo s($vendedor->telefono); ?>">
</fieldset>