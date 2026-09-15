-- 018 · Item 6 (POA Rendición) — el saldo contable solo descuenta rendiciones APROBADAS.
--
-- Decisión 2026-06-05: el "POA Rendición" NO es un documento aparte; es el mismo POA
-- Presupuestal (su reporte Excel `/reporte/poarendicion` se mantiene tal cual). Al APROBAR
-- el POA Presupuestal, las rendiciones del programa pasan a `rendicion.estado = 1` (Aprobada)
-- y recién entonces se descuentan del saldo contable. Mientras están en `estado = 0`
-- (Pendiente/registrada) NO afectan el saldo.
--
-- Por eso las 2 vistas de saldo CONTABLE (migr. 010) pasan a filtrar `rendicion.estado = 1`.
-- Las vistas de REPORTE que suman rendiciones (reporte_poa_rendicion,
-- total_monto_rendiciones_por_actividad, etc.) se mantienen SIN filtrar: reportan todo lo
-- registrado, no solo lo aprobado. CREATE OR REPLACE es idempotente; sin DEFINER explícito.

CREATE OR REPLACE VIEW `vista_total_egresos` AS
SELECT
  COALESCE((
      SELECT SUM(oc.`monto`)
      FROM `otros_ingresos_egresos` oie
      JOIN `oie_comprobante` oc ON oc.`id` = oie.`oie_comprobante_id`
      WHERE oie.`oie_tipo_id` = 2
    ), 0)
  + COALESCE((SELECT SUM(`monto`) FROM `rendicion` WHERE `estado` = 1), 0) AS `total_egresos`;

CREATE OR REPLACE VIEW `vista_saldo_fuente_financiamiento` AS
SELECT
  ff.`id`     AS `fuente_financiamiento_id`,
  ff.`codigo` AS `fuente_financiamiento_codigo`,
  ff.`nombre` AS `fuente_financiamiento_nombre`,
  ff.`presupuesto`
  + COALESCE((
      SELECT SUM(oc.`monto`)
      FROM `otros_ingresos_egresos` oie
      JOIN `oie_comprobante` oc ON oc.`id` = oie.`oie_comprobante_id`
      WHERE oie.`ff_id` = ff.`id` AND oie.`oie_tipo_id` = 1
    ), 0)
  - COALESCE((
      SELECT SUM(oc.`monto`)
      FROM `otros_ingresos_egresos` oie
      JOIN `oie_comprobante` oc ON oc.`id` = oie.`oie_comprobante_id`
      WHERE oie.`ff_id` = ff.`id` AND oie.`oie_tipo_id` = 2
    ), 0)
  - COALESCE((
      SELECT SUM(r.`monto`)
      FROM `rendicion` r
      WHERE r.`ff_id` = ff.`id` AND r.`estado` = 1
    ), 0) AS `fuente_financiamiento_saldo`
FROM `fuente_financiamiento` ff;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('018');
