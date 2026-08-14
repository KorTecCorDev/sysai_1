-- 035 — Vistas de los reportes de INGRESOS y EGRESOS/RENDICIONES saneadas.
-- Fase 1 de docs/plan-reportes-ingresos-y-rendiciones.md (decisiones 2026-08-14).
--
-- EL BUG QUE CIERRA (§2.1 del plan, medido contra la BD local):
--   `reporte_ingresos` y `reporte_egresos` colgaban de
--       JOIN detalle_financiamiento d ON d.fuente_financiamiento_id = o.ff_id
--       JOIN programa g               ON g.id = d.programa_id
--   Un INNER JOIN emparejado SOLO POR FUENTE, sin relación con el programa del
--   movimiento. Consecuencias, ambas verificadas:
--     * Si la fuente no tiene sobres, no hay fila que emparejar y el movimiento
--       DESAPARECE del reporte. Con dos ingresos reales: suma real S/ 35 000,
--       suma reportada S/ 10 000. Es justo el caso del ingreso híbrido
--       (programa_id NULL, dinero al remanente) que las reglas contemplan.
--     * Si la fuente tiene N sobres, el JOIN produce N copias de cada
--       movimiento. Hoy lo tapaba el SELECT DISTINCT: el DISTINCT no era una
--       optimización, era lo único que impedía duplicar importes. Añadir al
--       SELECT cualquier columna de `d`/`g` habría empezado a inflar las sumas.
--   Ni `detalle_financiamiento`, ni `programa`, ni `oie_tipo` aportaban una sola
--   columna al SELECT. Se retiran los tres, y con ellos el DISTINCT: dejarlo
--   enmascararía futuros errores de join.
--
-- LOS DEMÁS ARREGLOS
--   * `oie_comprobante` pasa a INNER JOIN: la FK es NOT NULL, y el LEFT JOIN que
--     había quedaba anulado de todos modos por el INNER a oie_tipo_comprobante.
--   * `oie_tipo_comprobante` se une también en INGRESOS, que no lo seleccionaba:
--     por eso la columna TIPO_COMPROBANTE de ese reporte salía SIEMPRE vacía.
--   * `LEFT JOIN programa ON programa.id = o.programa_id` — el programa DEL
--     MOVIMIENTO. NULL = ingreso al remanente de la fuente, que es un dato, no
--     un error: por eso LEFT y no INNER.
--   * `reporte_ingresos` no hacía CAST de la fecha a `date` y su gemela sí, de
--     ahí el datetime con segundos en un reporte y la fecha limpia en el otro.
--
-- LAS DOS VISTAS DE OIE QUEDAN ESTRUCTURALMENTE IDÉNTICAS (mismas columnas, en
-- el mismo orden); solo cambia el `WHERE oie_tipo_id`. Alimentan el mismo
-- builder y no tiene sentido que difieran.
--
-- COLUMNAS NUEVAS AL FINAL (convención de la migr. 030): los consumidores
-- actuales no se desalinean. Consumidores: ReporteIngresosVista /
-- ReporteEgresosVista / ReporteRendicionesVista → los reportes /reporte/ingresos
-- y /reporte/rendiciones (builder de la Fase 2).
--
-- LAS DOS FECHAS, con nombre y papel explícitos:
--   `*_fecha`           = fecha de REGISTRO en el sistema.
--   `*_fecha_original`  = fecha de OPERACIÓN. Es la que filtra y la que se
--                         imprime (decisión D4), coherente con el TC congelado
--                         de la migr. 029 y con NIC 21.
--
-- EL ESTADO NO SE FILTRA AQUÍ, A PROPÓSITO: el reporte muestra solo rendiciones
-- APROBADAS (D3), pero ese filtro vive en el builder para que esta misma vista
-- pueda alimentar mañana un reporte de pendientes sin duplicar SQL. La columna
-- `rendicion_estado` se expone para que el Excel deje constancia de qué muestra.


-- ---------------------------------------------------------------------------
-- INGRESOS (oie_tipo_id = 1)
-- ---------------------------------------------------------------------------
CREATE OR REPLACE VIEW `reporte_ingresos` AS
SELECT
  `o`.`id`                                          AS `otros_ingresos_egresos_id`,
  CAST(`o`.`fecha` AS date)                         AS `otros_ingresos_egresos_fecha`,
  `o`.`codigo`                                      AS `otros_ingresos_egresos_codigo`,
  `o`.`descripcion`                                 AS `otros_ingresos_egresos_descripcion`,
  `p`.`id`                                          AS `oie_tipo_comprobante_id`,
  `p`.`codigo`                                      AS `oie_tipo_comprobante_codigo`,
  `o`.`oie_tipo_id`                                 AS `otros_ingresos_egresos_oie_tipo_id`,
  `f`.`id`                                          AS `fuente_financiamiento_id`,
  `f`.`codigo`                                      AS `fuente_financiamiento_codigo`,
  `c`.`fecha_original`                              AS `oie_comprobante_fecha_original`,
  `c`.`ruc`                                         AS `oie_comprobante_ruc`,
  `c`.`razon_social`                                AS `oie_comprobante_razon_social`,
  `c`.`serie`                                       AS `oie_comprobante_serie`,
  `c`.`numero`                                      AS `oie_comprobante_numero`,
  `c`.`descripcion`                                 AS `oie_comprobante_descripcion`,
  `c`.`monto`                                       AS `oie_comprobante_monto`,
  ROUND(`c`.`monto` / NULLIF(`c`.`tc_usd`, 0), 2)   AS `oie_comprobante_monto_usd`,
  ROUND(`c`.`monto` / NULLIF(`c`.`tc_eur`, 0), 2)   AS `oie_comprobante_monto_eur`,
  -- Nuevas (al final)
  `o`.`programa_id`                                 AS `programa_id`,
  `g`.`codigo`                                      AS `programa_codigo`,
  `g`.`nombre`                                      AS `programa_nombre`,
  `f`.`nombre`                                      AS `fuente_financiamiento_nombre`,
  `p`.`nombre`                                      AS `oie_tipo_comprobante_nombre`
FROM `otros_ingresos_egresos` `o`
JOIN `oie_comprobante` `c`            ON `c`.`id` = `o`.`oie_comprobante_id`
JOIN `fuente_financiamiento` `f`      ON `f`.`id` = `o`.`ff_id`
LEFT JOIN `oie_tipo_comprobante` `p`  ON `p`.`id` = `c`.`oie_tipo_comprobante_id`
LEFT JOIN `programa` `g`              ON `g`.`id` = `o`.`programa_id`
WHERE `o`.`oie_tipo_id` = 1;


-- ---------------------------------------------------------------------------
-- EGRESOS (oie_tipo_id = 2) — gemela de la anterior
-- ---------------------------------------------------------------------------
CREATE OR REPLACE VIEW `reporte_egresos` AS
SELECT
  `o`.`id`                                          AS `otros_ingresos_egresos_id`,
  CAST(`o`.`fecha` AS date)                         AS `otros_ingresos_egresos_fecha`,
  `o`.`codigo`                                      AS `otros_ingresos_egresos_codigo`,
  `o`.`descripcion`                                 AS `otros_ingresos_egresos_descripcion`,
  `p`.`id`                                          AS `oie_tipo_comprobante_id`,
  `p`.`codigo`                                      AS `oie_tipo_comprobante_codigo`,
  `o`.`oie_tipo_id`                                 AS `otros_ingresos_egresos_oie_tipo_id`,
  `f`.`id`                                          AS `fuente_financiamiento_id`,
  `f`.`codigo`                                      AS `fuente_financiamiento_codigo`,
  `c`.`fecha_original`                              AS `oie_comprobante_fecha_original`,
  `c`.`ruc`                                         AS `oie_comprobante_ruc`,
  `c`.`razon_social`                                AS `oie_comprobante_razon_social`,
  `c`.`serie`                                       AS `oie_comprobante_serie`,
  `c`.`numero`                                      AS `oie_comprobante_numero`,
  `c`.`descripcion`                                 AS `oie_comprobante_descripcion`,
  `c`.`monto`                                       AS `oie_comprobante_monto`,
  ROUND(`c`.`monto` / NULLIF(`c`.`tc_usd`, 0), 2)   AS `oie_comprobante_monto_usd`,
  ROUND(`c`.`monto` / NULLIF(`c`.`tc_eur`, 0), 2)   AS `oie_comprobante_monto_eur`,
  -- Nuevas (al final)
  `o`.`programa_id`                                 AS `programa_id`,
  `g`.`codigo`                                      AS `programa_codigo`,
  `g`.`nombre`                                      AS `programa_nombre`,
  `f`.`nombre`                                      AS `fuente_financiamiento_nombre`,
  `p`.`nombre`                                      AS `oie_tipo_comprobante_nombre`
FROM `otros_ingresos_egresos` `o`
JOIN `oie_comprobante` `c`            ON `c`.`id` = `o`.`oie_comprobante_id`
JOIN `fuente_financiamiento` `f`      ON `f`.`id` = `o`.`ff_id`
LEFT JOIN `oie_tipo_comprobante` `p`  ON `p`.`id` = `c`.`oie_tipo_comprobante_id`
LEFT JOIN `programa` `g`              ON `g`.`id` = `o`.`programa_id`
WHERE `o`.`oie_tipo_id` = 2;


-- ---------------------------------------------------------------------------
-- RENDICIONES
-- ---------------------------------------------------------------------------
-- Se retiran: el `GROUP BY r.id` (no había ninguna agregación que agrupar) y el
-- duplicado `rendicion_monto` (misma columna que `rendicion_comprobante_monto`).
-- La cadena rubro→actividad→producto→resultado→programa SE CONSERVA y pasa a
-- tener propósito: antes eran INNER JOIN que no aportaban ni una columna al
-- SELECT pero sí podían descartar filas; ahora alimentan PROGRAMA y RUBRO.
CREATE OR REPLACE VIEW `reporte_rendiciones` AS
SELECT
  `r`.`id`                                          AS `rendicion_id`,
  CAST(`r`.`fecha` AS date)                         AS `rendicion_fecha`,
  `r`.`codigo`                                      AS `rendicion_codigo`,
  `r`.`descripcion`                                 AS `rendicion_descripcion`,
  `t`.`id`                                          AS `rendicion_tipo_comprobante_id`,
  `t`.`codigo`                                      AS `tipo_comprobante_codigo`,
  `f`.`id`                                          AS `fuente_financiamiento_id`,
  `f`.`codigo`                                      AS `fuente_financiamiento_codigo`,
  `r`.`fecha_original`                              AS `rendicion_fecha_original`,
  `r`.`ruc`                                         AS `rendicion_ruc`,
  `r`.`razon_social`                                AS `rendicion_razon_social`,
  `r`.`serie`                                       AS `rendicion_serie`,
  `r`.`numero`                                      AS `rendicion_numero`,
  `r`.`detalle`                                     AS `rendicion_detalle`,
  `r`.`monto`                                       AS `rendicion_comprobante_monto`,
  ROUND(`r`.`monto` / NULLIF(`r`.`tc_usd`, 0), 2)   AS `rendicion_monto_usd`,
  ROUND(`r`.`monto` / NULLIF(`r`.`tc_eur`, 0), 2)   AS `rendicion_monto_eur`,
  -- Nuevas (al final)
  `r`.`estado`                                      AS `rendicion_estado`,
  `ru`.`id`                                         AS `rubro_id`,
  `ru`.`codigo`                                     AS `rubro_codigo`,
  `ru`.`nombre`                                     AS `rubro_nombre`,
  `g`.`id`                                          AS `programa_id`,
  `g`.`codigo`                                      AS `programa_codigo`,
  `g`.`nombre`                                      AS `programa_nombre`,
  `f`.`nombre`                                      AS `fuente_financiamiento_nombre`,
  `t`.`descripcion`                                 AS `tipo_comprobante_descripcion`
FROM `rendicion` `r`
JOIN `tipo_comprobante` `t`       ON `t`.`id` = `r`.`tipo_comprobante_id`
JOIN `fuente_financiamiento` `f`  ON `f`.`id` = `r`.`ff_id`
JOIN `rubro` `ru`                 ON `ru`.`id` = `r`.`rubro_id`
JOIN `actividad` `a`              ON `a`.`id` = `ru`.`actividad_id`
JOIN `producto` `o`               ON `o`.`id` = `a`.`producto_id`
JOIN `resultado` `e`              ON `e`.`id` = `o`.`resultado_id`
JOIN `programa` `g`               ON `g`.`id` = `e`.`programa_id`;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('035');
