<main class="contenedor seccion contenido-centrado">
        <h1>Iniciar Sesión</h1>
        <!-- Imprimiendo las alertas de errores en la página -->
        <?php foreach($errores as $error):?>
        <div class="alerta error">
        <?php echo $error;?>
        </div>
        <?php endforeach;?>
        <form method="POST" class="formulario" action="/login">
            <fieldset>
                    <label for="nombre">Nombres</label>
                    <input name="nombre" type="text" placeholder="Nombres" id="nombre" require>
                    
                    <label for="apellido">Apellidos</label>
                    <input name="apellido" type="text" placeholder="Apellidos" id="apellido" require>

                    <label for="dni">DNI</label>
                    <input name="dni" type="number" placeholder="Dni" id="dni" require>

                    <label for="cargo">Cargo</label>
                    <select name="cargo" id="cargo">
                        <option>--Seleccione--</option>
                        <option value="">Administrador</option>
                        <option value="">Contador</option>
                    </select>
                <fieldset>
                    <legend>Accesos</legend>
                    <label for="email">E-mail</label>
                    <input name="email" type="email" placeholder="Email" id="email" require>
                    <label for="password">Password</label>
                    <input name="password" type="password" placeholder="Password" id="password" require>
                </fieldset>
            </fieldset>
            <input type="submit" value="Iniciar Sesión" class="boton boton-verde">
            <input type="submit" value="Cambia tu contraseña" class="boton boton-amarillo">
        </form>
</main>