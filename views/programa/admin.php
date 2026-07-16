<main>
    <div class="header-admin">
        <h1>Administrador de Programas</h1>
        <?php
        if ($resultado) {
            $mensaje = mostrarNotificacion(intval($resultado));
            if ($mensaje) { ?>
                <div class="alert alert-info"><?php echo s($mensaje); ?></div>
        <?php
            }
        }
        ?>
        <div class="row">
            <div class="col">
                <h2>Programas</h2>
            </div>
            <div class="d-flex justify-content-end mb-4"> <!-- Contenedor flexible alineado a la derecha -->
                <a href="/programa/crear" class="btn btn-primary shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            </div>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla Programa -->
    <div class="container">
        <div class="table-responsive rounded-3 shadow-sm"> <!-- Agregado rounded-3 y sombra -->
            <table class="table table-hover align-middle mb-0"> <!-- Quitado table-bordered -->
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-center rounded-start">Código</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Descripción</th>
                        <th scope="col" class="text-center rounded-end">Acciones</th> <!-- Borde redondeado derecho -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($programas as $programa) : ?>
                        <tr>
                            <td class="text-center fw-semibold"> <?php echo s($programa->codigo); ?> </td>
                            <td> <?php
                                    foreach ($tiposprograma as $tprograma) {
                                        echo s($tprograma->id == $programa->tipo_programa_id ? $tprograma->descripcion : '');
                                    }
                                    ?> </td>
                            <td>
                                <?php echo s($programa->nombre); ?>
                                <?php if ((int) ($programa->es_institucional ?? 0) === 1) : ?>
                                    <span class="badge bg-info text-dark ms-1" title="Programa permanente: su presupuesto se forma con las transferencias de los demás programas">Institucional</span>
                                <?php endif; ?>
                            </td>
                            <td> <?php echo s($programa->descripcion); ?> </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="/programa/actualizar?id=<?php echo s($programa->id); ?>"
                                        class="btn btn-sm btn-outline-warning px-3"
                                        title="Editar registro">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <?php // El Institucional es permanente: sin botón eliminar (guarda espejo en el controlador). ?>
                                    <?php if ((int) ($programa->es_institucional ?? 0) !== 1) : ?>
                                    <form method="POST" action="/programa/eliminar" class="d-inline"><?php echo csrf_input(); ?>
                                        <input type="hidden" name="id" value="<?php echo s($programa->id); ?>">
                                        <input type="hidden" name="tipo" value="programa">
                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger px-3"
                                            title="Eliminar registro"
                                            data-confirm="¿Estás seguro de eliminar este registro?">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>