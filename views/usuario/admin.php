<main>
    <h1>Administrador de Usuarios</h1>
    <?php
    if ($resultado) {
        $mensaje = mostrarNotificacion(intval($resultado));
        if ($mensaje) { ?>
            <p class="alert alert-info"><?php echo s($mensaje); ?></p>
    <?php
        }
    }
    ?>
    <div class="row">
        <div class="col">
            <h2>Usuarios</h2>
        </div>
        <div class="col-2 espacio-btn-agregar">
            <a href="/usuario/crear" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container text-center p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Datos</th>
                    <th scope="col">Email</th>
                    <th scope="col">Teléfono</th>
                    <th scope="col">Cargo</th>
                    <th scope="col" class="th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- MostrarLosRegistrosDePropiedades -->

                <?php foreach ($usuarios as $usuario) : ?>
                    <tr>
                        <td> <?php echo $usuario->id; ?> </td>
                        <td> <?php echo $usuario->descripcion; ?> </td>
                        <td> <?php echo $usuario->datos; ?> </td>
                        <td> <?php echo $usuario->email; ?> </td>
                        <td> <?php echo $usuario->telefono; ?> </td>
                        <td> <?php echo $usuario->cargo; ?> </td>
                        <td class="td-acciones">
                            <div class="btn-acciones">
                                <div>
                                    <a href="/usuario/actualizar?id=<?php echo $usuario->id; ?>" class="btn btn-warning btn-actualizar"><i class="bi bi-pen"></i> Actualizar</a>
                                </div>
                                <div>
                                    <form method="POST" class="w-100" action="/usuario/eliminar">
                                        <input type="hidden" name="id" value="<?php echo $usuario->id; ?>">
                                        <input type="hidden" name="tipo" value="usuario">
                                        <div class="btn-eliminar">
                                            <i class="bi bi-trash"></i>
                                            <input type="submit" class="btn" value="Eliminar">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>