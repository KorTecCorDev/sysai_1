<main>
    <div class="header-admin">
        <h1>Rendiciones del Rubro <?php echo s($rubro->codigo); ?></h1>
        <h4><?php echo s($rubro->nombre); ?></h4>
        <?php
        if ($resultado) {
            $mensaje = mostrarNotificacion(intval($resultado));
            if ($mensaje) { ?>
                <p class="alert alert-info"><?php echo s($mensaje); ?></p>
        <?php
            }
        }
        ?>
        <?php if (!empty($bloqueado)) : ?>
            <div class="alert alert-warning alert-persistente">
                <i class="bi bi-lock-fill me-2"></i>
                El POA Presupuestal está enviado o aprobado: no se pueden registrar rendiciones en este rubro.
            </div>
        <?php endif; ?>
    </div>

    <!-- Panel de saldo del rubro (plan de montos §2.3): saldo CON SIGNO, referencial.
         El rubro no limita el gasto (el tope real es el sobre); negativo = sobregasto. -->
    <div class="container mb-3">
        <div class="row g-2 text-center">
            <div class="col-md-4">
                <div class="border rounded-3 p-2 bg-light"><small>Monto del rubro (planificado)</small><br>
                    <span class="fw-bold">S/. <?php echo number_format($rubro->monto, 2, '.', ','); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-2 bg-light"><small>Total rendido</small><br>
                    <span class="fw-bold">S/. <?php echo number_format($totalRendido, 2, '.', ','); ?></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-2 <?php echo $disponible >= -0.001 ? 'bg-success-subtle' : 'bg-danger-subtle'; ?>">
                    <small><?php echo $disponible >= -0.001 ? 'Saldo del rubro (sobrante)' : 'Saldo del rubro (SOBREGASTO)'; ?></small><br>
                    <span class="fw-bold">S/. <?php echo number_format($disponible, 2, '.', ','); ?></span>
                </div>
            </div>
        </div>
        <?php if ($disponible < -0.001) : ?>
            <div class="alert alert-warning alert-persistente mt-2 mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Las rendiciones superan el monto planificado del rubro por
                <strong>S/. <?php echo number_format(-$disponible, 2, '.', ','); ?></strong>.
                Es una advertencia: el tope real del gasto es el saldo del sobre (programa, fuente).
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col">
            <h2>Comprobantes</h2>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <?php if (empty($bloqueado)) : ?>
                <a href="/rendicion/crear?rubro_id=<?php echo s($rubro_id); ?>" class="btn btn-primary rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            <?php endif; ?>
            <a href="/rubro/admin?actividad_id=<?php echo s($rubro->actividad_id); ?>" class="btn btn-outline-danger rounded-pill px-4 py-2">
                <i class="bi bi-arrow-left-short me-2"></i> Volver
            </a>
        </div>
    </div>
    <!-- Tabla de comprobantes de rendición -->
    <div class="container text-center p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col" class="text-center rounded-start">Código</th>
                    <th scope="col">Fecha</th>
                    <th scope="col">T/Comprobante</th>
                    <th scope="col">RUC</th>
                    <th scope="col">Razón social</th>
                    <th scope="col">Serie</th>
                    <th scope="col">Número</th>
                    <th scope="col">Detalle</th>
                    <th scope="col">F.Financiamiento</th>
                    <th scope="col">Monto</th>
                    <th scope="col" class="text-center rounded-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rendiciones)) : ?>
                    <tr><td colspan="11" class="text-muted py-3">No hay rendiciones para este rubro.</td></tr>
                <?php endif; ?>
                <?php foreach ($rendiciones as $rendicion) : ?>
                    <tr>
                        <?php $fechacmte = strtotime($rendicion->fecha_comprobante);
                        $fechafmt = date('d/m/Y', $fechacmte); ?>
                        <td> <?php echo s($rendicion->codigo); ?> </td>
                        <td> <?php echo s($fechafmt); ?> </td>
                        <td> <?php echo s($rendicion->tipo_comprobante); ?> </td>
                        <td> <?php echo s($rendicion->ruc); ?> </td>
                        <td> <?php echo s($rendicion->razon_social); ?> </td>
                        <td> <?php echo s($rendicion->serie); ?> </td>
                        <td> <?php echo s($rendicion->numero); ?> </td>
                        <td> <?php echo s($rendicion->detalle); ?> </td>
                        <td> <?php echo s($rendicion->fuente_financiamiento); ?> </td>
                        <td><?php echo 'S./ ' . number_format($rendicion->monto, 2, '.', ','); ?></td>
                        <td class="td-acciones">
                            <div class="d-flex justify-content-center gap-2">
                                <?php if (!empty($bloqueado)) : ?>
                                    <span class="text-muted small"><i class="bi bi-lock"></i></span>
                                <?php else : ?>
                                    <a href="/rendicion/actualizar?id=<?php echo s($rendicion->id); ?>&rubro_id=<?php echo s($rubro_id); ?>"
                                        class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                        title="Editar rendición">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <form method="POST" class="d-inline" action="/rendicion/eliminar"><?php echo csrf_input(); ?>
                                        <input type="hidden" name="id" value="<?php echo s($rendicion->id); ?>">
                                        <input type="hidden" name="tipo" value="rubro">
                                        <button type="submit"
                                            class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                            title="Eliminar rendición"
                                            data-confirm="¿Estás seguro de eliminar esta rendición?">
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
</main>
