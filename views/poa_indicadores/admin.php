<?php use Model\PoaIndicadores; ?>
<main>
    <div class="header-admin">
        <h1>POA de Indicadores <?php echo s($anio); ?></h1>
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
            PoaIndicadores::BORRADOR  => 'bg-secondary',
            PoaIndicadores::ENVIADO   => 'bg-info text-dark',
            PoaIndicadores::OBSERVADO => 'bg-warning text-dark',
            PoaIndicadores::APROBADO  => 'bg-success',
        ];
        $clase = $map[(int) $estado] ?? 'bg-dark';
        return '<span class="badge ' . $clase . '">' . s(PoaIndicadores::etiquetaEstado($estado)) . '</span>';
    };
    ?>

    <?php if (esCoordinador()) : ?>
        <!-- ===================== VISTA COORDINADOR ===================== -->
        <div class="container">
            <?php $doc = $documentos[0] ?? null; ?>
            <?php if (!$doc) : ?>
                <div class="alert alert-secondary">
                    Aún no has iniciado el POA de Indicadores de tu programa para el año <?php echo s($anio); ?>.
                </div>
                <form method="POST" action="/poa_indicadores/crear" class="d-inline"><?php echo csrf_input(); ?>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
                        <i class="bi bi-journal-plus me-2"></i> Iniciar POA de Indicadores
                    </button>
                </form>
            <?php else : ?>
                <div class="border rounded-3 shadow-sm p-4 my-3" style="background:#fff;max-width:760px;">
                    <p class="mb-2"><strong>Programa:</strong>
                        <?php echo s($programa->nombre ?? ('#' . $doc->programa_id)); ?></p>
                    <p class="mb-2"><strong>Año:</strong> <?php echo s($doc->anio); ?></p>
                    <p class="mb-3"><strong>Estado:</strong> <?php echo $badge($doc->estado); ?></p>

                    <?php if ((int) $doc->estado === PoaIndicadores::OBSERVADO) : ?>
                        <div class="alert alert-warning alert-persistente">
                            El Contador observó tu POA de Indicadores. Realiza los ajustes y vuelve a enviarlo.
                        </div>
                        <?php if (!empty($doc->observacion)) : ?>
                            <div class="alert alert-danger alert-persistente">
                                <i class="bi bi-chat-left-text me-2"></i>
                                <strong>Motivo de la observación:</strong> <?php echo s($doc->observacion); ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif ((int) $doc->estado === PoaIndicadores::ENVIADO) : ?>
                        <div class="alert alert-info">
                            Enviado al Contador. Quedó bloqueado para edición hasta su revisión.
                        </div>
                    <?php elseif ((int) $doc->estado === PoaIndicadores::APROBADO) : ?>
                        <div class="alert alert-success">
                            POA de Indicadores aprobado. Ya no admite cambios.
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="/resultado/admin" class="btn btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-diagram-3 me-2"></i> Gestionar jerarquía e indicadores
                        </a>
                        <a href="/poa_indicadores/revisar?id=<?php echo s($doc->id); ?>" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-eye me-2"></i> Previsualizar
                        </a>
                        <?php if (in_array((int) $doc->estado, [PoaIndicadores::BORRADOR, PoaIndicadores::OBSERVADO], true)) : ?>
                            <form method="POST" action="/poa_indicadores/enviar" class="d-inline"><?php echo csrf_input(); ?>
                                <input type="hidden" name="id" value="<?php echo s($doc->id); ?>">
                                <button type="submit" class="btn btn-primary rounded-pill px-4"
                                    data-confirm="¿Enviar el POA de Indicadores al Contador? No podrás editarlo hasta que lo revise.">
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
                            <th scope="col" class="text-center">Estado</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documentos)) : ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No hay documentos POA de Indicadores.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($documentos as $doc) : ?>
                            <tr>
                                <td><?php echo s($programas[$doc->programa_id] ?? ('#' . $doc->programa_id)); ?></td>
                                <td class="text-center"><?php echo s($doc->anio); ?></td>
                                <td class="text-center"><?php echo $badge($doc->estado); ?></td>
                                <td class="text-center">
                                    <a href="/poa_indicadores/revisar?id=<?php echo s($doc->id); ?>"
                                        class="btn btn-sm btn-primary rounded-pill px-3"
                                        title="Revisar el documento">
                                        <i class="bi bi-search"></i>
                                        <?php echo ((int) $doc->estado === PoaIndicadores::ENVIADO) ? 'Revisar y decidir' : 'Ver detalle'; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php if ((int) $doc->estado === PoaIndicadores::OBSERVADO && !empty($doc->observacion)) : ?>
                                <!-- Banner de la observación: visible mientras el documento esté Observado. -->
                                <tr>
                                    <td colspan="4" class="p-0">
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
