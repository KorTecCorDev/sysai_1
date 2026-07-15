-- 025 · Item 7/OIE — Vista de administración compatible con el ingreso híbrido.
-- La vista de la migr. 007 hacía JOIN (interno) a programa: con programa_id NULL
-- (ingreso al total de la fuente, migr. 020) esas filas desaparecían del listado.
-- Se recrea con LEFT JOIN y se exponen programa y fuente para el listado del Contador.

CREATE OR REPLACE VIEW `otros_ingresos_egresos_admin_vista` AS
SELECT
  `o`.`id`             AS `id`,
  `o`.`codigo`         AS `codigo`,
  `o`.`descripcion`    AS `descripcion`,
  `o`.`oie_tipo_id`    AS `oie_tipo_id`,
  `p`.`nombre`         AS `tipo`,
  `o`.`programa_id`    AS `programa_id`,
  `g`.`nombre`         AS `programa_nombre`,
  `f`.`codigo`         AS `fuente_codigo`,
  `f`.`nombre`         AS `fuente_nombre`,
  `t`.`codigo`         AS `tipo_comprobante_codigo`,
  `i`.`monto`          AS `comprobante_monto`,
  `i`.`fecha_original` AS `comprobante_fecha`
FROM `otros_ingresos_egresos` `o`
  JOIN `oie_comprobante` `i` ON `o`.`oie_comprobante_id` = `i`.`id`
  JOIN `oie_tipo_comprobante` `t` ON `i`.`oie_tipo_comprobante_id` = `t`.`id`
  JOIN `oie_tipo` `p` ON `o`.`oie_tipo_id` = `p`.`id`
  LEFT JOIN `programa` `g` ON `o`.`programa_id` = `g`.`id`
  LEFT JOIN `fuente_financiamiento` `f` ON `o`.`ff_id` = `f`.`id`
ORDER BY `o`.`id` DESC;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('025');
