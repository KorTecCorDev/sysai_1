<main>
    <div class="header-admin">
        <h1>Administrador de Otros Ingreso/Egreso</h1>
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
                <h2>Otros Ingresos/Egresos</h2>
            </div>
            <div class="col-2 espacio-btn-agregar">
                <a href="/ingreso_egreso/crear" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
            </div>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla fuente de financiamiento -->
    <div class="container text-center">
        <table class="ingreso_egreso table table-bordered">
            <thead>
                <tr>
                    <!-- Sección de OIE -->
                    <th scope="col">ID</th>
                    <th scope="col">Comprobante/Fecha</th>
                    <th scope="col">Código</th>
                    <!-- Sección de OIE -->
                    <!-- Sección de COMPROBANTE OIE -->
                    <th scope="col">Tipo</th>
                    <th scope="col">Tipo_Comprobante</th>
                    <th scope="col">Monto</th>
                    <!-- Sección de COMPROBANTE OIE -->
                    <th scope="col" class="th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Muestra todos los registros -->

                <?php foreach ($oies as $oe) : ?>
                    <tr>
                        <td> <?php echo $oe->id; ?> </td>
                        <td> <?php echo $oe->comprobante_fecha; ?> </td>
                        <td> <?php echo $oe->codigo; ?> </td>
                        <td> <?php echo $oe->tipo; ?> </td>
                        <?php // Tooltip que aparece en el código de tipo de comprobante 
                        ?>
                        <td title="<?php
                                    foreach ($tipocomprobantes as $tipocomprobante) {
                                        echo $tipocomprobante->codigo == $oe->tipo_comprobante_codigo ? $tipocomprobante->nombre : '';
                                    }
                                    ?>">
                            <?php echo $oe->tipo_comprobante_codigo; ?> </td>
                        <?php // Fin de aplicación del Tooltip 
                        ?>
                        <td> <?php echo $oe->comprobante_monto; ?> </td>
                        <td class="td-acciones">
                            <div class="btn-acciones">
                                <div>
                                    <a href="/ingreso_egreso/actualizar?id=<?php echo $oe->id; ?>" class="btn btn-warning btn-actualizar"><i class="bi bi-pen"></i> Actualizar</a>
                                </div>
                                <div>
                                    <form method="POST" class="w-100" action="/ingreso_egreso/eliminar">
                                        <input type="hidden" name="id" value="<?php echo $oe->id; ?>">
                                        <input type="hidden" name="tipo" value="ingreso_egreso">
                                        <div class="btn-eliminar">
                                            <i class="bi bi-trash"></i>
                                            <input type="submit" class="btn" value="Eliminar">
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