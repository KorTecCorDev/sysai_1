<main>
    <div class="header-admin">
        <h1>Fuentes de Financiamiento y Programas</h1>
        <p class="text-muted">Relacione un programa con sus fuentes de financiamiento y asigne el sobre (monto reservado) de cada una.</p>

        <?php
        // Notificación de resultado (creado / eliminado…)
        if ($resultado) {
            $mensaje = mostrarNotificacion(intval($resultado));
            if ($mensaje) { ?>
                <div class="alert alert-info"><?php echo s($mensaje); ?></div>
        <?php
            }
        }
        ?>

        <?php // Errores de validación, inline (sin modales apilados)
        if (!empty($errores)) : ?>
            <div class="alert alert-danger">
                <strong class="d-block mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Revisa lo siguiente:</strong>
                <ul class="mb-0">
                    <?php foreach ($errores as $error) : ?>
                        <li><?php echo s($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <!-- Selección de programa -->
    <div class="row">
        <div class="col-12 col-md-6 col-lg-4">
            <?php include __DIR__ . '/formulario.php'; ?>
        </div>
    </div>

    <?php $tienePrograma = ($programaSeleccionado ?? null) !== null; ?>

    <?php if (!$tienePrograma) : ?>
        <div class="alert alert-secondary d-flex align-items-center gap-2 mt-2">
            <i class="bi bi-arrow-up-circle-fill"></i>
            <span>Selecciona un programa para vincular sus fuentes de financiamiento.</span>
        </div>
    <?php endif; ?>

    <!-- Grilla de fuentes de financiamiento -->
    <div class="row g-3 mt-1 <?php echo $tienePrograma ? '' : 'opacity-50 pe-none'; ?>">
        <?php foreach ($fuentesfinanciamiento as $ff) :
            $d            = ($desglose ?? [])[(int) $ff->id] ?? ['presupuesto' => 0.0, 'comprometido' => 0.0];
            $presupuesto  = (float) $d['presupuesto'];
            $comprometido = (float) $d['comprometido'];
            $disponible   = $presupuesto - $comprometido;
            $vinculada    = array_key_exists((int) $ff->id, ($vinculos ?? []));
            $montoSobre   = $vinculada ? (float) $vinculos[(int) $ff->id] : 0.0;
            $uso          = $presupuesto > 0 ? min(100, max(0, ($comprometido / $presupuesto) * 100)) : 0;
            $usoTono      = $uso >= 100 ? 'danger' : ($uso >= 80 ? 'warning' : 'success');
        ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="card h-100 shadow-sm <?php echo $vinculada ? 'border-success' : ''; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <span class="fw-semibold text-truncate" title="<?php echo s($ff->nombre); ?>"><?php echo s($ff->nombre); ?></span>
                        <?php if ($vinculada) : ?>
                            <span class="badge bg-success flex-shrink-0"><i class="bi bi-check-circle me-1"></i>Vinculada</span>
                        <?php else : ?>
                            <span class="badge bg-secondary flex-shrink-0">Disponible</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body d-flex flex-column">
                        <!-- Desglose del presupuesto de la fuente -->
                        <ul class="list-group list-group-flush small mb-2">
                            <li class="list-group-item d-flex justify-content-between px-0 py-1">
                                <span class="text-muted">Presupuesto</span>
                                <span class="fw-semibold"><?php echo soles($presupuesto); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-1">
                                <span class="text-muted">Comprometido</span>
                                <span class="fw-semibold"><?php echo soles($comprometido); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-1 border-top">
                                <span class="fw-semibold">Disponible</span>
                                <span class="fw-bold text-<?php echo $disponible > 0 ? 'success' : 'danger'; ?>"><?php echo soles($disponible); ?></span>
                            </li>
                        </ul>

                        <!-- Barra de uso del presupuesto -->
                        <div class="progress mb-3" role="progressbar"
                             aria-label="Presupuesto comprometido de la fuente"
                             aria-valuenow="<?php echo round($uso); ?>" aria-valuemin="0" aria-valuemax="100"
                             style="height: 6px;">
                            <div class="progress-bar bg-<?php echo $usoTono; ?>" style="width: <?php echo $uso; ?>%;"></div>
                        </div>

                        <?php if ($vinculada) : ?>
                            <!-- Vinculada: monto del sobre + acción Quitar -->
                            <div class="text-center mb-3">
                                <div class="text-muted small">Sobre asignado a este programa</div>
                                <div class="fs-5 fw-bold text-success"><?php echo soles($montoSobre); ?></div>
                            </div>
                            <form method="POST" class="mt-auto"><?php echo csrf_input(); ?>
                                <input type="hidden" name="detalle_financiamiento[fuente_financiamiento_id]" value="<?php echo s($ff->id); ?>">
                                <input type="hidden" name="detalle_financiamiento[programa_id]" value="<?php echo s($programaSeleccionado->id); ?>">
                                <input type="hidden" name="detalle_financiamiento[tipo]" value="detalle_financiamiento">
                                <button type="submit" class="btn btn-outline-danger w-100"
                                        data-confirm="¿Quitar el vínculo de esta fuente con el programa?">
                                    <i class="bi bi-x-circle me-1"></i>Quitar
                                </button>
                            </form>
                        <?php else : ?>
                            <!-- No vinculada: capturar el sobre + acción Agregar -->
                            <?php $sinCupo = $disponible <= 0; ?>
                            <form method="POST" class="mt-auto"><?php echo csrf_input(); ?>
                                <input type="hidden" name="detalle_financiamiento[fuente_financiamiento_id]" value="<?php echo s($ff->id); ?>">
                                <input type="hidden" name="detalle_financiamiento[programa_id]" value="<?php echo s($tienePrograma ? $programaSeleccionado->id : ''); ?>">
                                <input type="hidden" name="detalle_financiamiento[tipo]" value="detalle_financiamiento">
                                <label class="form-label small mb-1" for="monto-<?php echo s($ff->id); ?>">Monto a asignar (S/)</label>
                                <input type="number" step="0.01" min="0" max="<?php echo $disponible; ?>"
                                       class="form-control form-control-sm mb-1" id="monto-<?php echo s($ff->id); ?>"
                                       name="detalle_financiamiento[monto_asignado]" placeholder="0.00"
                                       <?php echo $sinCupo ? 'disabled' : ''; ?>>
                                <div class="form-text mb-2">Disponible en la fuente: <?php echo soles($disponible); ?></div>
                                <button type="submit" class="btn btn-primary w-100" <?php echo $sinCupo ? 'disabled' : ''; ?>>
                                    <i class="bi bi-plus-circle me-1"></i>Agregar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
