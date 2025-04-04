<main>
    <h1>Administrador de Productos</h1>
    <?php
    if ($resultado) {
        $mensaje = mostrarNotificacion(intval($resultado));
        if ($mensaje) { ?>
            <p class="alert alert-info"><?php echo s($mensaje); ?></p>
    <?php
        }
    }
    ?>

    <?php

    ?>
    <div class="row">
        <div class="col">
            <h2>Productos</h2>
        </div>
        <div class="col-2 espacio-btn-agregar">
            <a href="/producto/crear?resultado_id=<?php echo $resultadoid; ?>" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
        </div>
        <div class="col-2 espacio-btn-volver">
            <a href="/resultado/admin?programa_id=<?php echo $programaid; ?>" class="btn btn-primary btn-volver"><i class="bi bi-arrow-bar-left"></i> Volver</a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container text-center p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Código</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Descripción</th>
                    <th scope="col" class="th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- MostrarLosRegistrosDePropiedades -->

                <?php foreach ($productos as $producto) : ?>
                    <tr>
                        <td> <?php echo $producto->id; ?> </td>
                        <td> <?php echo $producto->codigo; ?> </td>
                        <td> <?php echo $producto->nombre; ?> </td>
                        <td> <?php echo $producto->descripcion; ?> </td>
                        <td class="td-acciones">
                            <!-- Div de Acciones     -->
                            <div class="btn-acciones">
                                <div>
                                    <a href="/actividad/admin?producto_id=<?php echo $producto->id; ?>" class="btn btn-warning btn-add"><i class="bi bi-clipboard2-plus"></i></a>
                                </div>
                                <div>
                                    <a href="/producto/actualizar?id=<?php echo $producto->id; ?>&resultado_id=<?php echo $resultadoid ?>" class="btn btn-warning btn-actualizar"><i class="bi bi-pen"></i> Actualizar</a>
                                </div>
                                <div>
                                    <form method="POST" class="w-100" action="/producto/eliminar">
                                        <input type="hidden" name="id" value="<?php echo s($producto->id); ?>">
                                        <input type="hidden" name="resultado_id" value="<?php echo s($producto->resultado_id); ?>">
                                        <input type="hidden" name="tipo" value="resultado">
                                        <div class="btn-eliminar">
                                            <i class="bi bi-trash"></i>
                                            <input type="submit" class="btn" value="Eliminar">
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <!-- Div de Acciones     -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>