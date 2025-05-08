<main>
    <div class="header-admin">
        <h1>Administrador de Tipo de Cambio Dolar</h1>
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
                <h2>Tipo de Cambio Dólar</h2>
            </div>
            <div class="d-flex justify-content-end mb-4"> <!-- Contenedor flexible alineado a la derecha -->
                <a href="/tcambio/dolar/crear" class="btn btn-primary rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            </div>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla TipoCambioDolar -->
    <div class="container text-center p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">Usuario</th>
                    <th scope="col">Tipo de Cambio</th>
                    <th scope="col">Fecha</th>
                    <th scope="col" class="text-center rounded-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- MostrarLosRegistrosDeTipoCambioDolar -->
                <?php foreach ($tiposCambioDolar as $tipocambio) : ?>
                    <tr>
                        <td> <?php echo $tipocambio->usuario; ?> </td>
                        <td> <?php echo $tipocambio->tipo_cambio; ?> </td>
                        <td> <?php echo $tipocambio->fecha; ?> </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="/tcambio/dolar/actualizar?id=<?php echo $tipocambio->id; ?>"
                                    class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                    title="Editar registro">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                <form method="POST" action="/tcambio/dolar/eliminar" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $tipocambio->id; ?>">
                                    <input type="hidden" name="tipo" value="tipocambiodolar">
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