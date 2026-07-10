<body>
    <main>
        <div class="container mt-5">
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
                            'presupuesto' => 0.0, 'ingresos' => 0.0, 'egresos' => 0.0, 'rendiciones' => 0.0, 'comprometido' => 0.0,
                        ];
                        $presupuesto = $desg['presupuesto'];
                        $comprometido = $desg['comprometido'];          // Σ sobres asignados a programas
                        $sinAsignar   = $presupuesto - $comprometido;   // remanente sin repartir
                        $saldoFuente = (float) $fuente->fuente_financiamiento_saldo;

                        // Disponibilidad: saldo respecto al presupuesto de la fuente.
                        // Sin presupuesto: 100% si el saldo es no negativo, 0% si es negativo.
                        $ratio  = $presupuesto > 0 ? ($saldoFuente / $presupuesto) * 100 : ($saldoFuente >= 0 ? 100 : 0);
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
                                        <?php echo round($ratio); ?>% disponible de <?php echo soles($presupuesto); ?>
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

                                    <!-- Asignación a programas: comprometido (Σ sobres) vs remanente (Fase 4) -->
                                    <div class="mt-2 pt-2 border-top text-start small">
                                        <div class="fw-bold text-muted mb-1">Asignación a programas</div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span>Comprometido (Σ sobres)</span>
                                            <span class="fw-semibold"><?php echo soles($comprometido); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span>Sin asignar (remanente)</span>
                                            <span class="fw-semibold <?php echo $sinAsignar < -0.001 ? 'text-danger' : ''; ?>"><?php echo soles($sinAsignar); ?></span>
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
                                                <td class="text-end"><?php echo soles($asignado); ?></td>
                                                <td class="text-end text-success"><?php echo soles($sob->ingresos); ?></td>
                                                <td class="text-end text-danger"><?php echo soles($sob->egresos); ?></td>
                                                <td class="text-end text-danger"><?php echo soles($sob->rendiciones_aprobadas); ?></td>
                                                <td class="text-end fw-bold text-<?php echo $tonoSob; ?>"><?php echo soles($saldoSob); ?></td>
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
        </div>
    </main>
