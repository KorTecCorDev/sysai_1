<?php use Model\PoaIndicadores; ?>
<main>
    <div class="header-admin d-flex justify-content-between align-items-start flex-wrap">
        <h1>Revisión del POA de Indicadores</h1>
        <a href="/poa_indicadores/admin" class="btn btn-outline-danger rounded-pill px-4 py-2">
            <i class="bi bi-arrow-left-short me-2"></i> Volver
        </a>
    </div>

    <?php
    if ($resultado) {
        $mensaje = mostrarNotificacion(intval($resultado));
        if ($mensaje) { ?>
            <p class="alert alert-warning"><?php echo s($mensaje); ?></p>
    <?php }
    }

    $badgeMap = [
        PoaIndicadores::BORRADOR  => 'bg-secondary',
        PoaIndicadores::ENVIADO   => 'bg-info text-dark',
        PoaIndicadores::OBSERVADO => 'bg-warning text-dark',
        PoaIndicadores::APROBADO  => 'bg-success',
    ];
    $clase = $badgeMap[(int) $doc->estado] ?? 'bg-dark';
    ?>

    <div class="container">
        <div class="border rounded-3 shadow-sm p-3 my-3" style="background:#fff;">
            <div class="row">
                <div class="col-md-6"><strong>Programa:</strong>
                    <?php echo s($programa->nombre ?? ('#' . $doc->programa_id)); ?></div>
                <div class="col-md-3"><strong>Año:</strong> <?php echo s($doc->anio); ?></div>
                <div class="col-md-3"><strong>Estado:</strong>
                    <span class="badge <?php echo $clase; ?>"><?php echo s(PoaIndicadores::etiquetaEstado($doc->estado)); ?></span>
                </div>
            </div>
        </div>

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
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Actividad</th>
                                                <th class="text-center">Meta</th>
                                                <th>Medio de verificación</th>
                                                <th>Supuesto</th>
                                                <th>Responsable</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($nodoP['actividades'] as $nodoA) :
                                                $a = $nodoA['actividad'];
                                                $d = $nodoA['indicador']; ?>
                                                <tr>
                                                    <td><?php echo s($a->codigo); ?> — <?php echo s($a->nombre); ?></td>
                                                    <?php if ($d) : ?>
                                                        <td class="text-center"><?php echo s($d->indicador_medido); ?></td>
                                                        <td><?php echo s($d->medio_verificacion); ?></td>
                                                        <td><?php echo s($d->supuesto); ?></td>
                                                        <td><?php echo s($d->responsable); ?></td>
                                                    <?php else : ?>
                                                        <td colspan="4" class="text-center text-danger">
                                                            <i class="bi bi-exclamation-triangle"></i> Sin indicador capturado
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Acciones de revisión (solo Contador/Admin sobre documento Enviado) -->
        <?php if (!esCoordinador() && (int) $doc->estado === PoaIndicadores::ENVIADO) : ?>
            <div class="border rounded-3 shadow-sm p-4 my-3" style="background:#fff;">
                <h5 class="mb-3">Decisión del Contador</h5>
                <div class="d-flex gap-3 flex-wrap align-items-start">
                    <form method="POST" action="/poa_indicadores/aprobar"><?php echo csrf_input(); ?>
                        <input type="hidden" name="id" value="<?php echo s($doc->id); ?>">
                        <button type="submit" class="btn btn-success rounded-pill px-4"
                            data-confirm="¿Aprobar este POA de Indicadores?">
                            <i class="bi bi-check2-circle me-2"></i> Aprobar
                        </button>
                    </form>
                    <form method="POST" action="/poa_indicadores/observar" class="flex-grow-1" style="max-width:520px;"><?php echo csrf_input(); ?>
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
