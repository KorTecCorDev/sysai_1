<main>
    <div class="header-admin">
        <h1>Plan Operativo Anual POA</h1>
        <h4>Gestión de Estados de los Planes Operativos Anuales</h4>
        <?php
        if ($resultado) {
            $mensaje = mostrarNotificacion(intval($resultado));
            if ($mensaje) { ?>
                <p class="alert alert-info"> <?php echo s($mensaje); ?> </p>
        <?php
            }
        }
        ?>
    </div>
    <div class="container text-center">
        <div class="table-responsive rounded-3 shadow-sm">
            <table class="table table-hover align-middle mb-0">
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
                    <?php foreach ($programas as $programa) : ?>
                        <?php foreach ($poas as $poa) {
                            if ($poa->programa_id === $programa->id) { ?>
                                <tr>
                                    <td class="text-center fw-semibold"> <?php echo $programa->codigo; ?> </td>
                                    <td> <?php echo $programa->nombre; ?> </td>
                                    <td> <?php echo $poa->anio; ?> </td>
                                    <td class="text-end fw-bold text-success">S/. <?php echo number_format($poa->presupuesto, 2, '.', ','); ?></td>
                                    <td>
                                        <?php if ($poa->estado == 0) { ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-hourglass-split"></i> En espera
                                            </span>
                                        <?php } else if ($poa->estado == 1) { ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Completado
                                            </span>
                                        <?php } else if ($poa->estado == 2) { ?>
                                            <span class="badge bg-info text-dark">
                                                <i class="bi bi-search"></i> Verificado
                                            </span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn" style="background-color: #4A90E2;" data-bs-toggle="modal" data-bs-target="#modalConfirm<?php echo $poa->id; ?>">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal de Confirmación -->
                                <div class="modal fade oculto" id="modalConfirm<?php echo $poa->id; ?>" tabindex="-1" aria-labelledby="modalConfirmLabel<?php echo $poa->id; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modalConfirmLabel<?php echo $poa->id; ?>">Confirmar Cambio de Estado</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>¿Está seguro de que desea cambiar el estado de este registro? Esta acción tiene carácter de <strong>declaración jurada</strong> y será registrada.</p>
                                                <form method="POST" action="/reporte/modificarpoa?id=<?php echo $poa->id; ?>">
                                                    <input type="hidden" name="id" value="<?php echo $poa->id; ?>">
                                                    <input type="hidden" name="tipo" value="poa">
                                                    <select name="estado" class="form-select mb-3">
                                                        <?php if ($poa->estado == 0) { ?>
                                                            <option value="1">Completado</option>
                                                            <option value="2">Verificado</option>
                                                        <?php } elseif ($poa->estado == 1) { ?>
                                                            <option value="2">Verificado</option>
                                                        <?php } elseif ($poa->estado == 2) { ?>
                                                            <option value="1">Completado</option>
                                                        <?php } ?>
                                                    </select>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" required>
                                                        <label class="form-check-label">Confirmo que esta declaración es verdadera</label>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary mt-3">Confirmar</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Fin del Modal de Confirmación -->
                        <?php }
                        } ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>