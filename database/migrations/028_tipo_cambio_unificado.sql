-- 028 · Plan de montos (Fase 2) — Cimiento del tipo de cambio.
-- Reemplaza tipo_cambio_dolar/tipo_cambio_euro (y sus vistas) por UNA tabla donde la
-- moneda es un dato. Cambios de fondo respecto al módulo viejo:
--   * `fecha_vigencia` = la fecha a la que APLICA la tasa (≠ fecha de registro). El
--     "vigente a una fecha" se resuelve por fecha_vigencia máxima ≤ fecha (convención
--     contable para feriados/fines de semana), NUNCA por id/orden de tecleo.
--   * `compra` y `venta` separadas (ingreso → compra; gasto → venta; NIC 21).
--   * DECIMAL(12,6): la SBS publica 3 decimales; 6 es la convención ERP para tasas
--     (la conversión DIVIDE, y dividir amplifica el error de precisión).
--   * `origen` MANUAL | SBS (la tasa SBS es informativa: la dispara el Contador).
-- Sin migración de datos: ambas tablas viejas tienen 0 registros (verificado) y la
-- producción anterior fue dada de baja — el costo de normalizar está en su mínimo.

CREATE TABLE `tipo_cambio` (
  `id`             int(11) NOT NULL AUTO_INCREMENT,
  `moneda`         char(3) NOT NULL,                       -- 'USD' | 'EUR'
  `fecha_vigencia` date NOT NULL,                          -- fecha a la que aplica
  `compra`         decimal(12,6) NOT NULL,
  `venta`          decimal(12,6) NOT NULL,
  `origen`         varchar(20) NOT NULL DEFAULT 'MANUAL',  -- MANUAL | SBS
  `usuario_id`     int(11) NOT NULL,
  `fecha`          datetime NOT NULL,                      -- cuándo se registró
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_moneda_fecha` (`moneda`, `fecha_vigencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP VIEW IF EXISTS `vista_dolar`;
DROP VIEW IF EXISTS `vista_euro`;
DROP TABLE IF EXISTS `tipo_cambio_dolar`;
DROP TABLE IF EXISTS `tipo_cambio_euro`;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('028');
