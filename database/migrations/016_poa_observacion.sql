-- 016 — POA Presupuestal: columna de observación (motivo de devolución).
-- Al Observar (devolver) el documento, el Contador registra el motivo para que el
-- Coordinador sepa qué subsanar. Mismo patrón que poa_indicadores (migración 015).
-- Se limpia al reenviar/aprobar (lógica de app). El framework normaliza null->''.
ALTER TABLE `poa`
    ADD COLUMN `observacion` VARCHAR(500) NULL DEFAULT NULL AFTER `estado`;
