<fieldset>
  <?php // El código lo asigna el sistema al crear; solo se muestra al EDITAR
        // (en creación era un campo gris vacío: puro ruido). ?>
  <?php if (!empty($fuente_financiamiento->codigo)) : ?>
  <div class="mb-3 w-25">
    <label for="codigo" class="form-label">Código</label>
    <input type="text" class="form-control shadow-sm bg-body-secondary" id="codigo" aria-describedby="codigoHelp"
           value="<?php echo s($fuente_financiamiento->codigo); ?>" placeholder="Se asignará automáticamente" readonly>
    <div id="codigoHelp" class="form-text">Se genera automáticamente (p. ej. FF001).</div>
  </div>
  <?php endif; ?>

  <div class="mb-3">
    <label for="nombre" class="form-label">Nombre</label>
    <input type="text" class="form-control shadow-sm" id="nombre" name="fuente_financiamiento[nombre]" aria-describedby="nombreHelp" value="<?php echo s($fuente_financiamiento->nombre); ?>">
    <div id="nombreHelp" class="form-text">Ingrese el nombre que recibirá esta fuente de financiamiento</div>
  </div>

  <div class="mb-3">
    <label for="descripcion" class="form-label">Descripción</label>
    <textarea class="form-control shadow-sm" id="descripcion" name="fuente_financiamiento[descripcion]" aria-describedby="descripcionHelp"><?php echo s($fuente_financiamiento->descripcion); ?></textarea>
    <div id="descripcionHelp" class="form-text">Ingrese la descripción que recibirá esta fuente de financiamiento</div>
  </div>

  <div class="mb-3 w-25">
    <label for="presupuesto" class="form-label">Presupuesto</label>
    <input type="number" step="0.01" min="0" max="<?php echo s(MONTO_MAXIMO); ?>" class="form-control shadow-sm" id="presupuesto" name="fuente_financiamiento[presupuesto]" aria-describedby="presupuestoHelp" placeholder="0.00" value="<?php echo s($fuente_financiamiento->presupuesto); ?>">
    <div id="presupuestoHelp" class="form-text">Ingrese el monto en soles, sin separadores de miles (p. ej. 3500000.00)</div>
  </div>
</fieldset>