<?php $bloqueado = esCoordinador() && !poaIndicadoresEditable(programaIdPorProducto($productoid)); ?>
<main>
    <div class="header-admin">
        <h1>Administrador de Actividades del Producto
            <?php echo s($objproducto->codigo); ?></h1>
        <?php
        if ($resultado) {
            $mensaje = mostrarNotificacion(intval($resultado));
            if ($mensaje) { ?>
                <p class="alert alert-info"><?php echo s($mensaje); ?></p>
        <?php
            }
        }
        ?>
        <?php if ($bloqueado) : ?>
            <p class="alert alert-warning">
                <i class="bi bi-lock-fill me-2"></i>
                Tu POA de Indicadores está enviado o aprobado y en revisión: no puedes modificar las
                actividades ni sus indicadores hasta que el Contador lo observe.
            </p>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col">
            <h2>Actividades</h2>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <?php if (!$bloqueado) : ?>
                <a href="/actividad/crear?producto_id=<?php echo s($productoid); ?>" class="btn btn-primary rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            <?php endif; ?>
            <a href="/producto/admin?resultado_id=<?php echo s($resultadoid); ?>" class="btn btn-outline-danger rounded-pill px-4 py-2">
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
                            <td class="text-center fw-semibold"> <?php echo s($actividad->codigo); ?> </td>
                            <td> <?php echo s($actividad->nombre); ?> </td>
                            <td> <?php echo s($actividad->descripcion); ?> </td>
                            <td class="text-center">
                                <!-- Div de Acciones     -->
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="/rubro/admin?actividad_id=<?php echo s($actividad->id); ?>"
                                        class="btn btn-sm btn-success rounded-pill px-3"
                                        title="Rubros y rendiciones de la actividad">
                                        <i class="bi bi-plus-lg"></i>
                                    </a>

                                    <?php if (!$bloqueado) : ?>
                                        <a href="/detalle_actividad/editar?actividad_id=<?php echo s($actividad->id); ?>"
                                            class="btn btn-sm btn-info rounded-pill px-3"
                                            title="Indicadores de la actividad">
                                            <i class="bi bi-graph-up"></i>
                                        </a>

                                        <a href="/actividad/actualizar?id=<?php echo s($actividad->id); ?>&producto_id=<?php echo $productoid ?>"
                                            class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                            title="Editar actividad">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        <form method="POST" action="/actividad/eliminar" class="d-inline"><?php echo csrf_input(); ?>
                                            <input type="hidden" name="id" value="<?php echo s($actividad->id); ?>">
                                            <input type="hidden" name="producto_id" value="<?php echo s($actividad->producto_id); ?>">
                                            <button type="submit"
                                                class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                                title="Eliminar actividad"
                                                data-confirm="¿Estás seguro de eliminar este registro?">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
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