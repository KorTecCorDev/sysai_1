-- 030 · Plan de montos (Fase 4) — Reportes con conversión congelada.
-- Las vistas de rendiciones y OIE exponen los importes convertidos con el TC
-- CONGELADO en cada fila (migr. 029), no con "el último TC":
--   * ROUND(monto / NULLIF(tc,0), 2): sin TC => NULL (se muestra "—"), jamás un
--     DivisionByZeroError ni un cero mentiroso.
--   * En las agregaciones se suman valores YA redondeados => el total cuadra con la
--     suma de las filas mostradas. SUM() ignora los NULL: el convertido puede ser
--     PARCIAL — por eso se expone `rendiciones_sin_tc` para el aviso
--     "Faltan N tipos de cambio. Los importes en USD/EUR están incompletos."
-- Columnas NUEVAS siempre AL FINAL: los consumidores existentes no se desalinean.

CREATE OR REPLACE VIEW `reporte_poa_rendicion` AS
SELECT
  `a`.`id` AS `actividad_id`,
  `a`.`nombre` AS `actividad_nombre`,
  `f`.`id` AS `fuente_financiamiento_id`,
  `f`.`nombre` AS `fuente_financiamiento_nombre`,
  SUM(`r`.`monto`) AS `suma_monto_rendiciones`,
  SUM(ROUND(`r`.`monto` / NULLIF(`r`.`tc_usd`, 0), 2)) AS `suma_usd`,
  SUM(ROUND(`r`.`monto` / NULLIF(`r`.`tc_eur`, 0), 2)) AS `suma_eur`,
  SUM(`r`.`tc_usd` IS NULL OR `r`.`tc_eur` IS NULL) AS `rendiciones_sin_tc`
FROM `rendicion` `r`
JOIN `rubro` `ru` ON `ru`.`id` = `r`.`rubro_id`
JOIN `actividad` `a` ON `a`.`id` = `ru`.`actividad_id`
JOIN `fuente_financiamiento` `f` ON `r`.`ff_id` = `f`.`id`
GROUP BY `f`.`id`, `f`.`nombre`, `a`.`id`, `a`.`nombre`
ORDER BY `a`.`id`;

CREATE OR REPLACE VIEW `total_monto_rendiciones_por_actividad` AS
SELECT
  `a`.`id` AS `actividad_id`,
  `a`.`codigo` AS `actividad_codigo`,
  `a`.`nombre` AS `actividad_nombre`,
  SUM(`r`.`monto`) AS `total_monto_rendiciones`,
  SUM(ROUND(`r`.`monto` / NULLIF(`r`.`tc_usd`, 0), 2)) AS `total_usd`,
  SUM(ROUND(`r`.`monto` / NULLIF(`r`.`tc_eur`, 0), 2)) AS `total_eur`,
  SUM(`r`.`tc_usd` IS NULL OR `r`.`tc_eur` IS NULL) AS `rendiciones_sin_tc`
FROM `rendicion` `r`
JOIN `rubro` `ru` ON `ru`.`id` = `r`.`rubro_id`
JOIN `actividad` `a` ON `a`.`id` = `ru`.`actividad_id`
GROUP BY `a`.`id`, `a`.`codigo`, `a`.`nombre`
ORDER BY `a`.`id`;

CREATE OR REPLACE VIEW `reporte_rendiciones` AS
SELECT
  `r`.`id` AS `rendicion_id`,
  CAST(`r`.`fecha` AS date) AS `rendicion_fecha`,
  `r`.`codigo` AS `rendicion_codigo`,
  `r`.`descripcion` AS `rendicion_descripcion`,
  `t`.`id` AS `rendicion_tipo_comprobante_id`,
  `t`.`codigo` AS `tipo_comprobante_codigo`,
  `r`.`monto` AS `rendicion_monto`,
  `f`.`id` AS `fuente_financiamiento_id`,
  `f`.`codigo` AS `fuente_financiamiento_codigo`,
  `r`.`fecha_original` AS `rendicion_fecha_original`,
  `r`.`ruc` AS `rendicion_ruc`,
  `r`.`razon_social` AS `rendicion_razon_social`,
  `r`.`serie` AS `rendicion_serie`,
  `r`.`numero` AS `rendicion_numero`,
  `r`.`detalle` AS `rendicion_detalle`,
  `r`.`monto` AS `rendicion_comprobante_monto`,
  ROUND(`r`.`monto` / NULLIF(`r`.`tc_usd`, 0), 2) AS `rendicion_monto_usd`,
  ROUND(`r`.`monto` / NULLIF(`r`.`tc_eur`, 0), 2) AS `rendicion_monto_eur`
FROM `rendicion` `r`
JOIN `tipo_comprobante` `t` ON `r`.`tipo_comprobante_id` = `t`.`id`
JOIN `rubro` `ru` ON `ru`.`id` = `r`.`rubro_id`
JOIN `actividad` `a` ON `a`.`id` = `ru`.`actividad_id`
JOIN `producto` `o` ON `a`.`producto_id` = `o`.`id`
JOIN `resultado` `e` ON `o`.`resultado_id` = `e`.`id`
JOIN `programa` `g` ON `e`.`programa_id` = `g`.`id`
JOIN `fuente_financiamiento` `f` ON `f`.`id` = `r`.`ff_id`
GROUP BY `r`.`id`;

CREATE OR REPLACE VIEW `reporte_egresos` AS
SELECT DISTINCT
  `o`.`id` AS `otros_ingresos_egresos_id`,
  CAST(`o`.`fecha` AS date) AS `otros_ingresos_egresos_fecha`,
  `o`.`codigo` AS `otros_ingresos_egresos_codigo`,
  `o`.`descripcion` AS `otros_ingresos_egresos_descripcion`,
  `p`.`id` AS `oie_tipo_comprobante_id`,
  `p`.`codigo` AS `oie_tipo_comprobante_codigo`,
  `o`.`oie_tipo_id` AS `otros_ingresos_egresos_oie_tipo_id`,
  `f`.`id` AS `fuente_financiamiento_id`,
  `f`.`codigo` AS `fuente_financiamiento_codigo`,
  `c`.`fecha_original` AS `oie_comprobante_fecha_original`,
  `c`.`ruc` AS `oie_comprobante_ruc`,
  `c`.`razon_social` AS `oie_comprobante_razon_social`,
  `c`.`serie` AS `oie_comprobante_serie`,
  `c`.`numero` AS `oie_comprobante_numero`,
  `c`.`descripcion` AS `oie_comprobante_descripcion`,
  `c`.`monto` AS `oie_comprobante_monto`,
  ROUND(`c`.`monto` / NULLIF(`c`.`tc_usd`, 0), 2) AS `oie_comprobante_monto_usd`,
  ROUND(`c`.`monto` / NULLIF(`c`.`tc_eur`, 0), 2) AS `oie_comprobante_monto_eur`
FROM `otros_ingresos_egresos` `o`
LEFT JOIN `oie_comprobante` `c` ON `o`.`oie_comprobante_id` = `c`.`id`
JOIN `oie_tipo_comprobante` `p` ON `p`.`id` = `c`.`oie_tipo_comprobante_id`
JOIN `detalle_financiamiento` `d` ON `d`.`fuente_financiamiento_id` = `o`.`ff_id`
JOIN `fuente_financiamiento` `f` ON `f`.`id` = `d`.`fuente_financiamiento_id`
JOIN `programa` `g` ON `g`.`id` = `d`.`programa_id`
JOIN `oie_tipo` `t` ON `t`.`id` = `o`.`oie_tipo_id`
WHERE `o`.`oie_tipo_id` = 2;

CREATE OR REPLACE VIEW `reporte_ingresos` AS
SELECT DISTINCT
  `o`.`id` AS `otros_ingresos_egresos_id`,
  `o`.`fecha` AS `otros_ingresos_egresos_fecha`,
  `o`.`codigo` AS `otros_ingresos_egresos_codigo`,
  `o`.`descripcion` AS `otros_ingresos_egresos_descripcion`,
  `o`.`oie_tipo_id` AS `otros_ingresos_egresos_oie_tipo_id`,
  `f`.`id` AS `fuente_financiamiento_id`,
  `f`.`codigo` AS `fuente_financiamiento_codigo`,
  `c`.`fecha_original` AS `oie_comprobante_fecha_original`,
  `c`.`ruc` AS `oie_comprobante_ruc`,
  `c`.`razon_social` AS `oie_comprobante_razon_social`,
  `c`.`serie` AS `oie_comprobante_serie`,
  `c`.`numero` AS `oie_comprobante_numero`,
  `c`.`descripcion` AS `oie_comprobante_descripcion`,
  `c`.`monto` AS `oie_comprobante_monto`,
  ROUND(`c`.`monto` / NULLIF(`c`.`tc_usd`, 0), 2) AS `oie_comprobante_monto_usd`,
  ROUND(`c`.`monto` / NULLIF(`c`.`tc_eur`, 0), 2) AS `oie_comprobante_monto_eur`
FROM `otros_ingresos_egresos` `o`
LEFT JOIN `oie_comprobante` `c` ON `o`.`oie_comprobante_id` = `c`.`id`
JOIN `detalle_financiamiento` `d` ON `d`.`fuente_financiamiento_id` = `o`.`ff_id`
JOIN `fuente_financiamiento` `f` ON `f`.`id` = `d`.`fuente_financiamiento_id`
JOIN `programa` `g` ON `g`.`id` = `d`.`programa_id`
JOIN `oie_tipo` `t` ON `t`.`id` = `o`.`oie_tipo_id`
WHERE `o`.`oie_tipo_id` = 1;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('030');
