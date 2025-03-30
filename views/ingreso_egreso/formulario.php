<fieldset>
    <legend>Datos Generales</legend>
    <!-- Tipo de OIE - Ingreso/Egreso -->
    <div class="mb-3">
        <label for="combo_oie_tipo_id" class="form-label">Tipo</label>
        <select class="form-select" id="combo_oie_tipo_id" name="oie[oie_tipo_id]" aria-describedby="tipoOieHelp">
            <option value="1">Ingreso</option>
            <option value="2">Egreso</option>
        </select>
        <div id="tipoOieHelp" class="form-text">Seleccione si es un Ingreso o un Egreso</div>
    </div>
    <!-- Tipo de OIE - Ingreso/Egreso -->


    <!-- Combo_Programa - poa -->
    <div class="mb-3">
        <label for="combo_programa_poa" class="form-label">Programa-POA</label>
        <select class="form-select" id="combo_programa_poa" name="oie[poa_id]" aria-describedby="PoaHelp">
            <option value="0" selected disabled>--Seleccione--</option>
            <?php foreach ($poas as $poa) { ?>
                <option value="<?php echo s($poa->poa_id); ?>" <?php echo $oie->poa_id == $poa->poa_id ? 'selected' : '' ;?>><?php echo s("{$poa->programa_codigo} - {$poa->programa_nombre}"); ?></option>
            <?php } ?>
        </select>
        <div id="ProgramaPoaHelp" class="form-text">Seleccione el Programa/POA</div>
    </div>

    <!-- Combo_Programa - poa -->


    <!-- Codigo -->
    <div class="mb-3">
        <label for="codigo" class="form-label">Código</label>
        <input type="text" class="form-control" id="codigo" aria-describedby="codigoHelp" name="oie[codigo]" value="<?php echo s($oie->codigo); ?>">
        <div id="codigoHelp" class="form-text">Ingrese el código</div>
    </div>
    <!-- Codigo -->

    <!-- Descripcion -->
    <div class="mb-3">
        <label for="codigo" class="form-label">Descripción</label>
        <input type="text" class="form-control" id="codigo" aria-describedby="codigoHelp" name="oie[descripcion]" value="<?php echo s($oie->descripcion); ?>">
        <div id="codigoHelp" class="form-text">Ingrese la descripción</div>
    </div>
    <!-- Descripcion -->

</fieldset>

<fieldset>
    <legend>Comprobante</legend>
    <!-- Comprobante -->

    <!-- Fecha -->
    <div class="row mb-3">
        <div class="col-12 w-25">
            <label for="fecha" class="form-label">Fecha:</label>
            <input type="date" class="form-control" id="oie_comprobante[fecha_original]" name="oie_comprobante[fecha_original]" placeholder="Fecha del comprobante" value="<?php echo s($oie_comprobante->fecha_original); ?>">
        </div>
    </div>
    <!-- Fecha -->

    <!-- Tipo de COMPROBANTE OIE - Boleta/Factura/Recibo por Honorarios/DDJJ -->
    <div class="mb-3">
        <label for="combo_oie_tipo_id" class="form-label">Tipo</label>
        <select class="form-select" id="combo_oie_tipo_comprobante_id" name="oie_comprobante[oie_tipo_comprobante_id]" aria-describedby="tipoOieHelp">
            <option value="0" selected disabled>--Seleccione--</option>
            <?php foreach ($tipocomprobantes as $tipocomprobante) { ?>
                <option value="<?php echo s($tipocomprobante->id); ?>" <?php echo $oie_comprobante->oie_tipo_comprobante_id == $tipocomprobante->id ? 'selected' : ''; ?>><?php echo s("{$tipocomprobante->codigo} - {$tipocomprobante->nombre}"); ?></option>
            <?php } ?>
        </select>
        <div id="tipoOieHelp" class="form-text">Seleccione el tipo de Comprobante</div>
    </div>
    <!-- Tipo de COMPROBANTE OIE - Boleta/Factura/Recibo por Honorarios/DDJJ -->

    <!-- RUC -->
    <div class="row mb-3">
        <div class="col-12">
            <label for="ruc" class="form-label">RUC:</label>
            <input type="text" class="form-control" id="ruc" name="oie_comprobante[ruc]" placeholder="RUC" value="<?php echo s($oie_comprobante->ruc); ?>">
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
    <div class="row mb-3">
        <div class="col-12">
            <label for="serie" class="form-label">Serie:</label>
            <input type="text" class="form-control" id="serie" name="oie_comprobante[serie]" placeholder="Serie" value="<?php echo s($oie_comprobante->serie); ?>">
        </div>
    </div>
    <!-- Serie -->

    <!-- Número -->
    <div class="row mb-3">
        <div class="col-12 w-25">
            <label for="numero" class="form-label">Número:</label>
            <input type="text" class="form-control" id="numero" name="oie_comprobante[numero]" placeholder="Número de Comprobante" value="<?php echo s($oie_comprobante->numero); ?>">
        </div>
    </div>
    <!-- Número -->



    <!-- Descripcion -->
    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea type="text" class="form-control" id="descripcion" aria-describedby="descripcionHelp" name="oie_comprobante[descripcion]"><?php echo s($oie->descripcion); ?></textarea>
        <div id="descripcionHelp" class="form-text">Ingrese la descripción</div>
    </div>
    <!-- Descripcion -->



    <!-- Monto -->
    <div class="mb-3">
        <label for="monto" class="form-label">Monto</label>
        <input type="money" class="form-control" id="monto" aria-describedby="montoHelp" name="oie_comprobante[monto]" value="<?php echo s($oie_comprobante->monto); ?>">
        <div id="montoHelp" class="form-text">Ingrese el monto</div>
    </div>
    <!-- Monto -->
    <!-- Comprobante -->
</fieldset>