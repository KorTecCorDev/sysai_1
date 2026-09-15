<fieldset>
    <div class="mb-3 w-25">
        <label for="fecha_vigencia" class="form-label">Fecha de vigencia</label>
        <input type="date" class="form-control" id="fecha_vigencia" name="fecha_vigencia"
               max="<?php echo date('Y-m-d'); ?>" aria-describedby="fechaVigenciaHelp"
               value="<?php echo s($tipocambio->fecha_vigencia); ?>">
        <div id="fechaVigenciaHelp" class="form-text">
            La fecha a la que aplica la tasa (no la de registro). Puedes cargar días atrasados.
        </div>
    </div>
    <div class="mb-3 w-25">
        <label for="compra" class="form-label">TC Compra</label>
        <input type="number" step="0.001" min="0" class="form-control" id="compra" name="compra"
               aria-describedby="compraHelp" placeholder="3.748" value="<?php echo s($tipocambio->compra); ?>">
        <div id="compraHelp" class="form-text">Se usa para convertir ingresos (donativos). Admite 3+ decimales.</div>
    </div>
    <div class="mb-3 w-25">
        <label for="venta" class="form-label">TC Venta</label>
        <input type="number" step="0.001" min="0" class="form-control" id="venta" name="venta"
               aria-describedby="ventaHelp" placeholder="3.751" value="<?php echo s($tipocambio->venta); ?>">
        <div id="ventaHelp" class="form-text">Se usa para convertir gastos (rendiciones, rubros).</div>
    </div>
</fieldset>
