<main>
    <h1>Administrador de Programas</h1>
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
            <h2>Programas</h2>
        </div>
        <div class="col-2 espacio-btn-agregar">
            <a href="/programa/crear" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla Programa -->
    <div class="container text-center p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Código</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Descripción</th>
                    <th scope="col" class="th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- MostrarLosRegistrosDePropiedades -->
                <?php foreach ($programas as $programa) : ?>
                    <tr>
                        <td> <?php echo $programa->id; ?> </td>
                        <td> <?php echo $programa->codigo; ?> </td>
                        <td> <?php
                                foreach ($tiposprograma as $tprograma) {
                                    echo $tprograma->id == $programa->tipo_programa_id ? $tprograma->descripcion : '';
                                }
                                ?> </td>
                        <td> <?php echo $programa->nombre; ?> </td>
                        <td> <?php echo $programa->descripcion; ?> </td>
                        <td class="td-acciones">
                            <!-- Div de Acciones     -->
                            <div class="btn-acciones">
                                <div>
                                    <a href="/programa/actualizar?id=<?php echo $programa->id; ?>" class="btn btn-warning btn-actualizar"><i class="bi bi-pen"></i> Actualizar</a>
                                </div>
                                <div>
                                    <form method="POST" class="w-100" action="/producto/eliminar">
                                        <input type="hidden" name="id" value="<?php echo $programa->id; ?>">
                                        <input type="hidden" name="tipo" value="programa">
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
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>