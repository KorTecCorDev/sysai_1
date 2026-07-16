<fieldset>
    <legend>Datos Generales</legend>
    <!-- Tipo de OIE - Ingreso/Egreso -->
    <div class="mb-3 w-25">
        <label for="combo_oie_tipo_id" class="form-label">Tipo</label>
        <select class="form-select" id="combo_oie_tipo_id" name="oie[oie_tipo_id]" aria-describedby="tipoOieHelp">
            <option value="" selected disabled>--Seleccione--</option>
            <option value="1" <?php echo $oie->oie_tipo_id == 1 ? 'selected' : ''; ?>>Ingreso</option>
            <option value="2" <?php echo $oie->oie_tipo_id == 2 ? 'selected' : ''; ?>>Egreso</option>
        </select>
        <div id="tipoOieHelp" class="form-text">Seleccione si es un Ingreso o un Egreso</div>
    </div>
    <!-- Tipo de OIE - Ingreso/Egreso -->

    <!-- Fuente de financiamiento -->
    <div class="mb-3 w-50">
        <label for="combo_ff_id" class="form-label">Fuente de financiamiento</label>
        <select class="form-select" id="combo_ff_id" name="oie[ff_id]" aria-describedby="ffHelp">
            <option value="" selected disabled>--Seleccione--</option>
            <?php foreach ($fuentes as $fuente) { ?>
                <option value="<?php echo s($fuente->id); ?>" <?php echo $oie->ff_id == $fuente->id ? 'selected' : ''; ?>><?php echo s("{$fuente->codigo} - {$fuente->nombre}"); ?></option>
            <?php } ?>
        </select>
        <div id="ffHelp" class="form-text">Fuente cuyo presupuesto contable se afecta</div>
    </div>
    <!-- Fuente de financiamiento -->

    <!-- Programa destino (sobre) -->
    <div class="mb-3 w-50">
        <label for="combo_programa_id" class="form-label">Programa (destino)</label>
        <select class="form-select" id="combo_programa_id" name="oie[programa_id]" aria-describedby="programaHelp">
            <option value="" <?php echo !$oie->programa_id ? 'selected' : ''; ?>>— Al total de la fuente (sin programa) —</option>
            <?php foreach ($programas as $programa) { ?>
                <option value="<?php echo s($programa->id); ?>" <?php echo $oie->programa_id == $programa->id ? 'selected' : ''; ?>><?php echo s("{$programa->codigo} - {$programa->nombre}"); ?></option>
            <?php } ?>
        </select>
        <div id="programaHelp" class="form-text">Los ingresos pueden ir al total de la fuente o al sobre de un programa; los egresos siempre descuentan del sobre de un programa vinculado a la fuente</div>
    </div>
    <!-- Programa destino (sobre) -->

    <!-- Codigo -->
    <div class="mb-3 w-25">
        <label for="codigo" class="form-label">Código</label>
        <input style="text-transform: uppercase" type="text" class="form-control" id="codigo" aria-describedby="codigoHelp" name="oie[codigo]" value="<?php echo s($oie->codigo); ?>">
        <div id="codigoHelp" class="form-text">Ingrese el código</div>
    </div>
    <!-- Codigo -->

    <!-- Descripcion -->
    <div class="mb-3">
        <label for="oie_descripcion" class="form-label">Descripción</label>
        <input style="text-transform: uppercase" type="text" class="form-control" id="oie_descripcion" aria-describedby="oieDescripcionHelp" name="oie[descripcion]" value="<?php echo s($oie->descripcion); ?>">
        <div id="oieDescripcionHelp" class="form-text">Ingrese la descripción</div>
    </div>
    <!-- Descripcion -->

</fieldset>

<fieldset>
    <legend>Comprobante</legend>
    <!-- Comprobante -->

    <!-- Fecha -->
    <div class="mb-3 w-auto">
        <div class="col-12 w-25">
            <label for="fecha_original" class="form-label">Fecha:</label>
            <input style="text-transform: uppercase" type="date" class="form-control" id="fecha_original" name="oie_comprobante[fecha_original]" placeholder="Fecha del comprobante" value="<?php echo s($oie_comprobante->fecha_original); ?>">
        </div>
    </div>
    <!-- Fecha -->

    <!-- Tipo de COMPROBANTE OIE - Boleta/Factura/Recibo por Honorarios/DDJJ -->
    <div class="mb-3 w-50">
        <label for="combo_oie_tipo_comprobante_id" class="form-label">Tipo</label>
        <select class="form-select" id="combo_oie_tipo_comprobante_id" name="oie_comprobante[oie_tipo_comprobante_id]" aria-describedby="tipoComprobanteHelp">
            <option value="" selected disabled>--Seleccione--</option>
            <?php foreach ($tipocomprobantes as $tipocomprobante) { ?>
                <option value="<?php echo s($tipocomprobante->id); ?>" <?php echo $oie_comprobante->oie_tipo_comprobante_id == $tipocomprobante->id ? 'selected' : ''; ?>><?php echo s("{$tipocomprobante->codigo} - {$tipocomprobante->nombre}"); ?></option>
            <?php } ?>
        </select>
        <div id="tipoComprobanteHelp" class="form-text">Seleccione el tipo de Comprobante</div>
    </div>
    <!-- Tipo de COMPROBANTE OIE - Boleta/Factura/Recibo por Honorarios/DDJJ -->

    <!-- RUC -->
    <div class="mb-3 w-25">
        <div class="col-12">
            <label for="ruc" class="form-label">RUC:</label>
            <input style="text-transform: uppercase" type="text" class="form-control" id="ruc" name="oie_comprobante[ruc]" placeholder="RUC" value="<?php echo s($oie_comprobante->ruc); ?>">
        </div>
    </div>
    <!-- RUC -->

    <!-- Razón social -->
    <div class="row mb-3">
        <div class="col-12">
            <label for="razon_social" class="form-label">Razón social:</label>
            <input type="text" class="form-control" id="razon_social" name="oie_comprobante[razon_social]" placeholder="Razón Social" value="<?php echo s($oie_comprobante->razon_social); ?>">
        </div>
    </div>
    <!-- Razón social -->

    <!-- Serie -->
    <div class="mb-3 w-25">
        <div class="col-12">
            <label for="serie" class="form-label">Serie:</label>
            <input type="text" class="form-control" id="serie" name="oie_comprobante[serie]" placeholder="Serie" value="<?php echo s($oie_comprobante->serie); ?>">
        </div>
    </div>
    <!-- Serie -->

    <!-- Número -->
    <div class="mb-3 w-50">
        <div class="col-12 w-25">
            <label for="numero" class="form-label">Número:</label>
            <input type="text" class="form-control" id="numero" name="oie_comprobante[numero]" placeholder="Número de Comprobante" value="<?php echo s($oie_comprobante->numero); ?>">
        </div>
    </div>
    <!-- Número -->

    <!-- Descripcion -->
    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea type="text" class="form-control" id="descripcion" aria-describedby="descripcionHelp" name="oie_comprobante[descripcion]"><?php echo s($oie_comprobante->descripcion); ?></textarea>
        <div id="descripcionHelp" class="form-text">Ingrese la descripción</div>
    </div>
    <!-- Descripcion -->

    <!-- Monto -->
    <div class="mb-3 w-25">
        <label for="monto" class="form-label">Monto</label>
        <input type="number" step="0.01" min="0" max="<?php echo s(MONTO_MAXIMO); ?>" class="form-control" id="monto" aria-describedby="montoHelp" name="oie_comprobante[monto]" value="<?php echo s($oie_comprobante->monto); ?>">
        <div id="montoHelp" class="form-text">Ingrese el monto</div>
    </div>
    <!-- Monto -->
    <!-- Comprobante -->
</fieldset>
