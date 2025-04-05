<fieldset>
  <div class="mb-3 w-25">
    <label for="codigo" class="form-label">Código</label>
    <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="codigo" name="fuente_financiamiento[codigo]" aria-describedby="codigoHelp" value="<?php echo s($fuente_financiamiento->codigo); ?>">
    <div id="codigoHelp" class="form-text">Ingrese un código válido</div>
  </div>

  <div class="mb-3">
    <label for="nombre" class="form-label">Nombre</label>
    <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="nombre" name="fuente_financiamiento[nombre]" aria-describedby="nombreHelp" value="<?php echo s($fuente_financiamiento->nombre); ?>">
    <div id="nombreHelp" class="form-text">Ingrese el nombre que recibirá esta fuente de financiamiento</div>
  </div>

  <div class="mb-3">
    <label for="descripcion" class="form-label">Descripción</label>
    <textarea style="text-transform: uppercase" class="form-control shadow-sm" id="descripcion" name="fuente_financiamiento[descripcion]" aria-describedby="descripcionHelp"><?php echo s($fuente_financiamiento->descripcion); ?></textarea>
    <div id="descripcionHelp" class="form-text">Ingrese la descripción que recibirá esta fuente de financiamiento</div>
  </div>

  <div class="mb-3 w-25">
    <label for="presupuesto" class="form-label">Presupuesto</label>
    <input type="text" style="text-transform: uppercase" class="form-control shadow-sm" id="presupuesto" name="fuente_financiamiento[presupuesto]" aria-describedby="presupuestoHelp" placeholder="S./" value="<?php echo s($fuente_financiamiento->presupuesto); ?>">
    <div id="presupuestoHelp" class="form-text">Ingrese el monto en soles</div>
  </div>
</fieldset>