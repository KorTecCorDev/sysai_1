<main>

    <div class="header-admin">
        <h1>Plan Operativo Anual POA</h1>
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
            <div class="d-flex justify-content-end mb-4"> <!-- Contenedor flexible alineado a la derecha -->
                <a href="/resultado/crear" class="btn btn-primary rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            </div>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container">
        <div class="table-responsive rounded-3 shadow-sm"> <!-- Agregado rounded-3 y sombra -->
            <table class="table table-hover align-middle mb-0"> <!-- Quitado table-bordered -->
                <thead class="table">
                    <tr>
                        <th scope="col" class="text-center rounded-start">Código</th>
                        <th scope="col">Programa</th>
                        <th scope="col">Año</th>
                        <th scope="col" class="text-end">Presupuesto</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-center rounded-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- MostrarLosRegistrosDePropiedades -->

                    <?php foreach ($programas as $programa) : ?>
                        <?php foreach ($poas as $poa) {
                            if ($poa->programa_id === $programa->id) { ?>
                                <tr>
                                    <td class="text-center fw-semibold"> <?php echo $programa->codigo; ?> </td>
                                    <td> <?php echo $programa->nombre; ?> </td>
                                    <td> <?php echo $poa->anio; ?> </td>
                                    <td class="text-end fw-bold text-success"><?php echo 'S./ ' . number_format($poa->presupuesto, 2, '.', ','); ?></td>
                                    <td> <?php echo $poa->estado; ?> </td>
                                    <!-- Div de Acciones     -->
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="/poa/actualizar?id=<?php echo $poa->id; ?>"
                                                class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                                title="Editar registro"><i class="bi bi-pencil-fill"></i></a>
                                            <form method="POST" class="w-100">
                                                <input type="hidden" name="id" value="<?php echo $poa->id; ?>">
                                                <input type="hidden" name="tipo" value="poa">
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                                    title="Eliminar registro"
                                                    onclick="return confirm('¿Estás seguro de eliminar este registro?');">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
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
    </div>
</main>