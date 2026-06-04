<fieldset>
    <legend>Indicador de la Actividad</legend>
    <div class="mb-3 w-25">
        <label for="indicador_medido" class="form-label">Meta (indicador medido)</label>
        <input type="number" class="form-control" id="indicador_medido" name="indicador_medido"
            value="<?php echo s($detalle->indicador_medido); ?>">
        <div class="form-text">Valor numérico de la meta del indicador</div>
    </div>
    <div class="mb-3 w-auto">
        <label for="medio_verificacion" class="form-label">Medio de verificación</label>
        <input type="text" class="form-control" id="medio_verificacion" name="medio_verificacion"
            value="<?php echo s($detalle->medio_verificacion); ?>">
        <div class="form-text">Cómo se comprobará el cumplimiento del indicador</div>
    </div>
    <div class="mb-3 w-auto">
        <label for="supuesto" class="form-label">Supuesto</label>
        <input type="text" class="form-control" id="supuesto" name="supuesto"
            value="<?php echo s($detalle->supuesto); ?>">
        <div class="form-text">Condición externa que se asume para lograr la meta</div>
    </div>
    <div class="mb-3 w-auto">
        <label for="responsable" class="form-label">Responsable</label>
        <input type="text" class="form-control" id="responsable" name="responsable"
            value="<?php echo s($detalle->responsable); ?>">
        <div class="form-text">Persona o área responsable del indicador</div>
    </div>
</fieldset>
