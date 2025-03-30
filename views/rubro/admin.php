<main>
    <h1>Administrador de Rubros</h1>
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
            <h2>Rubros</h2>
        </div>
        <div class="col-2 espacio-btn-agregar">
            <a href="/rubro/crear?actividad_id=<?php echo $actividadid; ?>" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
        </div>
        <div class="col-2 espacio-btn-volver">
            <a href="/actividad/admin?producto_id=<?php echo $productoid; ?>" class="btn btn-primary btn-volver"><i class="bi bi-arrow-bar-left"></i> Volver</a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container text-center p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Categoría</th>
                    <th scope="col">Subcategoría</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Código</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Descripción</th>
                    <th scope="col">Monto</th>
                    <th scope="col" class="th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- MostrarLosRegistrosDePropiedades -->

                <?php foreach ($rubros as $rubro) : ?>
                    <tr>
                        <td> <?php echo $rubro->id; ?> </td>
                        <td> <?php echo $rubro->categoria_rubro; ?> </td>
                        <td> <?php echo $rubro->subcategoria_rubro; ?> </td>
                        <td> <?php echo $rubro->tipo_rubro; ?> </td>
                        <td> <?php echo $rubro->codigo; ?> </td>
                        <td> <?php echo $rubro->nombre; ?> </td>
                        <td> <?php echo $rubro->descripcion; ?> </td>
                        <td> S./ <?php echo $rubro->monto; ?> </td>
                        <td class="td-acciones">
                            <div class="btn-acciones">
                                <div class="actualizar-espacio">
                                    <a href="/rubro/actualizar?id=<?php echo $rubro->id; ?>&actividad_id=<?php echo $actividadid ?>" class="btn btn-warning btn-actualizar peque"><i class="bi bi-pen"></i></a>
                                </div>
                                <div class="eliminar-espacio">
                                    <form method="POST" class="w-100" action="/rubro/eliminar">
                                        <input type="hidden" name="id" value="<?php echo s($rubro->id); ?>">
                                        <input type="hidden" name="actividad_id" value="<?php echo s($actividadid); ?>">
                                        <input type="hidden" name="tipo" value="rubro">
                                        <div class="btn-eliminar peque">
                                            <i class="bi bi-trash"></i>
                                            <input type="submit" class="btn peque" value="">
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
