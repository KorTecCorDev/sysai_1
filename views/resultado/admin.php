<main>
    <div class="header-admin">
        <h1>Administrador de Resultados del Programa
            <?php foreach ($programas as $programa) { ?>
            <?php echo $programa->id == $idprograma ? $programa->codigo : '';
            } ?></h1>
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
    <div class="row justify-content-between align-items-center header-admin">
        <div class="">
            <h2>Resultados</h2>
        </div>

        <div class="col-4 select-programs mb-2">
            <label for="" class="form-label">Seleccione el programa</label>
            <select class="form-select w-auto" name="" id="programslist">
                <option value="" selected>--Seleccione--</option>
                <?php foreach ($programas as $programa) { ?>
                    <option value="<?php echo s($programa->id); ?>" <?php echo $programa->id == $idprograma ? 'selected' : ''; ?>><?php echo s($programa->nombre) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="d-flex justify-content-end mb-4"> <!-- Contenedor flexible alineado a la derecha -->
            <a href="/resultado/crear?programa_id=<?php echo $idprograma; ?>" class="btn btn-primary rounded-pill shadow-sm">
                <i class="bi bi-plus-circle me-2"></i>Agregar</a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container">
        <div class="table-responsive rounded-3 shadow-sm">
            <table class="table table-hover align-middle mb-0">
                <thead class="table">
                    <tr>
                        <th scope="col" class="text-center rounded-start">Código</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Descripción</th>
                        <th scope="col" class="text-center rounded-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- MostrarLosRegistrosDePropiedades -->

                    <?php foreach ($resultados as $resul) : ?>
                        <tr>
                            <td class="text-center fw-semibold"> <?php echo $resul->codigo; ?> </td>
                            <td> <?php echo $resul->nombre; ?> </td>
                            <td> <?php echo $resul->descripcion; ?> </td>
                            <td class="text-center">
                                <!-- Div de Acciones     -->
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="/resultado/actualizar?id=<?php echo $resul->id; ?>&programa_id=<?php echo $idprograma; ?>"
                                        class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                        title="Editar registro">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <form method="POST" action="/resultado/eliminar" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo s($resul->id); ?>">
                                        <input type="hidden" name="tipo" value="resultado">
                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                            title="Eliminar registro"
                                            onclick="return confirm('¿Estás seguro de eliminar este registro?');">
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
    </div>
</main>