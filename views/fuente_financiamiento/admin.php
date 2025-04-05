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
            <div class="d-flex justify-content-end mb-4"> <!-- Contenedor flexible alineado a la derecha -->
                <a href="/fuente_financiamiento/crear" class="btn btn-primary rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            </div>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla fuente de financiamiento -->
    <div class="container">
        <div class="table-responsive rounded-3 shadow-sm"> <!-- Agregado rounded-3 y sombra -->
            <table class="table table-hover align-middle mb-0"> <!-- Quitado table-bordered -->
                <thead class="table">
                    <tr>
                        <th scope="col" class="text-center rounded-start">Código</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Descripción</th>
                        <th scope="col" class="text-end">Presupuesto</th>
                        <th scope="col" class="text-center rounded-end">Acciones</th> <!-- Borde redondeado derecho -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fuente_financiamiento as $fuente_financiamiento) : ?>
                        <tr>
                            <td class="text-center fw-semibold"><?php echo $fuente_financiamiento->codigo; ?></td>
                            <td><?php echo $fuente_financiamiento->nombre; ?></td>
                            <td><?php echo $fuente_financiamiento->descripcion; ?></td>
                            <td class="text-end fw-bold text-success"><?php echo 'S./ ' . number_format($fuente_financiamiento->presupuesto, 2, '.', ','); ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="/fuente_financiamiento/actualizar?id=<?php echo $fuente_financiamiento->id; ?>"
                                        class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                        title="Editar registro">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <form method="POST" action="/fuente_financiamiento/eliminar" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $fuente_financiamiento->id; ?>">
                                        <input type="hidden" name="tipo" value="fuente_financiamiento">
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
    </div>

</main>