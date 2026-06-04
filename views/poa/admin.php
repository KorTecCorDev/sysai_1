<?php use Model\Poa; ?>
<main>
    <div class="header-admin">
        <h1>POA Presupuestal <?php echo s($anio); ?></h1>
        <h4>Plan Operativo Anual — presupuesto por rubros</h4>
        <?php
        if ($resultado) {
            $mensaje = mostrarNotificacion(intval($resultado));
            if ($mensaje) { ?>
                <p class="alert alert-info"><?php echo s($mensaje); ?></p>
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
            <?php if (!$doc) : ?>
                <div class="alert alert-secondary">
                    Aún no has iniciado el POA Presupuestal de tu programa para el año <?php echo s($anio); ?>.
                    Primero registra los rubros en la jerarquía; el presupuesto se calcula de ellos.
                </div>
                <p class="mb-2"><strong>Total de rubros actual:</strong> <?php echo s($soles($presupuestoVivo)); ?></p>
                <form method="POST" action="/poa/crear" class="d-inline"><?php echo csrf_input(); ?>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
                        <i class="bi bi-journal-plus me-2"></i> Iniciar POA Presupuestal
                    </button>
                </form>
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
            <div class="table-responsive rounded-3 shadow-sm">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table">
                        <tr>
                            <th scope="col">Programa</th>
                            <th scope="col" class="text-center">Año</th>
                            <th scope="col" class="text-end">Presupuesto</th>
                            <th scope="col" class="text-center">Estado</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documentos)) : ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No hay documentos POA Presupuestal.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($documentos as $doc) : ?>
                            <tr>
                                <td><?php echo s($programas[$doc->programa_id] ?? ('#' . $doc->programa_id)); ?></td>
                                <td class="text-center"><?php echo s($doc->anio); ?></td>
                                <td class="text-end fw-bold text-success"><?php echo s($soles($doc->presupuesto)); ?></td>
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
                                    <td colspan="5" class="p-0">
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
