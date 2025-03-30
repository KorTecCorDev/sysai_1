<main>
    <h1>Rendiciones Actividad <?php echo $actividad->codigo; ?></h1>
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
            <h2>Comprobantes</h2>
        </div>
        <div class="col-2 espacio-btn-agregar">
            <a href="/rendicion/crear?actividad_id=<?php echo $actividad_id; ?>" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
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
                    <th scope="col">Código</th>
                    <th scope="col">Fecha</th>
                    <th scope="col">T/Comprobante</th>
                    <th scope="col">RUC</th>
                    <th scope="col">Razón social</th>
                    <th scope="col">Serie</th>
                    <th scope="col">Número</th>
                    <th scope="col">Detalle</th>
                    <th scope="col">F.Financiamiento</th>
                    <th scope="col">Monto</th>
                    <th scope="col" class="th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- MostrarLosRegistrosDeRendiciones -->

                <?php foreach ($rendiciones as $rendicion) : ?>
                    <tr>
                        <!-- Personalizamos el formato de fecha para mostrarlo en el formato correcto. -->
                        <?php $fechacmte = strtotime($rendicion->fecha_comprobante);
                        $fechafmt = date('d/m/Y', $fechacmte);
                        ?>
                        <td> <?php echo $rendicion->codigo; ?> </td>
                        <td> <?php echo $fechafmt; ?> </td>
                        <td> <?php echo $rendicion->tipo_comprobante; ?> </td>
                        <td> <?php echo $rendicion->ruc; ?> </td>
                        <td> <?php echo $rendicion->razon_social; ?> </td>
                        <td> <?php echo $rendicion->serie; ?> </td>
                        <td> <?php echo $rendicion->numero; ?> </td>
                        <td> <?php echo $rendicion->detalle; ?> </td>
                        <td> <?php echo $rendicion->fuente_financiamiento; ?> </td>
                        <td> S./ <?php echo $rendicion->monto; ?> </td>
                        <td class="td-acciones">
                            <div class="btn-acciones">
                                <div class="actualizar-espacio">
                                    <a href="/rendicion/actualizar?id=<?php echo $rendicion->id; ?>&actividad_id=<?php echo $rendicion->actividad_id; ?>" class="btn btn-warning btn-actualizar peque"><i class="bi bi-pen"></i></a>
                                </div>
                                <div class="eliminar-espacio">
                                    <form method="POST" class="w-100" action="/rendicion/eliminar">
                                        <input type="hidden" name="id" value="<?php echo s($rendicion->id); ?>">
                                        <input type="hidden" name="actividad_id" value="<?php echo s($rendicion->actividad_id); ?>">
                                        <input type="hidden" name="tipo" value="rendicion">
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