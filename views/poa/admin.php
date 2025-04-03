<main>
    <h1>Programa Operativo Anual</h1>
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
            <h2>POA Activos</h2>
        </div>
        <div class="col-2 espacio-btn-agregar">
            <a href="/resultado/crear" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container text-center">
        <table class="usuario table table-bordered">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Código</th>
                    <th scope="col">Programa</th>
                    <th scope="col">Año</th>
                    <th scope="col">Presupuesto</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- MostrarLosRegistrosDePropiedades -->

                <?php foreach ($programas as $programa) : ?>
                    <?php foreach ($poas as $poa) {
                        if ($poa->programa_id === $programa->id) { ?>
                            <tr>
                                <td> <?php echo $programa->id; ?> </td>
                                <td> <?php echo $programa->codigo; ?> </td>
                                <td> <?php echo $programa->nombre; ?> </td>
                                <td> <?php echo 'S./ ' . $poa->presupuesto; ?> </td>
                                <td> <?php echo $poa->estado; ?> </td>
                                <td class="td-acciones">
                                    <!-- Div de Acciones     -->
                                    <div class="btn-acciones">
                                        <div>
                                            <a href="/poa/actualizar?id=<?php echo $poa->id; ?>" class="btn btn-warning btn-actualizar"><i class="bi bi-pen"></i> Actualizar</a>
                                        </div>
                                        <div>
                                            <form method="POST" class="w-100" action="/poa/eliminar">
                                                <input type="hidden" name="id" value="<?php echo $oe->id; ?>">
                                                <input type="hidden" name="tipo" value="poa">
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
                        <?php } ?>
                    <?php } ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>