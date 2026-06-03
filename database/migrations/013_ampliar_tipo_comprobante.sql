-- 013 · Rendiciones — ampliar catálogo tipo_comprobante
-- Decisión de negocio 2026-06-03: se desdobla la "boleta" genérica y se
-- formalizan recibos para sustento de rendiciones.
--   - La "Boleta" genérica (TCM002) se reutiliza como "Boleta de venta"
--     (conserva la(s) rendición(es) que ya la referencian, sin romper la FK).
--   - "Boleta de viaje" se agrega como código nuevo.
--   - Se agregan recibos: de caja, de servicio básico, de viaje, de pago de
--     servicios y general.
-- Catálogo resultante (lista completa documentada en CLAUDE.md):
--   TCM001 Factura · TCM002 Boleta de venta · TCM003 Declaración Jurada ·
--   TCM004 Recibo por honorarios · TCM005 Boleta de viaje ·
--   TCM006 Recibo de caja · TCM007 Recibo de servicio básico ·
--   TCM008 Recibo de viaje · TCM009 Recibo de pago de servicios ·
--   TCM010 Recibo general

-- 1) Renombrar la boleta genérica a "Boleta de venta"
UPDATE `tipo_comprobante`
  SET `descripcion` = 'Boleta de venta'
  WHERE `codigo` = 'TCM002';

-- 2) Nuevos tipos (idempotente por UNIQUE(codigo))
INSERT IGNORE INTO `tipo_comprobante` (`codigo`, `descripcion`) VALUES
  ('TCM005', 'Boleta de viaje'),
  ('TCM006', 'Recibo de caja'),
  ('TCM007', 'Recibo de servicio básico'),
  ('TCM008', 'Recibo de viaje'),
  ('TCM009', 'Recibo de pago de servicios'),
  ('TCM010', 'Recibo general');

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('013');
