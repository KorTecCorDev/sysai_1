-- 034 — Reporte de rendición re-anclado a RUBRO (item 9, decisiones 2026-07-16).
-- La vista `reporte_poa_rendicion` agrupaba por (actividad, fuente): un fósil de
-- la era pre-migr. 017, cuando la rendición apuntaba a la actividad. El Excel
-- dibuja UNA FILA POR RUBRO, así que las sumas caían en la fila del último rubro
-- de la actividad (desalineadas). Hoy la rendición vive en el rubro
-- (rendicion.rubro_id) → el grano correcto es (rubro, fuente): cada celda del
-- reporte responde "¿cuánto se rindió de ESTE rubro con ESTA fuente?".
--
-- Además (decisiones de negocio 2026-07-16):
--   * Solo rendiciones APROBADAS (estado=1): es el documento de rendición de
--     cuentas; las pendientes aún pueden observarse.
--   * Solo el EJERCICIO VIGENTE: por año de la fecha de operación
--     (fecha_original), consistente con el TC congelado (migr. 029).
-- Columna NUEVA (`rubro_id`) AL FINAL — convención migr. 030: los consumidores
-- existentes no se desalinean. Consumidores: RendicionFuentesVista →
-- views/reporte/poarendicion.php y poarubros.php (builder de la Fase E).

CREATE OR REPLACE VIEW `reporte_poa_rendicion` AS
SELECT
  `a`.`id` AS `actividad_id`,
  `a`.`nombre` AS `actividad_nombre`,
  `f`.`id` AS `fuente_financiamiento_id`,
  `f`.`nombre` AS `fuente_financiamiento_nombre`,
  SUM(`r`.`monto`) AS `suma_monto_rendiciones`,
  SUM(ROUND(`r`.`monto` / NULLIF(`r`.`tc_usd`, 0), 2)) AS `suma_usd`,
  SUM(ROUND(`r`.`monto` / NULLIF(`r`.`tc_eur`, 0), 2)) AS `suma_eur`,
  SUM(`r`.`tc_usd` IS NULL OR `r`.`tc_eur` IS NULL) AS `rendiciones_sin_tc`,
  `ru`.`id` AS `rubro_id`
FROM `rendicion` `r`
JOIN `rubro` `ru` ON `ru`.`id` = `r`.`rubro_id`
JOIN `actividad` `a` ON `a`.`id` = `ru`.`actividad_id`
JOIN `fuente_financiamiento` `f` ON `r`.`ff_id` = `f`.`id`
WHERE `r`.`estado` = 1
  AND YEAR(`r`.`fecha_original`) = YEAR(CURDATE())
GROUP BY `ru`.`id`, `f`.`id`, `f`.`nombre`, `a`.`id`, `a`.`nombre`
ORDER BY `a`.`id`, `ru`.`id`;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('034');
