<?php use Model\Poa; ?>
<main>
    <div class="header-admin">
        <h1>POA Presupuestal <?php echo s($anio); ?></h1>
        <h4>Plan Operativo Anual — presupuesto por rubros</h4>
        <?php
        if ($resultado) {
            $mensaje = mostrarNotificacion(intval($resultado));
            // 19 (sin sobres) y 20 (tope excedido) deben persistir para que el
            // coordinador entienda qué esperar; los demás flash se auto-ocultan.
            $clasealerta = in_array(intval($resultado), [19, 20], true)
                ? 'alert alert-warning alert-persistente'
                : 'alert alert-info';
            if ($mensaje) { ?>
                <p class="<?php echo $clasealerta; ?>"><?php echo s($mensaje); ?></p>
        <?php
            }
        }
        ?>
    </div>

    <?php
    // Badges por estado para reutilizar en ambas vistas.
    $badge = function ($estado) {
        $map = [
            Poa::BORRADOR  => 'bg-secondary',
            Poa::ENVIADO   => 'bg-info text-dark',
            Poa::OBSERVADO => 'bg-warning text-dark',
            Poa::APROBADO  => 'bg-success',
        ];
        $clase = $map[(int) $estado] ?? 'bg-dark';
        return '<span class="badge ' . $clase . '">' . s(Poa::etiquetaEstado($estado)) . '</span>';
    };
    $soles = fn($m) => 'S/. ' . number_format((float) $m, 2, '.', ',');
    ?>

    <?php if (esCoordinador()) : ?>
        <!-- ===================== VISTA COORDINADOR ===================== -->
        <div class="container">
            <?php $doc = $documentos[0] ?? null; ?>
            <?php $sinSobres = (float) ($topeSobres ?? 0) <= 0; ?>
            <?php if ($sinSobres) : ?>
                <div class="alert alert-warning alert-persistente">
                    <i class="bi bi-envelope-exclamation me-2"></i>
                    <strong>Tu programa aún no tiene sobres asignados.</strong>
                    El Contador debe asignar el presupuesto (fuente↔programa) antes de que puedas
                    registrar rubros, el POA Presupuestal o rendiciones. Mientras tanto puedes seguir
                    elaborando tu POA de Indicadores y la jerarquía de resultados.
                </div>
            <?php endif; ?>
            <?php if (!$doc) : ?>
                <div class="alert alert-secondary">
                    Aún no has iniciado el POA Presupuestal de tu programa para el año <?php echo s($anio); ?>.
                    Primero registra los rubros en la jerarquía; el presupuesto se calcula de ellos.
                </div>
                <p class="mb-2"><strong>Total de rubros actual:</strong> <?php echo s($soles($presupuestoVivo)); ?></p>
                <?php if (!$sinSobres) : ?>
                    <p class="mb-2"><strong>Suma de sobres asignados (tope del POA):</strong>
                        <?php echo s($soles($topeSobres)); ?></p>
                    <form method="POST" action="/poa/crear" class="d-inline"><?php echo csrf_input(); ?>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
                            <i class="bi bi-journal-plus me-2"></i> Iniciar POA Presupuestal
                        </button>
                    </form>
                <?php endif; ?>
            <?php else : ?>
                <div class="border rounded-3 shadow-sm p-4 my-3" style="background:#fff;max-width:760px;">
                    <p class="mb-2"><strong>Programa:</strong>
                        <?php echo s($programa->nombre ?? ('#' . $doc->programa_id)); ?></p>
                    <p class="mb-2"><strong>Año:</strong> <?php echo s($doc->anio); ?></p>
                    <p class="mb-2"><strong>Presupuesto del documento:</strong>
                        <span class="fw-bold text-success"><?php echo s($soles($doc->presupuesto)); ?></span></p>
                    <?php if ($doc->esEditable()) : ?>
                        <p class="mb-2 text-muted"><small>Total de rubros vigente: <?php echo s($soles($presupuestoVivo)); ?>
                            (se congela al enviar)</small></p>
                    <?php endif; ?>
                    <?php $margen = (float) ($topeSobres ?? 0) - (float) $presupuestoVivo; ?>
                    <p class="mb-2"><strong>Suma de sobres asignados (tope del POA):</strong>
                        <?php echo s($soles($topeSobres ?? 0)); ?>
                        <?php if ($doc->esEditable() && !$sinSobres) : ?>
                            <span class="badge <?php echo $margen < 0 ? 'bg-danger' : 'bg-success'; ?> ms-2">
                                <?php echo $margen < 0
                                    ? 'Excede por ' . s($soles(-$margen))
                                    : 'Margen: ' . s($soles($margen)); ?>
                            </span>
                        <?php endif; ?>
                    </p>
                    <?php if ($doc->esEditable() && $margen < -0.001 && !$sinSobres) : ?>
                        <div class="alert alert-warning alert-persistente">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            El total de rubros (<?php echo s($soles($presupuestoVivo)); ?>) supera la suma de
                            tus sobres (<?php echo s($soles($topeSobres)); ?>). Ajusta los rubros antes de enviar:
                            excedes por <strong><?php echo s($soles(-$margen)); ?></strong>.
                        </div>
                    <?php endif; ?>
                    <p class="mb-3"><strong>Estado:</strong> <?php echo $badge($doc->estado); ?></p>

                    <?php if ((int) $doc->estado === Poa::OBSERVADO) : ?>
                        <div class="alert alert-warning alert-persistente">
                            El Contador observó tu POA Presupuestal. Realiza los ajustes en los rubros y vuelve a enviarlo.
                        </div>
                        <?php if (!empty($doc->observacion)) : ?>
                            <div class="alert alert-danger alert-persistente">
                                <i class="bi bi-chat-left-text me-2"></i>
                                <strong>Motivo de la observación:</strong> <?php echo s($doc->observacion); ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif ((int) $doc->estado === Poa::ENVIADO) : ?>
                        <div class="alert alert-info">
                            Enviado al Contador. Los rubros quedaron bloqueados hasta su revisión.
                        </div>
                    <?php elseif ((int) $doc->estado === Poa::APROBADO) : ?>
                        <div class="alert alert-success">
                            POA Presupuestal aprobado. Los rubros ya no admiten cambios (solo el Contador como adenda).
                            Tus rendiciones quedaron <strong>aprobadas</strong> y descontadas del saldo contable.
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="/resultado/admin" class="btn btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-diagram-3 me-2"></i> Gestionar jerarquía y rubros
                        </a>
                        <a href="/poa/revisar?id=<?php echo s($doc->id); ?>" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-eye me-2"></i> Previsualizar
                        </a>
                        <?php if (in_array((int) $doc->estado, [Poa::BORRADOR, Poa::OBSERVADO], true)) : ?>
                            <form method="POST" action="/poa/enviar" class="d-inline"><?php echo csrf_input(); ?>
                                <input type="hidden" name="id" value="<?php echo s($doc->id); ?>">
                                <button type="submit" class="btn btn-primary rounded-pill px-4"
                                    data-confirm="¿Enviar el POA Presupuestal al Contador? No podrás editar los rubros hasta que lo revise.">
                                    <i class="bi bi-send me-2"></i> Enviar al Contador
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <?php else : ?>
        <!-- ===================== VISTA CONTADOR / ADMIN ===================== -->
        <div class="container">

            <?php if (!empty($institucional)) : ?>
                <!-- Panel de elaboración del Programa Institucional (migr. 033): el
                     Contador lo opera directamente — espejo del panel del coordinador. -->
                <?php $sinSobresInst = (float) ($topeSobresInst ?? 0) <= 0; ?>
                <div class="border rounded-3 shadow-sm p-4 mb-4" style="background:#fff;">
                    <h5 class="mb-3"><i class="bi bi-building me-2"></i>Programa Institucional
                        <span class="badge bg-info text-dark ms-1">a tu cargo</span></h5>
                    <?php if ($sinSobresInst) : ?>
                        <div class="alert alert-secondary mb-2">
                            El Institucional aún no tiene presupuesto: registra transferencias al asignar
                            los sobres de cada programa en <a href="/dfinanciamiento/crear">Fuentes ↔ Programas</a>.
                        </div>
                    <?php else : ?>
                        <?php $margenInst = (float) $topeSobresInst - (float) $presupuestoVivoInst; ?>
                        <p class="mb-1"><strong>Presupuesto (Σ transferencias):</strong>
                            <?php echo s($soles($topeSobresInst)); ?>
                            <span class="badge <?php echo $margenInst < 0 ? 'bg-danger' : 'bg-success'; ?> ms-2">
                                <?php echo $margenInst < 0
                                    ? 'Rubros exceden por ' . s($soles(-$margenInst))
                                    : 'Margen: ' . s($soles($margenInst)); ?>
                            </span>
                        </p>
                        <p class="mb-2"><strong>Total de rubros actual:</strong> <?php echo s($soles($presupuestoVivoInst)); ?></p>
                        <?php if (!$docInstitucional) : ?>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="/resultado/admin?programa_id=<?php echo s($institucional->id); ?>"
                                   class="btn btn-outline-primary rounded-pill px-4">
                                    <i class="bi bi-diagram-3 me-2"></i> Gestionar jerarquía y rubros
                                </a>
                                <form method="POST" action="/poa/crear" class="d-inline"><?php echo csrf_input(); ?>
                                    <input type="hidden" name="programa_id" value="<?php echo s($institucional->id); ?>">
                                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                                        <i class="bi bi-journal-plus me-2"></i> Iniciar POA Institucional <?php echo s($anio); ?>
                                    </button>
                                </form>
                            </div>
                        <?php else : ?>
                            <p class="mb-2"><strong>Documento <?php echo s($docInstitucional->anio); ?>:</strong>
                                <?php echo $badge($docInstitucional->estado); ?>
                                <span class="text-muted ms-2">Presupuesto del documento:
                                    <?php echo s($soles($docInstitucional->presupuesto)); ?></span></p>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="/resultado/admin?programa_id=<?php echo s($institucional->id); ?>"
                                   class="btn btn-outline-primary rounded-pill px-4">
                                    <i class="bi bi-diagram-3 me-2"></i> Gestionar jerarquía y rubros
                                </a>
                                <a href="/poa/revisar?id=<?php echo s($docInstitucional->id); ?>"
                                   class="btn btn-outline-secondary rounded-pill px-4">
                                    <i class="bi bi-eye me-2"></i> Previsualizar
                                </a>
                                <?php if (in_array((int) $docInstitucional->estado, [Poa::BORRADOR, Poa::OBSERVADO], true)) : ?>
                                    <form method="POST" action="/poa/enviar" class="d-inline"><?php echo csrf_input(); ?>
                                        <input type="hidden" name="id" value="<?php echo s($docInstitucional->id); ?>">
                                        <button type="submit" class="btn btn-primary rounded-pill px-4"
                                            data-confirm="¿Enviar el POA Institucional a revisión? El presupuesto se congela con los rubros vigentes.">
                                            <i class="bi bi-send me-2"></i> Enviar a revisión
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive rounded-3 shadow-sm">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table">
                        <tr>
                            <th scope="col">Programa</th>
                            <th scope="col" class="text-center">Año</th>
                            <th scope="col" class="text-end">Presupuesto</th>
                            <th scope="col" class="text-end">Σ sobres (tope)</th>
                            <th scope="col" class="text-center">Estado</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documentos)) : ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">No hay documentos POA Presupuestal.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($documentos as $doc) : ?>
                            <?php $topeDoc = (float) (($topesSobres ?? [])[$doc->programa_id] ?? 0); ?>
                            <tr>
                                <td><?php echo s($programas[$doc->programa_id] ?? ('#' . $doc->programa_id)); ?></td>
                                <td class="text-center"><?php echo s($doc->anio); ?></td>
                                <td class="text-end fw-bold text-success"><?php echo s($soles($doc->presupuesto)); ?></td>
                                <td class="text-end <?php echo ((float) $doc->presupuesto > $topeDoc + 0.001) ? 'fw-bold text-danger' : ''; ?>"
                                    <?php if ((float) $doc->presupuesto > $topeDoc + 0.001) : ?>
                                        title="El presupuesto del POA supera la suma de los sobres del programa"
                                    <?php endif; ?>>
                                    <?php echo s($soles($topeDoc)); ?>
                                </td>
                                <td class="text-center"><?php echo $badge($doc->estado); ?></td>
                                <td class="text-center">
                                    <a href="/poa/revisar?id=<?php echo s($doc->id); ?>"
                                        class="btn btn-sm btn-primary rounded-pill px-3"
                                        title="Revisar el documento">
                                        <i class="bi bi-search"></i>
                                        <?php echo ((int) $doc->estado === Poa::ENVIADO) ? 'Revisar y decidir' : 'Ver detalle'; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php if ((int) $doc->estado === Poa::OBSERVADO && !empty($doc->observacion)) : ?>
                                <tr>
                                    <td colspan="6" class="p-0">
                                        <div class="alert alert-warning alert-persistente rounded-0 mb-0">
                                            <i class="bi bi-chat-left-text me-2"></i>
                                            <strong>Observación registrada:</strong> <?php echo s($doc->observacion); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>
