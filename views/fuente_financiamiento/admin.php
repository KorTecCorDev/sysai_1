<main>
    <div class="header-admin">
        <h1>Administrador de Fuentes de Financiamiento</h1>
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
                <h2>Fuentes de Financiamiento</h2>
            </div>
            <div class="col-2 espacio-btn-agregar">
                <a href="/fuente_financiamiento/crear" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
            </div>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla fuente de financiamiento -->
    <div class="container text-center">
        <table class="fuente_financiamiento table table-bordered">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Código</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Descripción</th>
                    <th scope="col">Presupuesto</th>
                    <th scope="col" class="th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Muestra todos los registros -->

                <?php foreach ($fuente_financiamiento as $fuente_financiamiento) : ?>
                    <tr>
                        <td> <?php echo $fuente_financiamiento->id; ?> </td>
                        <td> <?php echo $fuente_financiamiento->codigo; ?> </td>
                        <td> <?php echo $fuente_financiamiento->nombre; ?> </td>
                        <td> <?php echo $fuente_financiamiento->descripcion; ?> </td>
                        <td> <?php echo 'S./ ' . number_format($fuente_financiamiento->presupuesto, 2, '.', ','); ?> </td>
                        <td class="td-acciones">
                            <div class="btn-acciones">
                                <div>
                                    <a href="/fuente_financiamiento/actualizar?id=<?php echo $fuente_financiamiento->id; ?>" class="btn btn-warning btn-actualizar"><i class="bi bi-pen"></i> Actualizar</a>
                                </div>
                                <div>
                                    <form method="POST" class="w-100" action="/fuente_financiamiento/eliminar">
                                        <input type="hidden" name="id" value="<?php echo $fuente_financiamiento->id; ?>">
                                        <input type="hidden" name="tipo" value="fuente_financiamiento">
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