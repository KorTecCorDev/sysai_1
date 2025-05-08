<main>
    <div class="header-admin">
        <h1>Administrador de Fuentes de Financiamiento</h1>
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
                <h2>Categorías de Rubros</h2>
            </div>
            <div class="d-flex justify-content-end mb-4"> <!-- Contenedor flexible alineado a la derecha -->
                <a href="/categoria_rubro/crear" class="btn btn-primary rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            </div>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla propiedades -->
    <div class="container">
        <div class="table-responsive rounded-3 shadow-sm"> <!-- Agregado rounded-3 y sombra -->
            <table class="table table-hover align-middle mb-0"> <!-- Quitado table-bordered -->
                <thead class="table">
                    <tr>
                        <th scope="col" class="text-center rounded-start">Código</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Descripción</th>
                        <th scope="col" class="text-center rounded-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Muestra todos los registros -->
                    <?php foreach ($categoria_rubro as $categoria_rubro) : ?>
                        <tr>
                            <td class="text-center fw-semibold"> <?php echo $categoria_rubro->codigo; ?> </td>
                            <td> <?php echo $categoria_rubro->nombre; ?> </td>
                            <td> <?php echo $categoria_rubro->descripcion; ?> </td>
                            <td class="text-center">
                                <!-- Div de Acciones     -->
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="/categoria_rubro/actualizar?id=<?php echo $categoria_rubro->id; ?>"
                                        class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                        title="Editar categoría">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <form method="POST" action="/categoria_rubro/eliminar" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $categoria_rubro->id; ?>">
                                        <input type="hidden" name="tipo" value="fuente_financiamiento">
                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                            title="Eliminar categoría"
                                            onclick="return confirm('¿Estás seguro de eliminar esta categoría?');">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                                <!-- Div de Acciones     -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>