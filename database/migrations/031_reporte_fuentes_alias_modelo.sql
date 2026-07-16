-- 031 · Plan de montos (Fase 5) — Alinear `reporte_fuentes` con su modelo.
-- `ReporteFuentesVista` (y `findporRango('fuente_fecha', ...)` en el reporte de
-- ingresos) esperan columnas `fuente_*`, pero la vista exponía fecha/codigo/
-- descripcion/monto => "Unknown column 'fuente_fecha'" y /reporte/ingresosdesc
-- reventaba. El fatal quedó oculto hasta que el arnés qa_reportes.ps1 cubrió la
-- ruta. Se renombra en la vista (los alias del modelo son los descriptivos).

CREATE OR REPLACE VIEW `reporte_fuentes` AS
SELECT
  CAST(`fuente_financiamiento`.`fecha` AS date) AS `fuente_fecha`,
  `fuente_financiamiento`.`codigo` AS `fuente_codigo`,
  `fuente_financiamiento`.`nombre` AS `fuente_descripcion`,
  `fuente_financiamiento`.`presupuesto` AS `fuente_monto`
FROM `fuente_financiamiento`;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('031');
