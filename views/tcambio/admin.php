<main>
    <div class="header-admin">
        <h1>Tipos de Cambio <?php echo s($moneda); ?></h1>
        <h4>Sol peruano (S/) por 1 <?php echo s($moneda); ?> — el vigente a una fecha es el de fecha de vigencia máxima ≤ esa fecha</h4>
        <?php
        if ($resultado) {
            $mensaje = mostrarNotificacion(intval($resultado));
            if ($mensaje) { ?>
                <p class="alert alert-info"><?php echo s($mensaje); ?></p>
        <?php
            }
        }
        ?>
        <div class="row">
            <div class="col">
                <!-- Selector de moneda -->
                <div class="btn-group" role="group" aria-label="Moneda">
                    <a href="/tcambio/admin?moneda=USD" class="btn <?php echo $moneda === 'USD' ? 'btn-primary' : 'btn-outline-primary'; ?>">USD (Dólar)</a>
                    <a href="/tcambio/admin?moneda=EUR" class="btn <?php echo $moneda === 'EUR' ? 'btn-primary' : 'btn-outline-primary'; ?>">EUR (Euro)</a>
                </div>
            </div>
            <div class="d-flex justify-content-end mb-4">
                <a href="/tcambio/crear?moneda=<?php echo s($moneda); ?>" class="btn btn-primary rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i>Agregar</a>
            </div>
        </div>
    </div>
    <div class="container text-center p-0">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col" class="rounded-start">Fecha de vigencia</th>
                    <th scope="col">Compra</th>
                    <th scope="col">Venta</th>
                    <th scope="col">Origen</th>
                    <th scope="col">Registrado</th>
                    <th scope="col" class="text-center rounded-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tiposcambio)) : ?>
                    <tr><td colspan="6" class="text-muted py-3">
                        No hay tipos de cambio <?php echo s($moneda); ?> registrados. Sin TC, los reportes
                        muestran solo soles (no revientan, pero los importes en <?php echo s($moneda); ?> quedan incompletos).
                    </td></tr>
                <?php endif; ?>
                <?php foreach ($tiposcambio as $tc) : ?>
                    <tr>
                        <td class="fw-semibold"> <?php echo s(date('d/m/Y', strtotime($tc->fecha_vigencia))); ?> </td>
                        <td> <?php echo s(rtrim(rtrim(number_format((float) $tc->compra, 6, '.', ''), '0'), '.')); ?> </td>
                        <td> <?php echo s(rtrim(rtrim(number_format((float) $tc->venta, 6, '.', ''), '0'), '.')); ?> </td>
                        <td><span class="badge <?php echo $tc->origen === 'SBS' ? 'bg-info text-dark' : 'bg-secondary'; ?>"><?php echo s($tc->origen); ?></span></td>
                        <td class="text-muted small"> <?php echo s($tc->fecha); ?> </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="/tcambio/actualizar?id=<?php echo s($tc->id); ?>&moneda=<?php echo s($moneda); ?>"
                                    class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                    title="Editar registro">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <form method="POST" action="/tcambio/eliminar" class="d-inline"><?php echo csrf_input(); ?>
                                    <input type="hidden" name="id" value="<?php echo s($tc->id); ?>">
                                    <button type="submit"
                                        class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                        title="Eliminar registro"
                                        data-confirm="¿Eliminar este tipo de cambio? Las conversiones ya congeladas en rendiciones/OIE no cambian.">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
