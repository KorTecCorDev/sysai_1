-- 020 — Sub-presupuestos por programa (asignación / reserva de fuentes).
-- Decisión 2026-07-09: REVIERTE dos reglas previas:
--   (a) "El presupuesto de la fuente no se reparte por programa" → AHORA sí se reparte
--       en "sobres" exclusivos por programa (detalle_financiamiento.monto_asignado).
--   (b) "Σ rendiciones contra un rubro no puede exceder rubro.monto" → el tope de gasto
--       pasa al SOBRE (programa, fuente); el rubro queda como clasificación/planeación.
--       (La relajación del tope por rubro se aplica en la capa de validación — Fase 2.)
--
-- Modelo resultante:
--   Σ monto_asignado por fuente  ≤  fuente.presupuesto   (se permite remanente sin asignar).
--   Gasto (rendiciones aprobadas + otros egresos) se descuenta del sobre (programa, fuente).
--   Ingreso OIE: por defecto al total de la fuente (remanente, programa_id = NULL);
--                opcionalmente a un programa concreto (su sobre, programa_id informado).
-- Ver enmienda en CLAUDE.md › [REGLAS DE NEGOCIO — CONFIRMADAS].

-- 1) El "sobre": monto del presupuesto de la fuente reservado para el programa.
ALTER TABLE `detalle_financiamiento`
    ADD COLUMN `monto_asignado` decimal(14,2) NOT NULL DEFAULT 0.00 AFTER `fuente_financiamiento_id`;

-- 2) Un único sobre por par (programa, fuente): evita vínculos duplicados.
ALTER TABLE `detalle_financiamiento`
    ADD UNIQUE KEY `uq_programa_fuente` (`programa_id`, `fuente_financiamiento_id`);

-- 3) OIE: el ingreso puede ir al total de la fuente (sin programa) o a un programa
--    concreto → programa_id pasa a NULL-able. El egreso siempre lleva programa
--    (se exige en la validación de la app, no en el esquema).
ALTER TABLE `otros_ingresos_egresos`
    MODIFY COLUMN `programa_id` int(11) NULL;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('020');
