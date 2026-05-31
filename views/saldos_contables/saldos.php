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
                    <div class="card text-white bg-success p-3" data-aos="fade-up">
                        <div class="card-body text-center">
                            <i class="bi bi-arrow-up-circle icon"></i>
                            <h4 class="card-title">Ingresos Totales</h4>
                            <h2 id="ingresosTotales">
                                <?php foreach ($totalingresos as $tingreso) : ?>
                                    <?php echo s($tingreso->total_ingresos); ?>
                                <?php endforeach ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <!-- Egresos Totales -->
                <div class="col-md-4">
                    <div class="card text-white bg-danger p-3" data-aos="fade-up" data-aos-delay="200">
                        <div class="card-body text-center">
                            <i class="bi bi-arrow-down-circle icon"></i>
                            <h4 class="card-title">Egresos Totales</h4>
                            <h2 id="egresosTotales">
                                <?php foreach ($totalegresos as $tegreso) : ?>
                                    <?php echo s($tegreso->total_egresos); ?>
                                <?php endforeach ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <!-- Saldo Total -->
                <div class="col-md-4">
                    <div class="card text-white bg-primary p-3" data-aos="fade-up" data-aos-delay="400">
                        <div class="card-body text-center">
                            <i class="bi bi-cash-stack icon"></i>
                            <h4 class="card-title">Saldo Total</h4>
                            <h2 id="saldoTotal">
                                <?php foreach ($totalsaldocontable as $tsaldo) : ?>
                                    <?php echo s($tsaldo->saldo_contable); ?>
                                <?php endforeach ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bloque 2: Saldos de fuentes de financiamiento -->
            <div class="row">
                <div class="col-12">
                    <h3 class="mb-4 text-center">Saldos por Fuente de Financiamiento</h3>
                </div>
                <?php if (!empty($totalsaldofuentes)) : ?>
                    <?php foreach ($totalsaldofuentes as $fuente) : ?>
                        <div class="col-md-4 mb-4">
                            <div class="card border-info h-100 shadow fuente-saldo-card">
                                <div class="card-header bg-info text-white text-center fuente-saldo-header">
                                    <strong><?php echo s($fuente->fuente_financiamiento_codigo); ?> - <?php echo s($fuente->fuente_financiamiento_nombre); ?></strong>
                                </div>
                                <div class="card-body text-center">
                                    <span class="fw-bold">Saldo:</span>
                                    <h4 class="text-info mt-2">S/ <?php echo number_format($fuente->fuente_financiamiento_saldo, 2); ?></h4>
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
        </div>
    </main>