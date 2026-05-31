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
            <div class="d-flex justify-content-end mb-4"> <!-- Contenedor flexible alineado a la derecha -->
                <a href="/ingreso_egreso/crear" class="btn btn-primary rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            </div>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla fuente de financiamiento -->
    <div class="container text-center">
        <table class="ingreso_egreso table table-bordered">


            <thead class="table">
                <tr>
                    <th scope="col" class="text-center rounded-start">Código</th>
                    <th scope="col">Comprobante/Fecha</th>
                    <th scope="col">Tipo</th>
                    <th scope="col" class="text-end">Tipo_Comprobante</th>
                    <th scope="col" class="text-end">Monto</th>
                    <th scope="col" class="text-center rounded-end">Acciones</th> <!-- Borde redondeado derecho -->
                </tr>
            </thead>

            <tbody>
                <!-- Muestra todos los registros -->

                <?php foreach ($oies as $oe) : ?>
                    <tr>

                        <td> <?php echo s($oe->codigo); ?> </td>
                        <td> <?php echo s($oe->comprobante_fecha); ?> </td>
                        <td> <?php echo s($oe->tipo); ?> </td>
                        <?php // Tooltip que aparece en el código de tipo de comprobante 
                        ?>
                        <td title="<?php
                                    foreach ($tipocomprobantes as $tipocomprobante) {
                                        echo $tipocomprobante->codigo == $oe->tipo_comprobante_codigo ? $tipocomprobante->nombre : '';
                                    }
                                    ?>">
                            <?php echo s($oe->tipo_comprobante_codigo); ?> </td>
                        <?php // Fin de aplicación del Tooltip 
                        ?>
                        <td> <?php echo s($oe->comprobante_monto); ?> </td>

                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="/ingreso_egreso/actualizar?id=<?php echo s($oe->id); ?>"
                                    class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                    title="Editar registro">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                <form method="POST" action="/ingreso_egreso/eliminar" class="d-inline"><?php echo csrf_input(); ?>
                                    <input type="hidden" name="id" value="<?php echo s($oe->id); ?>">
                                    <input type="hidden" name="tipo" value="ingreso_egreso">
                                    <button type="submit"
                                        class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                        title="Eliminar registro"
                                        onclick="return confirm('¿Estás seguro de eliminar este registro?');">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>