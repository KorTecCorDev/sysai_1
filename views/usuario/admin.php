<main>
    <div class="header-admin">
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
            <div class="d-flex justify-content-end mb-4"> <!-- Contenedor flexible alineado a la derecha -->
                <a href="/usuario/crear" class="btn btn-primary rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            </div>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container">
        <div class="table-responsive rounded-3 shadow-sm"> <!-- Agregado rounded-3 y sombra -->
            <table class="table table-hover align-middle mb-0"> <!-- Quitado table-bordered -->
                <thead class="table">
                    <tr>
                        <th scope="col" class="text-center rounded-start">Usuario</th>
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
                            <td class="text-center fw-semibold"> <?php echo s($usuario->descripcion); ?> </td>
                            <td> <?php echo s($usuario->datos); ?> </td>
                            <td> <?php echo s($usuario->email); ?> </td>
                            <td> <?php echo s($usuario->telefono); ?> </td>
                            <td> <?php echo s($usuario->cargo); ?> </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="/usuario/actualizar?id=<?php echo s($usuario->id); ?>"
                                        class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                        title="Editar registro">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <form method="POST" action="/usuario/eliminar" class="d-inline"><?php echo csrf_input(); ?>
                                        <input type="hidden" name="id" value="<?php echo s($usuario->id); ?>">
                                        <input type="hidden" name="tipo" value="usuario">
                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                            title="Eliminar registro"
                                            onclick="return confirm('¿Estás seguro de eliminar este registro?');">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>