<body>
    <main>
        <div class="container mt-5">
            <?php
            if (!empty($resultado)) {
                $mensaje = mostrarNotificacion(intval($resultado));
                if ($mensaje) { ?>
                    <p class="alert alert-info"><?php echo s($mensaje); ?></p>
            <?php }
            }
            ?>

            <!-- Item 8 — Cierre anual: snapshot por fuente en fuente_presupuesto_anual.
                 Manual (lo dispara el Contador), re-cerrable con aviso, y las
                 rendiciones pendientes advierten sin bloquear (decisiones 2026-07-16). -->
            <div class="d-flex justify-content-end mb-3">
                <?php
                $avisos = [];
                if (!empty($fechaCierre)) {
                    $avisos[] = 'Ya existe un cierre del ' . date('d/m/Y H:i', strtotime($fechaCierre)) . ': se REEMPLAZARÁ con las cifras actuales.';
                }
                if (!empty($rendicionesPendientes)) {
                    $avisos[] = 'Hay ' . (int) $rendicionesPendientes . ' rendición(es) PENDIENTE(S) (POAs sin aprobar) que aún no descuentan el contable.';
                }
                $confirmCierre = '¿Registrar el cierre del año ' . s($anio ?? date('Y')) . '? '
                    . 'Se guardará el snapshot por fuente (inicial, comprometido y contable). '
                    . implode(' ', array_map('s', $avisos));
                ?>
                <form method="POST" action="/cierre_anual/guardar"><?php echo csrf_input(); ?>
                    <button type="submit" class="btn btn-outline-primary rounded-pill px-4"
                            data-confirm="<?php echo $confirmCierre; ?>">
                        <i class="bi bi-archive me-2"></i>
                        Registrar cierre del año <?php echo s($anio ?? date('Y')); ?>
                        <?php if (!empty($fechaCierre)) : ?>
                            <span class="badge bg-secondary ms-1">re-cierre</span>
                        <?php endif; ?>
                    </button>
                </form>
            </div>

            <!-- Bloque 1: Saldos generales -->
            <div class="row mb-5">
                <div class="col-12">
                    <h3 class="mb-4 text-center">Saldos Generales</h3>
                </div>
                <!-- Ingresos Totales -->
                <div class="col-md-4">
                    <div class="card text-white bg-success p-3">
                        <div class="card-body text-center">
                            <i class="bi bi-arrow-up-circle icon"></i>
                            <h4 class="card-title">Ingresos Totales</h4>
                            <h2 id="ingresosTotales"><?php echo soles($ingresos); ?></h2>
                        </div>
                    </div>
                </div>
                <!-- Egresos Totales -->
                <div class="col-md-4">
                    <div class="card text-white bg-danger p-3">
                        <div class="card-body text-center">
                            <i class="bi bi-arrow-down-circle icon"></i>
                            <h4 class="card-title">Egresos Totales</h4>
                            <h2 id="egresosTotales"><?php echo soles($egresos); ?></h2>
                        </div>
                    </div>
                </div>
                <!-- Saldo Total: azul si es superávit, ámbar si es déficit (negativo) -->
                <?php
                $saldoNegativo = ((float) $saldo) < 0;
                $saldoClase    = $saldoNegativo ? 'bg-warning text-dark' : 'bg-primary text-white';
                $saldoIcono    = $saldoNegativo ? 'bi-exclamation-triangle' : 'bi-cash-stack';
                ?>
                <div class="col-md-4">
                    <div class="card <?php echo $saldoClase; ?> p-3">
                        <div class="card-body text-center">
                            <i class="bi <?php echo $saldoIcono; ?> icon"></i>
                            <h4 class="card-title">Saldo Total</h4>
                            <h2 id="saldoTotal"><?php echo soles($saldo); ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Conversión al cierre (Fase 4, plan de montos): compra vigente a hoy,
                     con tasa, fecha de vigencia y origen SIEMPRE visibles — la
                     desactualización se ve, no se esconde. Sin TC: solo soles. -->
                <div class="col-12 mt-2">
                    <?php
                    $lineasTc = [];
                    foreach (['USD' => ($tcUsdCierre ?? null), 'EUR' => ($tcEurCierre ?? null)] as $mon => $tc) {
                        if ($tc) {
                            $convertido = convertirMoneda($saldo, $tc->compra);
                            $tasaTxt = rtrim(rtrim(number_format((float) $tc->compra, 6, '.', ''), '0'), '.');
                            $antiguedad = (int) floor((strtotime(date('Y-m-d')) - strtotime($tc->fecha_vigencia)) / 86400);
                            $lineasTc[] = '<strong>' . s($mon) . ' ' . number_format((float) $convertido, 2)
                                . '</strong> <span class="text-muted">(TC compra ' . s($tasaTxt)
                                . ' del ' . s(date('d/m/Y', strtotime($tc->fecha_vigencia)))
                                . ' · ' . s($tc->origen) . ')</span>'
                                . ($antiguedad > 7 ? ' <span class="badge bg-warning text-dark">TC de hace ' . $antiguedad . ' días</span>' : '');
                        } else {
                            $lineasTc[] = '<strong>' . s($mon) . '</strong> <span class="text-muted">sin tipo de cambio registrado</span>'
                                . ' <a href="/tcambio/admin?moneda=' . s($mon) . '" class="ms-1">registrar</a>';
                        }
                    }
                    ?>
                    <!-- .alert-persistente: es un banner de contenido, no un flash —
                         sin la clase, app.js lo desvanece a los 3 segundos. -->
                    <div class="alert alert-light alert-persistente border text-center mb-0">
                        <i class="bi bi-currency-exchange me-2"></i>
                        Saldo total convertido al cierre: <?php echo implode(' &nbsp;·&nbsp; ', $lineasTc); ?>
                    </div>
                </div>
            </div>

            <!-- Bloque 2: Comparativo de saldos por fuente (gráfico SVG inline, CSP-safe) -->
            <?php if (!empty($saldofuentes)) :
                // Copia ordenada de mayor a menor saldo para el comparativo.
                $orden = $saldofuentes;
                usort($orden, fn($a, $b) => $b->fuente_financiamiento_saldo <=> $a->fuente_financiamiento_saldo);

                // Escala por valor absoluto: la longitud comunica magnitud; el color, el signo.
                $maxAbs = 0.0;
                foreach ($orden as $f) {
                    $maxAbs = max($maxAbs, abs((float) $f->fuente_financiamiento_saldo));
                }
                $maxAbs = $maxAbs > 0 ? $maxAbs : 1.0;

                // Geometría del lienzo (viewBox responsivo).
                $gutterIzq = 120;   // etiquetas de código de fuente
                $gutterDer = 130;   // etiquetas de valor
                $anchoTotal = 820;
                $anchoPlot = $anchoTotal - $gutterIzq - $gutterDer;
                $altoFila = 34;
                $altoBarra = 18;
                $altoTotal = count($orden) * $altoFila + 12;

                // Paleta validada (dataviz): azul secuencial para superávit, rojo estado para déficit.
                $cBarra  = '#2a78d6';
                $cNeg    = '#d03b3b';
                $cPrim   = '#0b0b0b';
                $cSec    = '#52514e';
                $cEje    = '#c3c2b7';
            ?>
                <div class="row mb-5">
                    <div class="col-12">
                        <h3 class="mb-4 text-center">Comparativo de Saldos por Fuente</h3>
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <svg viewBox="0 0 <?php echo $anchoTotal; ?> <?php echo $altoTotal; ?>"
                                     width="100%" height="auto" preserveAspectRatio="xMidYMid meet"
                                     role="img" aria-label="Gráfico de barras: saldo disponible por fuente de financiamiento, ordenado de mayor a menor."
                                     style="max-width:100%;">
                                    <title>Saldo por fuente de financiamiento</title>
                                    <!-- Eje base (cero) -->
                                    <line x1="<?php echo $gutterIzq; ?>" y1="0" x2="<?php echo $gutterIzq; ?>" y2="<?php echo $altoTotal - 12; ?>"
                                          stroke="<?php echo $cEje; ?>" stroke-width="1"></line>
                                    <?php foreach ($orden as $i => $f) :
                                        $val    = (float) $f->fuente_financiamiento_saldo;
                                        $ancho  = $anchoPlot * (abs($val) / $maxAbs);
                                        $ancho  = max($ancho, 2); // barra mínima visible
                                        $yFila  = $i * $altoFila;
                                        $yBarra = $yFila + ($altoFila - $altoBarra) / 2;
                                        $yCentro = $yFila + $altoFila / 2;
                                        $color  = $val < 0 ? $cNeg : $cBarra;
                                    ?>
                                        <!-- Etiqueta de fuente -->
                                        <text x="<?php echo $gutterIzq - 8; ?>" y="<?php echo $yCentro; ?>"
                                              text-anchor="end" dominant-baseline="central"
                                              font-size="12" font-weight="600" fill="<?php echo $cSec; ?>"
                                              font-family="system-ui, -apple-system, 'Segoe UI', sans-serif"><?php echo s($f->fuente_financiamiento_codigo); ?></text>
                                        <!-- Barra (extremo redondeado 4px) -->
                                        <rect x="<?php echo $gutterIzq; ?>" y="<?php echo $yBarra; ?>"
                                              width="<?php echo round($ancho, 2); ?>" height="<?php echo $altoBarra; ?>"
                                              rx="4" ry="4" fill="<?php echo $color; ?>"></rect>
                                        <!-- Valor -->
                                        <text x="<?php echo $gutterIzq + $ancho + 8; ?>" y="<?php echo $yCentro; ?>"
                                              text-anchor="start" dominant-baseline="central"
                                              font-size="12" fill="<?php echo $cPrim; ?>"
                                              font-family="system-ui, -apple-system, 'Segoe UI', sans-serif"
                                              style="font-variant-numeric: tabular-nums;"><?php echo soles($val); ?></text>
                                    <?php endforeach; ?>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Bloque 3: Detalle y desglose por fuente de financiamiento -->
            <div class="row">
                <div class="col-12">
                    <h3 class="mb-4 text-center">Detalle por Fuente de Financiamiento</h3>
                </div>
                <?php if (!empty($saldofuentes)) : ?>
                    <?php foreach ($saldofuentes as $fuente) :
                        $desg = $desglosefuentes[$fuente->fuente_financiamiento_id] ?? [
                            'presupuesto' => 0.0, 'ingresos' => 0.0, 'ingresos_libres' => 0.0, 'egresos' => 0.0,
                            'rendiciones' => 0.0, 'comprometido' => 0.0, 'vigente' => 0.0, 'capacidad_asignable' => 0.0,
                        ];
                        $presupuesto = $desg['presupuesto'];            // inicial (inmutable)
                        $vigente     = $desg['vigente'];                // inicial + todos los ingresos
                        $comprometido = $desg['comprometido'];          // Σ sobres asignados a programas
                        $capacidad   = $desg['capacidad_asignable'];    // inicial + ingresos libres − Σ sobres
                        $saldoFuente = (float) $fuente->fuente_financiamiento_saldo;

                        // Disponibilidad: saldo respecto al presupuesto VIGENTE de la fuente.
                        // Sin presupuesto: 100% si el saldo es no negativo, 0% si es negativo.
                        $ratio  = $vigente > 0 ? ($saldoFuente / $vigente) * 100 : ($saldoFuente >= 0 ? 100 : 0);
                        $ancho  = max(0, min(100, $ratio)); // ancho de la barra acotado a [0,100]

                        // Semáforo de salud según disponibilidad
                        if ($saldoFuente < 0 || $ratio < 15) {
                            $tono = 'danger';
                        } elseif ($ratio < 50) {
                            $tono = 'warning';
                        } else {
                            $tono = 'success';
                        }
                        $tonoTexto = $tono === 'warning' ? 'text-dark' : 'text-white';
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="card border-<?php echo $tono; ?> h-100 shadow fuente-saldo-card">
                                <div class="card-header bg-<?php echo $tono; ?> <?php echo $tonoTexto; ?> text-center fuente-saldo-header">
                                    <strong><?php echo s($fuente->fuente_financiamiento_codigo); ?> - <?php echo s($fuente->fuente_financiamiento_nombre); ?></strong>
                                </div>
                                <div class="card-body text-center">
                                    <span class="fw-bold">Saldo disponible:</span>
                                    <h4 class="text-<?php echo $tono; ?> mt-2"><?php echo soles($saldoFuente); ?></h4>
                                    <div class="progress mt-2" role="progressbar"
                                         aria-label="Disponibilidad sobre el presupuesto"
                                         aria-valuenow="<?php echo round($ancho); ?>" aria-valuemin="0" aria-valuemax="100"
                                         style="height: 10px;">
                                        <div class="progress-bar bg-<?php echo $tono; ?>" style="width: <?php echo $ancho; ?>%;"></div>
                                    </div>
                                    <small class="text-muted d-block mt-2 mb-3">
                                        <?php echo round($ratio); ?>% disponible de <?php echo soles($vigente); ?> (vigente)
                                    </small>

                                    <!-- Desglose del saldo (C1) -->
                                    <ul class="list-group list-group-flush text-start small">
                                        <li class="list-group-item d-flex justify-content-between px-0 py-1">
                                            <span>Presupuesto inicial</span>
                                            <span class="fw-semibold"><?php echo soles($desg['presupuesto']); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0 py-1">
                                            <span class="text-success">(+) Ingresos</span>
                                            <span class="fw-semibold text-success"><?php echo soles($desg['ingresos']); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0 py-1">
                                            <span class="text-danger">(−) Rendiciones aprobadas</span>
                                            <span class="fw-semibold text-danger"><?php echo soles($desg['rendiciones']); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0 py-1">
                                            <span class="text-danger">(−) Otros egresos</span>
                                            <span class="fw-semibold text-danger"><?php echo soles($desg['egresos']); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0 py-1 border-top">
                                            <span class="fw-bold">= Saldo contable</span>
                                            <span class="fw-bold text-<?php echo $tono; ?>"><?php echo soles($saldoFuente); ?></span>
                                        </li>
                                    </ul>

                                    <!-- Las tres cifras del presupuesto (migr. 027, plan de montos §2.4) -->
                                    <div class="mt-2 pt-2 border-top text-start small">
                                        <div class="fw-bold text-muted mb-1">Presupuesto y asignación</div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span>Presupuesto vigente</span>
                                            <span class="fw-semibold"><?php echo soles($vigente); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span>Comprometido (Σ sobres)</span>
                                            <span class="fw-semibold"><?php echo soles($comprometido); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span>Capacidad asignable</span>
                                            <span class="fw-semibold <?php echo $capacidad < -0.001 ? 'text-danger' : ''; ?>"><?php echo soles($capacidad); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="col-12">
                        <div class="alert alert-warning text-center">No hay fuentes de financiamiento registradas.</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bloque 4: Saldos por SOBRE (sub-presupuesto programa × fuente) -->
            <div class="row mt-5">
                <div class="col-12">
                    <h3 class="mb-2 text-center">Saldos por Sobre (Programa × Fuente)</h3>
                    <p class="text-muted text-center small mb-4">
                        Cada sobre es la porción del presupuesto de una fuente reservada a un programa.
                        Saldo contable = asignado + ingresos − egresos − rendiciones aprobadas.
                    </p>
                    <?php if (!empty($saldosobres)) : ?>
                        <div class="card shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-variant-numeric: tabular-nums;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Programa</th>
                                            <th>Fuente</th>
                                            <th class="text-end">Asignado</th>
                                            <th class="text-end">(+) Ingresos</th>
                                            <th class="text-end">(−) Egresos</th>
                                            <th class="text-end">(−) Rend. aprob.</th>
                                            <th class="text-end">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($saldosobres as $sob) :
                                            // Item 8 — regla confirmada: el saldo del programa solo es
                                            // firme con su POA Presupuestal APROBADO (las rendiciones
                                            // pendientes no descuentan). Sin POA aprobado: la fila se
                                            // muestra, las cifras no.
                                            $conPoaAprobado = ($poaAprobado ?? [])[(int) $sob->programa_id] ?? false;
                                            $asignado = (float) $sob->monto_asignado;
                                            $saldoSob = (float) $sob->saldo;
                                            $ratio    = $asignado > 0 ? ($saldoSob / $asignado) * 100 : ($saldoSob >= 0 ? 100 : 0);
                                            if ($saldoSob < 0 || $ratio < 15) {
                                                $tonoSob = 'danger';
                                            } elseif ($ratio < 50) {
                                                $tonoSob = 'warning';
                                            } else {
                                                $tonoSob = 'success';
                                            }
                                        ?>
                                            <tr>
                                                <td><span class="fw-semibold"><?php echo s($sob->programa_codigo); ?></span> · <?php echo s($sob->programa_nombre); ?></td>
                                                <td><span class="fw-semibold"><?php echo s($sob->fuente_codigo); ?></span> · <?php echo s($sob->fuente_nombre); ?></td>
                                                <?php if ($conPoaAprobado) : ?>
                                                    <td class="text-end"><?php echo soles($asignado); ?></td>
                                                    <td class="text-end text-success"><?php echo soles($sob->ingresos); ?></td>
                                                    <td class="text-end text-danger"><?php echo soles($sob->egresos); ?></td>
                                                    <td class="text-end text-danger"><?php echo soles($sob->rendiciones_aprobadas); ?></td>
                                                    <td class="text-end fw-bold text-<?php echo $tonoSob; ?>"><?php echo soles($saldoSob); ?></td>
                                                <?php else : ?>
                                                    <td colspan="5" class="text-center text-muted">
                                                        <span class="badge bg-warning text-dark">POA no aprobado</span>
                                                        <small>el saldo se muestra cuando el POA Presupuestal del año esté aprobado</small>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info text-center">
                            No hay sobres registrados. Asigna presupuesto a las fuentes de cada programa (vínculo de financiamiento).
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bloque 5 (item 8): Histórico anual por fuente — snapshots del cierre.
                 Se escribe SOLO al registrar el cierre (botón de arriba); comprometido
                 = Σ sobres al momento del corte; contable = sobrante al corte. -->
            <div class="row mt-5 mb-5">
                <div class="col-12">
                    <h3 class="mb-2 text-center">Histórico Anual por Fuente</h3>
                    <p class="text-muted text-center small mb-4">
                        Snapshot registrado en cada cierre de año. Contable = inicial + ingresos − rendiciones aprobadas − otros egresos.
                    </p>
                    <?php if (!empty($historicoAnual)) : ?>
                        <div class="card shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-variant-numeric: tabular-nums;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center">Año</th>
                                            <th>Fuente</th>
                                            <th class="text-end">Monto inicial</th>
                                            <th class="text-end">Comprometido (Σ sobres)</th>
                                            <th class="text-end">Contable (sobrante)</th>
                                            <th class="text-center">Registrado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historicoAnual as $h) : ?>
                                            <tr>
                                                <td class="text-center fw-semibold"><?php echo s($h['anio']); ?></td>
                                                <td><span class="fw-semibold"><?php echo s($h['fuente_codigo']); ?></span> · <?php echo s($h['fuente_nombre']); ?></td>
                                                <td class="text-end"><?php echo soles($h['monto_inicial']); ?></td>
                                                <td class="text-end"><?php echo soles($h['presupuesto_comprometido']); ?></td>
                                                <td class="text-end fw-bold <?php echo $h['presupuesto_contable'] < -0.001 ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo soles($h['presupuesto_contable']); ?>
                                                </td>
                                                <td class="text-center text-muted small"><?php echo s(date('d/m/Y H:i', strtotime($h['fecha']))); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info alert-persistente text-center">
                            Aún no hay cierres registrados. El botón "Registrar cierre del año" guarda el snapshot por fuente.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
