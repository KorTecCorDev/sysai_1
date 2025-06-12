<body>
    <main>
        <div class="container mt-5">
            <div class="row">
                <!-- Ingresos Totales -->
                <div class="col-md-4">
                    <div class="card text-white bg-success p-3" data-aos="fade-up">
                        <div class="card-body text-center">
                            <i class="bi bi-arrow-up-circle icon"></i>
                            <h4 class="card-title">Ingresos Totales</h4>
                            <h2 id="ingresosTotales"><?php foreach ($totalingresos as $tingreso) : ?>
                                    <?php echo $tingreso->total_ingresos; ?>
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
                            <h2 id="egresosTotales"><?php foreach ($totalegresos as $tegreso) : ?>
                                    <?php echo $tegreso->total_egresos; ?>
                                <?php endforeach ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Saldo Total -->
                <div class="col-md-4">
                    <div class="card text-white bg-primary p-3" data-aos="fade-up" data-aos-delay="400">
                        <div class="card-body text-center">
                            <i class="bi bi-cash-stack icon"></i>
                            <h4 class="card-title">Saldo Total</h4>
                            <h2 id="saldoTotal"><?php foreach ($totalsaldocontable as $tsaldo) : ?>
                                    <?php echo $tsaldo->saldo_contable; ?>
                                <?php endforeach ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>