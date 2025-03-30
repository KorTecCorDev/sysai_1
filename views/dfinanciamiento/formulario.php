<fieldset>
    <!-- Programas -->
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Programa</label>
            <select id="programs" name="detalle_financiamiento[programa_id]" class="form-select">
                <option value="0" selected>--Seleccione--</option>
                <?php $prgma_id = $_GET['programa_id'] ?? null; ?>
                <?php foreach ($programas as $programa) { ?>
                    <?php if ($resuls) { ?>
                        <option <?php echo $programa->id === $resuls[0]->programa_id ? 'selected' : ''; ?> value="<?php echo $programa->id; ?>"><?php echo $programa->nombre; ?></option>
                    <?php } else { ?>
                        <?php if ($prgma_id !== null) { ?>
                            <?php if ($programa->id === $prgma_id) { ?>
                                <option selected value="<?php echo $programa->id; ?>"><?php echo $programa->nombre; ?></option>
                            <?php } else { ?>
                                <option value="<?php echo $programa->id; ?>"><?php echo $programa->nombre; ?></option>
                            <?php } ?>
                        <?php } else { ?>
                            <option value="<?php echo $programa->id; ?>"><?php echo $programa->nombre; ?></option>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>
            </select>
            <div class="form-text">Seleccione el programa que desea vincular</div>
        </div>
    </form>
</fieldset>