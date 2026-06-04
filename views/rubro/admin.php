<?php
// Bloqueo de rubros: si el coordinador ya envió/aprobó su POA Presupuestal, no edita.
$bloqueado = esCoordinador() && !poaPresupuestalEditable(programaIdPorActividad($actividadid));
?>
<main>
    <div class="header-admin">
        <h1>Administrador de Rubros de la Actividad
            <?php echo s($objactividad->codigo); ?></h1>
        <?php
        if ($resultado) {
            $mensaje = mostrarNotificacion(intval($resultado));
            if ($mensaje) { ?>
                <p class="alert alert-info"><?php echo s($mensaje); ?></p>
        <?php
            }
        }
        ?>
        <?php if ($bloqueado) : ?>
            <div class="alert alert-warning alert-persistente">
                <i class="bi bi-lock-fill me-2"></i>
                El POA Presupuestal está enviado o aprobado: los rubros quedaron bloqueados.
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col">
            <h2>Rubros</h2>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <?php if (!$bloqueado) : ?>
                <a href="/rubro/crear?actividad_id=<?php echo s($actividadid); ?>" class="btn btn-primary rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            <?php endif; ?>
            <a href="/actividad/admin?producto_id=<?php echo s($productoid); ?>" class="btn btn-outline-danger rounded-pill px-4 py-2">
                <i class="bi bi-arrow-left-short me-2"></i> Volver
            </a>
        </div>
    </div>
    <!-- Tabla que muestra los registros dentro de la tabla usuario -->
    <div class="container">
        <div class="table-responsive rounded-3 shadow-sm">
            <table class="table table-hover align-middle mb-0">
                <thead class="table">
                    <tr>
                        <th scope="col" class="text-center rounded-start">Código</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Subcategoría</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Monto</th>
                        <th scope="col" class="text-center rounded-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- MostrarLosRegistrosDePropiedades -->

                    <?php foreach ($rubros as $rubro) : ?>
                        <tr>
                            <td class="text-center fw-semibold"> <?php echo s($rubro->codigo); ?> </td>
                            <td> <?php echo s($rubro->categoria_rubro); ?> </td>
                            <td> <?php echo s($rubro->subcategoria_rubro); ?> </td>
                            <td> <?php echo s($rubro->tipo_rubro); ?> </td>
                            <td> <?php echo s($rubro->nombre); ?> </td>
                            <td> <?php echo s($rubro->descripcion); ?> </td>
                            <td><?php echo 'S./ ' . number_format($rubro->monto, 2, '.', ','); ?></td>
                            <td class="td-acciones">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="/rendicion/admin?rubro_id=<?php echo s($rubro->id); ?>"
                                        class="btn btn-sm btn-success rounded-pill px-3"
                                        title="Rendiciones de este rubro">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    <?php if ($bloqueado) : ?>
                                        <span class="text-muted small"><i class="bi bi-lock"></i></span>
                                    <?php else : ?>
                                        <a href="/rubro/actualizar?id=<?php echo s($rubro->id); ?>&actividad_id=<?php echo $actividadid ?>"
                                            class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                            title="Editar rubro">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        <form method="POST" action="/rubro/eliminar" class="d-inline"><?php echo csrf_input(); ?>
                                            <input type="hidden" name="id" value="<?php echo s($rubro->id); ?>">
                                            <input type="hidden" name="tipo" value="rubro">
                                            <button type="submit"
                                                class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                                title="Eliminar rubro"
                                                data-confirm="¿Estás seguro de eliminar este rubro?">
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