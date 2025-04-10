<main>
    <div class="header-admin">
        <h1>Administrador de Rendiciones de la Actividad
            <?php echo $actividad->codigo; ?></h1>
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
            <h2>Comprobantes</h2>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <a href="/rendicion/crear?actividad_id=<?php echo $actividad_id; ?>" class="btn btn-primary rounded-pill shadow-sm">
                <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            <a href="/actividad/admin?producto_id=<?php echo $producto_id; ?>" class="btn btn-outline-danger rounded-pill px-4 py-2">
                <i class="bi bi-arrow-left-short me-2"></i> Volver
            </a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container text-center p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col" class="text-center rounded-start">Código</th>
                    <th scope="col">Fecha</th>
                    <th scope="col">T/Comprobante</th>
                    <th scope="col">RUC</th>
                    <th scope="col">Razón social</th>
                    <th scope="col">Serie</th>
                    <th scope="col">Número</th>
                    <th scope="col">Detalle</th>
                    <th scope="col">F.Financiamiento</th>
                    <th scope="col">Monto</th>
                    <th scope="col" class="text-center rounded-end">Acciones</th>
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
                        <td><?php echo 'S./ ' . number_format($rendicion->monto, 2, '.', ','); ?></td>
                        <td class="td-acciones">
                            <!-- Div de Acciones     -->
                            <div class="d-flex justify-content-center gap-2">
                                <a href="/rendicion/actualizar?id=<?php echo $rendicion->id; ?>&actividad_id=<?php echo $actividad_id ?>"
                                    class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                    title="Editar rendición">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                <form method="POST" class="d-inline" action="/rendicion/eliminar">
                                    <input type="hidden" name="id" value="<?php echo s($rendicion->id); ?>">
                                    <input type="hidden" name="tipo" value="rubro">
                                    <button type="submit"
                                        class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                        title="Eliminar rendición"
                                        onclick="return confirm('¿Estás seguro de eliminar esta rendición?');">
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
</main>