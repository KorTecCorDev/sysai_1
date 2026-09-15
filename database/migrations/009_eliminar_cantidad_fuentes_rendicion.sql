-- 009 · Limpieza — eliminar cantidad_fuentes_rendicion
-- Concepto descartado: una rendición usa una sola fuente.
-- OJO: es una VISTA (no tabla), por eso DROP VIEW.
-- Pendiente fase de código: retirar el modelo RendicionFuentesCantidadVista.

DROP VIEW IF EXISTS `cantidad_fuentes_rendicion`;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('009');
