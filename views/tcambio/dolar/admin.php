<main>
    <h1>Administrador de Tipos de Cambio Dólar</h1>
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
            <h2>Tipos de Cambio Dólar</h2>
        </div>
        <div class="col-2 espacio-btn-agregar">
            <a href="/tcambio/dolar/crear" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla TipoCambioDolar -->
    <div class="container text-center p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Tipo de Cambio</th>
                    <th scope="col">Fecha</th>
                    <th scope="col" class="th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- MostrarLosRegistrosDeTipoCambioDolar -->
                <?php foreach ($tiposCambioDolar as $tipocambio) : ?>
                    <tr>
                        <td> <?php echo $tipocambio->id; ?> </td>
                        <td> <?php echo $tipocambio->usuario; ?> </td>
                        <td> <?php echo $tipocambio->tipo_cambio; ?> </td>
                        <td> <?php echo $tipocambio->fecha; ?> </td>
                        <td class="td-acciones">
                            <div class="btn-acciones">
                                <div>
                                    <a href="/tcambio/dolar/actualizar?id=<?php echo $tipocambio->id; ?>" class="btn btn-warning btn-actualizar"><i class="bi bi-pen"></i>Actualizar</a>
                                </div>
                                <div>
                                    <form method="POST" class="w-100" action="/tcambio/dolar/eliminar">
                                        <input type="hidden" name="id" value="<?php echo $tipocambio->id; ?>">
                                        <input type="hidden" name="tipo" value="tdolar">
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