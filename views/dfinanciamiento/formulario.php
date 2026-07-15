<fieldset>
    <!-- Programas -->
    <form method="POST"><?php echo csrf_input(); ?>
        <div class="mb-3">
            <label class="form-label" for="programs">Programa</label>
            <?php
            // Programa actualmente elegido: desde la URL, o el del vínculo ya cargado.
            // Se compara como STRING: el id llega como string por la URL, pero puede
            // venir como int desde consultas preparadas (mysqlnd); el === estricto
            // fallaba y el select revertía a "--Seleccione--".
            $selId = $_GET['programa_id'] ?? ($resuls[0]->programa_id ?? null);
            $selId = ($selId === null || $selId === '') ? null : (string) $selId;
            ?>
            <select id="programs" name="detalle_financiamiento[programa_id]" class="form-select">
                <option value="" disabled <?php echo $selId === null ? 'selected' : ''; ?>>--Seleccione--</option>
                <?php foreach ($programas as $programa) : ?>
                    <option value="<?php echo s($programa->id); ?>" <?php echo ((string) $programa->id === $selId) ? 'selected' : ''; ?>>
                        <?php echo s($programa->nombre); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Seleccione el programa que desea vincular</div>
        </div>
    </form>
</fieldset>