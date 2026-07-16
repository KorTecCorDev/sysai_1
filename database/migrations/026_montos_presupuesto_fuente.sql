-- 026 · Plan de montos (Fase 0) — Ensanchar el presupuesto de la fuente.
-- `fuente_financiamiento.presupuesto` era decimal(8,2) => máximo S/ 999,999.99, y los
-- presupuestos reales de Arco Iris van de S/ 1M a S/ 10M: el techo estaba por debajo
-- del piso y, sin STRICT_TRANS_TABLES, MariaDB clampeaba EN SILENCIO. Se alinea con
-- `detalle_financiamiento.monto_asignado`, `poa.presupuesto` y
-- `fuente_presupuesto_anual.monto_inicial`, que ya son decimal(14,2). Era la única
-- columna sin ensanchar, siendo la raíz del árbol (todos los topes cuelgan de ella).
-- Las vistas que arrastran el tipo re-resuelven solas tras el ALTER (verificado).

ALTER TABLE `fuente_financiamiento`
  MODIFY `presupuesto` DECIMAL(14,2) NOT NULL DEFAULT 0.00;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('026');
