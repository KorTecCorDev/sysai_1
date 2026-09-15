<main>
    <div class="header-admin">
        <h1>Registrar Tipo de Cambio <?php echo s($moneda); ?></h1>
    </div>
    <div class="container">
        <?php foreach ($errores as $error) : ?>
            <div class="alert alert-danger"><?php echo s($error); ?></div>
        <?php endforeach; ?>

        <?php if (($sbs ?? null) === 'ok') : ?>
            <div class="alert alert-info alert-persistente">
                <i class="bi bi-cloud-download me-2"></i>
                Tasa SBS pre-cargada para el <?php echo s(date('d/m/Y', strtotime($tipocambio->fecha_vigencia))); ?>.
                <strong>Revísala y guarda</strong> — la consulta es informativa, no se registra sola.
            </div>
        <?php elseif (($sbs ?? null) === 'fail') : ?>
            <div class="alert alert-warning alert-persistente">
                <i class="bi bi-cloud-slash me-2"></i>
                No se pudo consultar la tasa SBS (servicio no disponible o no configurado en
                <code>SBS_API_URL</code>). Ingresa la tasa manualmente, como siempre.
            </div>
        <?php endif; ?>

        <!-- Consulta SBS (Fase 6 — informativa): pre-llena el formulario, no guarda. -->
        <form method="GET" action="/tcambio/sbs" class="d-flex align-items-end gap-2 mb-3">
            <input type="hidden" name="moneda" value="<?php echo s($moneda); ?>">
            <div>
                <label for="fecha-sbs" class="form-label small mb-1">Traer tasa SBS del día</label>
                <input type="date" class="form-control form-control-sm" id="fecha-sbs" name="fecha"
                       max="<?php echo date('Y-m-d'); ?>" value="<?php echo s($tipocambio->fecha_vigencia); ?>">
            </div>
            <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-cloud-download me-1"></i>Consultar SBS
            </button>
        </form>

        <form method="POST" action="/tcambio/crear?moneda=<?php echo s($moneda); ?>"><?php echo csrf_input(); ?>
            <?php if (($sbs ?? null) === 'ok') : ?>
                <input type="hidden" name="origen" value="SBS">
            <?php endif; ?>
            <?php include __DIR__ . '/formulario.php'; ?>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-save me-2"></i>Guardar
                </button>
                <a href="/tcambio/admin?moneda=<?php echo s($moneda); ?>" class="btn btn-outline-danger rounded-pill px-4">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</main>
