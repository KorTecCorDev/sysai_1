-- 029 · Plan de montos (Fase 3) — Congelar el TC en las transacciones.
-- Al registrar una rendición u OIE se resuelve el TC vigente a su FECHA DE OPERACIÓN
-- (rendicion.fecha_original / oie_comprobante.fecha_original) y se COPIA EL VALOR:
--   * rendición (gasto) -> venta; OIE ingreso -> compra; OIE egreso -> venta (§2.5).
--   * NULL a propósito (no 0): significa "sin conversión disponible" — permite mostrar
--     "—", contar los faltantes, y ROUND(monto / NULLIF(tc,0), 2) nunca da fatal.
--   * Se copia el NÚMERO, no un FK (§2.6): editar/borrar el TC después no reescribe
--     la contabilidad. tipo_cambio_*_id queda solo como rastro de procedencia.

ALTER TABLE `rendicion`
  ADD COLUMN `tc_usd` DECIMAL(12,6) NULL,
  ADD COLUMN `tc_eur` DECIMAL(12,6) NULL,
  ADD COLUMN `tipo_cambio_usd_id` INT NULL,
  ADD COLUMN `tipo_cambio_eur_id` INT NULL;

ALTER TABLE `oie_comprobante`
  ADD COLUMN `tc_usd` DECIMAL(12,6) NULL,
  ADD COLUMN `tc_eur` DECIMAL(12,6) NULL,
  ADD COLUMN `tipo_cambio_usd_id` INT NULL,
  ADD COLUMN `tipo_cambio_eur_id` INT NULL;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('029');
