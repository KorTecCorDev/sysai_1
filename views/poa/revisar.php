<?php use Model\Poa; ?>
<main>
    <div class="header-admin d-flex justify-content-between align-items-start flex-wrap">
        <h1>Revisión del POA Presupuestal</h1>
        <a href="/poa/admin" class="btn btn-outline-danger rounded-pill px-4 py-2">
            <i class="bi bi-arrow-left-short me-2"></i> Volver
        </a>
    </div>

    <?php
    if ($resultado) {
        $mensaje = mostrarNotificacion(intval($resultado));
        // 19/20 (puerta y tope de sobres) y 22 (TC faltantes) persisten; los demás
        // flash se auto-ocultan.
        $clasealerta = in_array(intval($resultado), [19, 20, 22], true)
            ? 'alert alert-warning alert-persistente'
            : 'alert alert-warning';
        if ($mensaje) { ?>
            <p class="<?php echo $clasealerta; ?>"><?php echo s($mensaje); ?></p>
    <?php }
    }

    $badgeMap = [
        Poa::BORRADOR  => 'bg-secondary',
        Poa::ENVIADO   => 'bg-info text-dark',
        Poa::OBSERVADO => 'bg-warning text-dark',
        Poa::APROBADO  => 'bg-success',
    ];
    $clase = $badgeMap[(int) $doc->estado] ?? 'bg-dark';
    $soles = fn($m) => 'S/. ' . number_format((float) $m, 2, '.', ',');
    ?>

    <div class="container">
        <?php $margen = (float) ($topeSobres ?? 0) - (float) $total; ?>
        <div class="border rounded-3 shadow-sm p-3 my-3" style="background:#fff;">
            <div class="row">
                <div class="col-md-4"><strong>Programa:</strong>
                    <?php echo s($programa->nombre ?? ('#' . $doc->programa_id)); ?></div>
                <div class="col-md-2"><strong>Año:</strong> <?php echo s($doc->anio); ?></div>
                <div class="col-md-2"><strong>Total rubros:</strong>
                    <span class="fw-bold text-success"><?php echo s($soles($total)); ?></span></div>
                <div class="col-md-2"><strong>Σ sobres (tope):</strong>
                    <span class="fw-bold"><?php echo s($soles($topeSobres ?? 0)); ?></span></div>
                <div class="col-md-2"><strong>Estado:</strong>
                    <span class="badge <?php echo $clase; ?>"><?php echo s(Poa::etiquetaEstado($doc->estado)); ?></span>
                </div>
            </div>
            <div class="mt-2">
                <span class="badge <?php echo $margen < -0.001 ? 'bg-danger' : 'bg-success'; ?>">
                    <?php echo $margen < -0.001
                        ? 'Excede el tope de sobres por ' . s($soles(-$margen))
                        : 'Margen contra los sobres: ' . s($soles($margen)); ?>
                </span>
            </div>
        </div>

        <?php if ($margen < -0.001) : ?>
            <div class="alert alert-warning alert-persistente">
                <i class="bi bi-exclamation-triangle me-2"></i>
                El total de rubros (<?php echo s($soles($total)); ?>) supera la suma de los sobres
                asignados al programa (<?php echo s($soles($topeSobres ?? 0)); ?>).
                <?php echo ((float) ($topeSobres ?? 0)) <= 0
                    ? 'El programa aún no tiene sobres asignados: primero asigna presupuesto en fuente↔programa.'
                    : 'Este documento no se puede enviar ni aprobar hasta ajustar los rubros o ampliar los sobres.'; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($fechasSinTc)) : ?>
            <div class="alert alert-warning alert-persistente">
                <i class="bi bi-currency-exchange me-2"></i>
                <strong>Rendiciones pendientes de tipo de cambio.</strong>
                Falta TC (USD y/o EUR) que cubra estas fechas de operación:
                <strong><?php echo s(implode(', ', array_map(fn($f) => date('d/m/Y', strtotime($f)), $fechasSinTc))); ?></strong>.
                La aprobación queda bloqueada hasta <a href="/tcambio/admin?moneda=USD" class="alert-link">registrarlos</a>.
            </div>
        <?php endif; ?>

        <?php if (!empty($doc->observacion)) : ?>
            <div class="alert alert-warning alert-persistente">
                <i class="bi bi-chat-left-text me-2"></i>
                <strong>Observación del Contador:</strong> <?php echo s($doc->observacion); ?>
            </div>
        <?php endif; ?>

        <!-- Árbol consolidado (solo lectura) -->
        <?php if (empty($arbol)) : ?>
            <div class="alert alert-secondary">El programa aún no tiene resultados registrados.</div>
        <?php endif; ?>

        <?php foreach ($arbol as $nodoR) : $r = $nodoR['resultado']; ?>
            <div class="card my-3 shadow-sm">
                <div class="card-header bg-light">
                    <strong>Resultado <?php echo s($r->codigo); ?>:</strong> <?php echo s($r->nombre); ?>
                </div>
                <div class="card-body">
                    <?php if (empty($nodoR['productos'])) : ?>
                        <p class="text-muted mb-0">Sin productos.</p>
                    <?php endif; ?>
                    <?php foreach ($nodoR['productos'] as $nodoP) : $p = $nodoP['producto']; ?>
                        <div class="mb-3">
                            <p class="mb-2"><strong>Producto <?php echo s($p->codigo); ?>:</strong> <?php echo s($p->nombre); ?></p>
                            <?php if (empty($nodoP['actividades'])) : ?>
                                <p class="text-muted ms-3">Sin actividades.</p>
                            <?php else : ?>
                                <?php foreach ($nodoP['actividades'] as $nodoA) :
                                    $a = $nodoA['actividad'];
                                    $rubros = $nodoA['rubros']; ?>
                                    <div class="ms-3 mb-2">
                                        <p class="mb-1"><em>Actividad <?php echo s($a->codigo); ?>:</em> <?php echo s($a->nombre); ?></p>
                                        <?php if (empty($rubros)) : ?>
                                            <p class="text-danger ms-3 mb-0">
                                                <i class="bi bi-exclamation-triangle"></i> Sin rubros presupuestados
                                            </p>
                                        <?php else : ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Código</th>
                                                            <th>Rubro</th>
                                                            <th class="text-end">Monto</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php $subtotal = 0; foreach ($rubros as $ru) : $subtotal += (float) $ru->monto; ?>
                                                            <tr>
                                                                <td><?php echo s($ru->codigo); ?></td>
                                                                <td><?php echo s($ru->nombre); ?></td>
                                                                <td class="text-end"><?php echo s($soles($ru->monto)); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        <tr class="table-light">
                                                            <td colspan="2" class="text-end fw-semibold">Subtotal actividad</td>
                                                            <td class="text-end fw-semibold"><?php echo s($soles($subtotal)); ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Acciones de revisión (solo Contador/Admin sobre documento Enviado) -->
        <?php if (!esCoordinador() && (int) $doc->estado === Poa::ENVIADO) : ?>
            <div class="border rounded-3 shadow-sm p-4 my-3" style="background:#fff;">
                <h5 class="mb-3">Decisión del Contador</h5>
                <div class="d-flex gap-3 flex-wrap align-items-start">
                    <form method="POST" action="/poa/aprobar"><?php echo csrf_input(); ?>
                        <input type="hidden" name="id" value="<?php echo s($doc->id); ?>">
                        <button type="submit" class="btn btn-success rounded-pill px-4"
                            data-confirm="¿Aprobar este POA Presupuestal? Las rendiciones del programa quedarán aprobadas y se descontarán del saldo contable.">
                            <i class="bi bi-check2-circle me-2"></i> Aprobar
                        </button>
                    </form>
                    <form method="POST" action="/poa/observar" class="flex-grow-1" style="max-width:520px;"><?php echo csrf_input(); ?>
                        <input type="hidden" name="id" value="<?php echo s($doc->id); ?>">
                        <div class="mb-2">
                            <label for="observacion" class="form-label">Motivo de la observación (obligatorio para devolver)</label>
                            <textarea class="form-control" id="observacion" name="observacion" rows="3"
                                placeholder="Describa qué debe corregir el coordinador..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-warning rounded-pill px-4"
                            data-confirm="¿Devolver el documento al Coordinador con esta observación?">
                            <i class="bi bi-arrow-counterclockwise me-2"></i> Observar y devolver
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
