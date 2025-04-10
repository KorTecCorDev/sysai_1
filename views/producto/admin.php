<main>
    <div class="header-admin">
        <h1>Administrador de Productos del Resultado
            <?php echo $objresultado->codigo; ?></h1>
        <?php
        if ($resultado) {
            $mensaje = mostrarNotificacion(intval($resultado));
            if ($mensaje) { ?>
                <p class="alert alert-info"><?php echo s($mensaje); ?></p>
        <?php
            }
        }
        ?>
    </div>


    <div class="row">
        <div class="col">
            <h2>Productos</h2>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <a href="/producto/crear?resultado_id=<?php echo $resultadoid; ?>" class="btn btn-primary rounded-pill shadow-sm">
                <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            <a href="/resultado/admin?programa_id=<?php echo $programaid; ?>" class="btn btn-outline-danger rounded-pill px-4 py-2">
                <i class="bi bi-arrow-left-short me-2"></i> Volver
            </a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container">
        <div class="table-responsive rounded-3 shadow-sm">
            <table class="table table-hover align-middle mb-0">
                <thead class="table">
                    <tr>
                        <th scope="col" class="text-center rounded-start">Código</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Descripción</th>
                        <th scope="col" class="text-center rounded-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- MostrarLosRegistrosDePropiedades -->

                    <?php foreach ($productos as $producto) : ?>
                        <tr>
                            <td class="text-center fw-semibold"> <?php echo $producto->codigo; ?> </td>
                            <td> <?php echo $producto->nombre; ?> </td>
                            <td> <?php echo $producto->descripcion; ?> </td>
                            <td class="text-center">
                                <!-- Div de Acciones     -->
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="/actividad/admin?producto_id=<?php echo $producto->id; ?>"
                                        class="btn btn-sm btn-success rounded-pill px-3"
                                        title="Agregar actividad">
                                        <i class="bi bi-plus-lg"></i>
                                    </a>

                                    <a href="/producto/actualizar?id=<?php echo $producto->id; ?>&resultado_id=<?php echo $resultadoid ?>"
                                        class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                        title="Editar producto">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <form method="POST" action="/producto/eliminar" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo s($producto->id); ?>">
                                        <input type="hidden" name="tipo" value="producto">
                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                            title="Eliminar producto"
                                            onclick="return confirm('¿Estás seguro de eliminar este registro?');">
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