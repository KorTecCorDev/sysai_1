-- 007 · Grupo 13/OIE — Desvincular Otros Ingresos/Egresos del POA
-- El OIE ya no se ata a un POA sino directamente al programa (solo Contador,
-- descuento inmediato). El monto vive en oie_comprobante.monto.
-- La vista otros_ingresos_egresos_admin_vista depende de poa_id: se recrea.

-- 1) Soltar la vista dependiente antes de tocar la columna
DROP VIEW IF EXISTS `otros_ingresos_egresos_admin_vista`;

-- 2) Nueva columna y backfill desde el programa del POA actual
ALTER TABLE `otros_ingresos_egresos`
  ADD COLUMN `programa_id` int(11) DEFAULT NULL AFTER `id`;

UPDATE `otros_ingresos_egresos` `o`
  JOIN `poa` `p` ON `o`.`poa_id` = `p`.`id`
  SET `o`.`programa_id` = `p`.`programa_id`;

-- 3) Quitar FK + columna poa_id
ALTER TABLE `otros_ingresos_egresos`
  DROP FOREIGN KEY `fk_otros_ingresos_egresos_poa1`;
ALTER TABLE `otros_ingresos_egresos`
  DROP COLUMN `poa_id`;

-- 4) Fijar NOT NULL + FK a programa
ALTER TABLE `otros_ingresos_egresos`
  MODIFY `programa_id` int(11) NOT NULL;
ALTER TABLE `otros_ingresos_egresos`
  ADD KEY `fk_oie_programa_idx` (`programa_id`),
  ADD CONSTRAINT `fk_oie_programa` FOREIGN KEY (`programa_id`) REFERENCES `programa` (`id`);

-- 5) Recrear la vista apuntando a programa en vez de poa
CREATE OR REPLACE VIEW `otros_ingresos_egresos_admin_vista` AS
SELECT
  `o`.`id`        AS `id`,
  `o`.`codigo`    AS `codigo`,
  `p`.`nombre`    AS `tipo`,
  `t`.`codigo`    AS `tipo_comprobante_codigo`,
  `i`.`monto`     AS `comprobante_monto`,
  `i`.`fecha_original` AS `comprobante_fecha`
FROM `otros_ingresos_egresos` `o`
  JOIN `oie_comprobante` `i` ON `o`.`oie_comprobante_id` = `i`.`id`
  JOIN `oie_tipo_comprobante` `t` ON `i`.`oie_tipo_comprobante_id` = `t`.`id`
  JOIN `oie_tipo` `p` ON `o`.`oie_tipo_id` = `p`.`id`
  JOIN `programa` `g` ON `o`.`programa_id` = `g`.`id`
ORDER BY `o`.`id` DESC;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('007');
