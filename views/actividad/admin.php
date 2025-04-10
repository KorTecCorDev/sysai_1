<main>
    <div class="header-admin">
        <h1>Administrador de Actividades del Producto
            <?php echo $objproducto->codigo; ?></h1>
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
            <h2>Actividades</h2>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <a href="/actividad/crear?producto_id=<?php echo $productoid; ?>" class="btn btn-primary rounded-pill shadow-sm">
                <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            <a href="/producto/admin?resultado_id=<?php echo $resultadoid; ?>" class="btn btn-outline-danger rounded-pill px-4 py-2">
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

                    <?php foreach ($actividades as $actividad) : ?>
                        <tr>
                            <td class="text-center fw-semibold"> <?php echo $actividad->codigo; ?> </td>
                            <td> <?php echo $actividad->nombre; ?> </td>
                            <td> <?php echo $actividad->descripcion; ?> </td>
                            <td class="text-center">
                                <!-- Div de Acciones     -->
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="/rubro/admin?actividad_id=<?php echo $actividad->id; ?>"
                                        class="btn btn-sm btn-success rounded-pill px-3"
                                        title="Agregar rubro">
                                        <i class="bi bi-plus-lg"></i>
                                    </a>


                                    <a href="/rendicion/admin?actividad_id=<?php echo $actividad->id; ?>"
                                        class="btn btn-sm btn-success rounded-pill px-3"
                                        title="Agregar comprobante">
                                        <i class="bi bi-receipt"></i>
                                    </a>

                                    <a href="/actividad/actualizar?id=<?php echo $actividad->id; ?>&producto_id=<?php echo $productoid ?>"
                                        class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                        title="Editar actividad">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <form method="POST" action="/actividad/eliminar" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo s($actividad->id); ?>">
                                        <input type="hidden" name="producto_id" value="<?php echo s($actividad->producto_id); ?>">
                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                            title="Eliminar actividad"
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