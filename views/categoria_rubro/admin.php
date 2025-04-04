<main>
    <h1>Administrador de Categoría de Rubros</h1>
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
            <h2>Categorías de Rubro</h2>
        </div>
        <div class="col-2 espacio-btn-agregar">
            <a href="/categoria_rubro/crear" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla propiedades -->
    <div class="container text-center">
        <table class="categoria_rubro table table-bordered">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Código</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Descripción</th>
                    <th scope="col" class="th-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Muestra todos los registros -->

                <?php foreach ($categoria_rubro as $categoria_rubro) : ?>
                    <tr>
                        <td> <?php echo $categoria_rubro->id; ?> </td>
                        <td> <?php echo $categoria_rubro->codigo; ?> </td>
                        <td> <?php echo $categoria_rubro->nombre; ?> </td>
                        <td> <?php echo $categoria_rubro->descripcion; ?> </td>
                        <td class="td-acciones">
                            <!-- Div de Acciones     -->
                            <div class="btn-acciones">
                                <div>
                                    <a href="/categoria_rubro/actualizar?id=<?php echo $categoria_rubro->id; ?>" class="btn btn-warning btn-actualizar"><i class="bi bi-pen"></i> Actualizar</a>
                                </div>
                                <div>
                                    <form method="POST" class="w-100" action="/categoria_rubro/eliminar">
                                        <input type="hidden" name="id" value="<?php echo $categoria_rubro->id; ?>">
                                        <input type="hidden" name="tipo" value="categoria_rubro">
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