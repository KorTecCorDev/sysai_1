-- 010 · Seguridad B1 — Vistas de saldos contables (/saldos_contables/saldos)
-- Portada desde db/migracion_saldos.sql (rama seguridad/hardening-y-despliegue-local).
-- Los modelos VistaTotalIngresos/VistaTotalEgresos/VistaSaldoContable/
-- SaldoFuenteFinanciamientoVista apuntaban a vistas inexistentes -> fatal al abrir saldos.
--
-- Regla contable: SALDO = Presupuesto + Ingresos(OIE tipo 1) − Egresos(OIE tipo 2 + rendiciones),
-- imputado por ff_id. Se usan subconsultas agregadas independientes para evitar el fan-out (bug B2).
-- CREATE OR REPLACE es idempotente; sin DEFINER explícito (lo asigna quien ejecuta).
--
-- NOTA: el saldo por fuente usa fuente_financiamiento.presupuesto. Tras Grupo 13
-- (tabla fuente_presupuesto_anual, migr. 008) esta lógica podría moverse al año vigente
-- — ver backlog de implementación #8.

CREATE OR REPLACE VIEW `vista_total_ingresos` AS
SELECT
  COALESCE((SELECT SUM(`presupuesto`) FROM `fuente_financiamiento`), 0)
  + COALESCE((
      SELECT SUM(oc.`monto`)
      FROM `otros_ingresos_egresos` oie
      JOIN `oie_comprobante` oc ON oc.`id` = oie.`oie_comprobante_id`
      WHERE oie.`oie_tipo_id` = 1
    ), 0) AS `total_ingresos`;

CREATE OR REPLACE VIEW `vista_total_egresos` AS
SELECT
  COALESCE((
      SELECT SUM(oc.`monto`)
      FROM `otros_ingresos_egresos` oie
      JOIN `oie_comprobante` oc ON oc.`id` = oie.`oie_comprobante_id`
      WHERE oie.`oie_tipo_id` = 2
    ), 0)
  + COALESCE((SELECT SUM(`monto`) FROM `rendicion`), 0) AS `total_egresos`;

CREATE OR REPLACE VIEW `vista_saldo_contable` AS
SELECT
  (SELECT `total_ingresos` FROM `vista_total_ingresos`)
  - (SELECT `total_egresos` FROM `vista_total_egresos`) AS `saldo_contable`;

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
      WHERE r.`ff_id` = ff.`id`
    ), 0) AS `fuente_financiamiento_saldo`
FROM `fuente_financiamiento` ff;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('010');
