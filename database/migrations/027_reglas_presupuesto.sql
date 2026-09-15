-- 027 · Plan de montos (Fase 1) — Reglas de presupuesto.
--
-- 1) `vista_saldo_fuente_financiamiento` expone las TRES cifras diferenciadas (§2.4
--    del plan): el presupuesto NO se muta con los ingresos, se calcula en vivo.
--      presupuesto_inicial = compromiso original (inmutable)
--      presupuesto_vigente = inicial + TODOS los ingresos OIE
--      capacidad_asignable = inicial + ingresos SIN programa (NULL) − Σ monto_asignado
--    Los ingresos dirigidos a un sobre se cancelan en ambos lados (ya entran al saldo
--    del sobre vía vista_saldo_sobre): sumarlos también a la capacidad asignable
--    contaría el mismo dinero dos veces.
--    `fuente_financiamiento_saldo` (contable) conserva su fórmula y su alias.
--
-- 2) `vista_saldo_rubro` (nueva, §2.3): el rubro NO limita el gasto (enmienda
--    2026-07-09) pero recupera visibilidad — saldo con signo: positivo = sobrante,
--    negativo = sobregasto ("excedente"). Cuenta rendiciones de todo estado.

CREATE OR REPLACE VIEW `vista_saldo_fuente_financiamiento` AS
SELECT
  `ff`.`id`     AS `fuente_financiamiento_id`,
  `ff`.`codigo` AS `fuente_financiamiento_codigo`,
  `ff`.`nombre` AS `fuente_financiamiento_nombre`,
  `ff`.`presupuesto` AS `presupuesto_inicial`,
  `ff`.`presupuesto`
    + COALESCE((SELECT SUM(`oc`.`monto`)
                FROM `otros_ingresos_egresos` `oie`
                JOIN `oie_comprobante` `oc` ON `oc`.`id` = `oie`.`oie_comprobante_id`
                WHERE `oie`.`ff_id` = `ff`.`id` AND `oie`.`oie_tipo_id` = 1), 0)
    AS `presupuesto_vigente`,
  `ff`.`presupuesto`
    + COALESCE((SELECT SUM(`oc`.`monto`)
                FROM `otros_ingresos_egresos` `oie`
                JOIN `oie_comprobante` `oc` ON `oc`.`id` = `oie`.`oie_comprobante_id`
                WHERE `oie`.`ff_id` = `ff`.`id` AND `oie`.`oie_tipo_id` = 1
                  AND `oie`.`programa_id` IS NULL), 0)
    - COALESCE((SELECT SUM(`df`.`monto_asignado`)
                FROM `detalle_financiamiento` `df`
                WHERE `df`.`fuente_financiamiento_id` = `ff`.`id`), 0)
    AS `capacidad_asignable`,
  `ff`.`presupuesto`
    + COALESCE((SELECT SUM(`oc`.`monto`)
                FROM `otros_ingresos_egresos` `oie`
                JOIN `oie_comprobante` `oc` ON `oc`.`id` = `oie`.`oie_comprobante_id`
                WHERE `oie`.`ff_id` = `ff`.`id` AND `oie`.`oie_tipo_id` = 1), 0)
    - COALESCE((SELECT SUM(`oc`.`monto`)
                FROM `otros_ingresos_egresos` `oie`
                JOIN `oie_comprobante` `oc` ON `oc`.`id` = `oie`.`oie_comprobante_id`
                WHERE `oie`.`ff_id` = `ff`.`id` AND `oie`.`oie_tipo_id` = 2), 0)
    - COALESCE((SELECT SUM(`r`.`monto`)
                FROM `rendicion` `r`
                WHERE `r`.`ff_id` = `ff`.`id` AND `r`.`estado` = 1), 0)
    AS `fuente_financiamiento_saldo`
FROM `fuente_financiamiento` `ff`;

CREATE OR REPLACE VIEW `vista_saldo_rubro` AS
SELECT
  `r`.`id`           AS `rubro_id`,
  `r`.`actividad_id` AS `actividad_id`,
  `r`.`codigo`       AS `rubro_codigo`,
  `r`.`nombre`       AS `rubro_nombre`,
  `r`.`monto`        AS `monto`,
  COALESCE((SELECT SUM(`re`.`monto`) FROM `rendicion` `re` WHERE `re`.`rubro_id` = `r`.`id`), 0) AS `ejecutado`,
  `r`.`monto`
    - COALESCE((SELECT SUM(`re`.`monto`) FROM `rendicion` `re` WHERE `re`.`rubro_id` = `r`.`id`), 0) AS `saldo`
FROM `rubro` `r`;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('027');
