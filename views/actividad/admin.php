<main>
    <h1>Administrador de Actividades</h1>
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
            <h2>Actividades</h2>
        </div>
        <div class="col-2 espacio-btn-agregar">
            <a href="/actividad/crear?producto_id=<?php echo $productoid; ?>" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
        </div>
        <div class="col-2 espacio-btn-volver">
            <a href="/producto/admin?resultado_id=<?php echo $resultadoid; ?>" class="btn btn-primary btn-volver"><i class="bi bi-arrow-bar-left"></i> Volver</a>
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

                <?php foreach ($actividades as $actividad) : ?>
                    <tr>
                        <td> <?php echo $actividad->id; ?> </td>
                        <td> <?php echo $actividad->codigo; ?> </td>
                        <td> <?php echo $actividad->nombre; ?> </td>
                        <td> <?php echo $actividad->descripcion; ?> </td>
                        <td class="td-acciones">
                            <!-- Div de Acciones     -->
                            <div class="btn-acciones">
                                <div>
                                    <a href="/rubro/admin?actividad_id=<?php echo $actividad->id; ?>" class="btn btn-warning btn-add"><i class="bi bi-clipboard2-plus" title="Agregar rubro"></i></a>
                                </div>
                                <div>
                                    <a href="/rendicion/admin?actividad_id=<?php echo $actividad->id; ?>" class="btn btn-warning btn-add"><i class="bi bi-folder-plus" title="Agregar rendiciones"></i></a>
                                </div>
                                <div>
                                    <a href="/actividad/actualizar?id=<?php echo $actividad->id; ?>&producto_id=<?php echo $productoid ?>" class="btn btn-warning btn-actualizar" title="Actualizar"><i class="bi bi-pen"></i> Actualizar</a>
                                </div>
                                <div>
                                    <form method="POST" class="w-100" action="/actividad/eliminar">
                                        <input type="hidden" name="id" value="<?php echo s($actividad->id); ?>">
                                        <input type="hidden" name="producto_id" value="<?php echo s($actividad->producto_id); ?>">
                                        <input type="hidden" name="tipo" value="actividad">
                                        <div class="btn-eliminar">
                                            <i class="bi bi-trash"></i>
                                            <input type="submit" class="btn" value="Eliminar" title="Eliminar">
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