<fieldset>
    <div class="mb-3">
    <label for="codigo" class="form-label">Código</label>
    <input type="text" class="form-control" id="codigo" aria-describedby="codigoHelp" name="fuente_financiamiento[codigo]" value="<?php echo s($fuente_financiamiento->codigo); ?>">
    <div id="codigoHelp" class="form-text">Ingrese el código que recibirá esta fuente de financiamiento</div>
    </div>

    <div class="mb-3">
    <label for="nombre" class="form-label">Nombre</label>
    <input type="text" class="form-control" id="nombre" aria-describedby="codigoHelp" name="fuente_financiamiento[nombre]" value="<?php echo s($fuente_financiamiento->nombre); ?>">
    <div id="nombreHelp" class="form-text">Ingrese el nombre que recibirá esta fuente de financiamiento</div>
    </div>

    <div class="mb-3">
    <label for="descripcion" class="form-label">Descripción</label>
    <textarea type="text" class="form-control" id="descripcion" aria-describedby="descripcionHelp" name="fuente_financiamiento[descripcion]"><?php echo s($fuente_financiamiento->descripcion); ?></textarea>
    <div id="descripcionHelp" class="form-text">Ingrese la descripción que recibirá esta fuente de financiamiento</div>
    </div>

    <div class="mb-3">
    <label for="presupuesto" class="form-label">Presupuesto</label>
    <input type="text" class="form-control" id="presupuesto" aria-describedby="presupuestoHelp" name="fuente_financiamiento[presupuesto]" value="<?php echo s($fuente_financiamiento->presupuesto); ?>">
    <div id="presupuestoHelp" class="form-text">Ingrese el presupuesto que tendrá esta fuente de financiamiento</div>
    </div>
</fieldset>