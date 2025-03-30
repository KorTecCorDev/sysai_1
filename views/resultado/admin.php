<main>
    <h1>Administrador de Resultados</h1>
    <?php
    if ($resultado) {
        $mensaje = mostrarNotificacion(intval($resultado));
        if ($mensaje) { ?>
            <p class="alert alert-info"><?php echo s($mensaje); ?></p>
    <?php
        }
    }
    ?>

    <?php

    ?>
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
        <div class="col-2 espacio-btn-agregar">
            <a href="/resultado/crear?programa_id=<?php echo $idprograma; ?>" class="btn btn-primary btn-agregar"><i class="bi bi-plus-circle"></i> Agregar</a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container text-center p-0">
        <table class="table table-bordered">
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
                <!-- MostrarLosRegistrosDePropiedades -->

                <?php foreach ($resultados as $resul) : ?>
                    <tr>
                        <td> <?php echo $resul->id; ?> </td>
                        <td> <?php echo $resul->codigo; ?> </td>
                        <td> <?php echo $resul->nombre; ?> </td>
                        <td> <?php echo $resul->descripcion; ?> </td>
                        <td class="td-acciones">
                            <div class="btn-acciones">
                                <!-- Botón de agregar Producto     -->
                                <div>
                                    <a href="/producto/admin?resultado_id=<?php echo $resul->id; ?>" class="btn btn-warning btn-add"><i class="bi bi-clipboard2-plus"></i></a>
                                </div>
                                <!-- Fin de Botón de agregar Producto -->
                                <div>
                                    <a href="/resultado/actualizar?id=<?php echo $resul->id; ?>&programa_id=<?php echo $idprograma; ?>" class="btn btn-warning btn-actualizar"><i class="bi bi-pen"></i> Actualizar</a>
                                </div>
                                <div>
                                    <form method="POST" class="w-100" action="/resultado/eliminar">
                                        <input type="hidden" name="id" value="<?php echo s($resul->id); ?>">
                                        <input type="hidden" name="tipo" value="resultado">
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