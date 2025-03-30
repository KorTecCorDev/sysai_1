<main>
    <h1>Elección de Fuente de Financiamiento para la Rendicion <?php echo $respt[0]->actividad_codigo; ?></h1>
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
            <h3 title="<?php echo ($actividad_nombre); ?>" >Actividad <?php echo $respt[0]->actividad_codigo ?> </h3>
        </div>
        <div class="col">
            <h3>Monto rendido S./ <?php echo $resptrendiff[0]->total_monto_rendiciones ?></h3>
        </div>
        <div class="col-2 espacio-btn-agregar">
            <a href="/rendicionff/crear?actividad_id=<?php echo $actividad_id; ?>" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
        </div>
        <div class="col-2 espacio-btn-volver">
            <a href="/actividad/admin?producto_id=<?php echo $producto_id; ?>" class="btn btn-primary btn-volver"><i class="bi bi-arrow-bar-left"></i> Volver</a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container text-center p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">Código/Actividad</th>
                    <th scope="col">Fuente_financiamiento</th>
                    <th scope="col">Monto</th>
                    <th scope="col" class="th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- MostrarLosRegistrosDePropiedades -->

                <?php foreach ($particiones as $particion) : ?>
                    <tr>
                        <td> <?php echo $particion->actividad_id; ?> </td>
                        <td> <?php echo $particion->ff_id; ?> </td>
                        <td> <?php echo "S./ {$particion->monto}"; ?> </td>
                        <td class="td-acciones">
                            <div class="btn-acciones">
                                <div class="actualizar-espacio">
                                    <a href="/rendicionff/actualizar?id=<?php echo $particion->id; ?>&actividad_id=<?php echo $particion->actividad_id; ?>" class="btn btn-warning btn-actualizar peque"><i class="bi bi-pen"></i></a>
                                </div>
                                <div class="eliminar-espacio">
                                    <form method="POST" class="w-100" action="/rendicionff/eliminar">
                                        <input type="hidden" name="id" value="<?php echo s($particion->id); ?>">
                                        <input type="hidden" name="actividad_id" value="<?php echo s($particion->actividad_id); ?>">
                                        <input type="hidden" name="tipo" value="rendicionff">
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