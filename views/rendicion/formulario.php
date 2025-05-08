<fieldset class="border p-3">
    <legend>Datos del comprobante</legend>
    <div class="col-12 w-auto">
        <label class="form-label" for="tipo_comprobante">Tipo de comprobante:</label>
        <select class="form-select shadow-sm w-auto" id="tipo_comprobante_combo" name="tipo_comprobante_id">
            <option value="0" disabled selected>-- Seleccione --</option>
            <?php foreach ($tipocomprobantes as $tipocomprobante): ?>
                <option <?php echo $rendicion->tipo_comprobante_id === $tipocomprobante->id ? 'selected' : ''; ?> value="<?php echo $tipocomprobante->id; ?>">
                    <?php echo $tipocomprobante->descripcion; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <!-- Seleccionar fuente de financiamiento para el comprobante -->

    <div class="col-12 w-auto">
        <label class="form-label" for="tipo_comprobante">Fuente de financiamiento:</label>
        <select class="form-select shadow-sm w-auto" id="fuente_financiamiento_combo" name="ff_id">
            <option value="0" disabled selected>-- Seleccione --</option>
            <?php foreach ($fuentesfinanciamiento as $fuentefinanciamiento): ?>
                <option <?php echo $rendicion->ff_id === $fuentefinanciamiento->fuente_id ? 'selected' : ''; ?> value="<?php echo $fuentefinanciamiento->fuente_id; ?>">
                    <?php echo $fuentefinanciamiento->fuente_nombre . ' - ' . $fuentefinanciamiento->fuente_presupuesto; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3 w-25">
        <label for="fecha" class="form-label">Fecha:</label>
        <input type="date" class="form-control" id="fecha_original" name="fecha_original" placeholder="Fecha del comprobante" value="<?php echo s($rendicion->fecha_original); ?>">
    </div>

    <div class="mb-3 w-25">
        <label for="ruc" class="form-label">RUC:</label>
        <input type="text" class="form-control" id="ruc" name="ruc" placeholder="RUC" value="<?php echo s($rendicion->ruc); ?>">
    </div>

    <div class="mb-3 w-auto">
        <label for="razon_social" class="form-label">Razón social:</label>
        <input type="text" class="form-control" id="razon_social" name="razon_social" placeholder="Razón Social" value="<?php echo s($rendicion->razon_social); ?>">
    </div>

    <div class="mb-3 w-25">
        <label for="codigo" class="form-label">Código:</label>
        <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Código" value="<?php echo s($rendicion->codigo); ?>">
    </div>

    <div class="mb-3 w-25">
        <label for="serie" class="form-label">Serie:</label>
        <input type="text" class="form-control" id="serie" name="serie" placeholder="Serie" value="<?php echo s($rendicion->serie); ?>">
    </div>

    <div class="mb-3 w-25">
        <label for="numero" class="form-label">Número:</label>
        <input type="text" class="form-control" id="numero" name="numero" placeholder="Número de Comprobante" value="<?php echo s($rendicion->numero); ?>">
    </div>

    <div class="mb-3 w-auto">
        <label for="detalle" class="form-label">Detalle:</label>
        <input type="text" class="form-control" id="detalle" name="detalle" placeholder="Descripción del comprobante" value="<?php echo s($rendicion->detalle); ?>">
    </div>

    <div class="mb-3 w-auto">
        <label for="descripcion" class="form-label">Comentario:</label>
        <input type="text" class="form-control" id="descripcion" name="descripcion" placeholder="Comentario sobre la rendición" value="<?php echo s($rendicion->descripcion); ?>">
    </div>

    <div class="mb-3 w-25">
        <label for="monto" class="form-label">Monto:</label>
        <input type="text" class="form-control" id="monto" name="monto" placeholder="Monto" value="<?php echo s($rendicion->monto); ?>">
    </div>

</fieldset>