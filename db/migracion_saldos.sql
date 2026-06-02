-- ============================================================================
-- Migración B1 — Vistas de saldos contables (página /saldos_contables/saldos)
-- ----------------------------------------------------------------------------
-- Los modelos VistaTotalIngresos, VistaTotalEgresos, VistaSaldoContable y
-- SaldoFuenteFinanciamientoVista apuntaban a vistas que NO existían en la BD,
-- lo que provocaba un fatal al abrir la página de saldos. Aquí se crean.
--
-- Regla de negocio acordada:
--   SALDO = Presupuesto + Ingresos − Egresos
--     · Ingresos = otros_ingresos_egresos con oie_tipo_id = 1 (monto del comprobante)
--     · Egresos  = otros_ingresos_egresos con oie_tipo_id = 2 (monto del comprobante)
--                  MÁS los gastos rendidos en la tabla `rendicion`
--   Imputación por `ff_id` (fuente de financiamiento) en el saldo por fuente.
--
-- Nota: se usan subconsultas agregadas independientes (NO joins directos entre
-- fuente/oie/rendicion) para evitar el fan-out cartesiano (mismo patrón del bug
-- de reportes B2). Cada SUM se calcula sobre su propia tabla.
--
-- Para Hostinger: ejecutar tal cual sobre la BD de producción (u612374195_sysai).
-- CREATE OR REPLACE es idempotente. No se define DEFINER (lo asigna el usuario
-- que ejecuta) para evitar el problema de definer root@localhost.
-- ============================================================================

-- Ingresos totales = presupuesto asignado a las fuentes + otros ingresos (OIE tipo 1)
CREATE OR REPLACE VIEW `vista_total_ingresos` AS
SELECT
  COALESCE((SELECT SUM(`presupuesto`) FROM `fuente_financiamiento`), 0)
  + COALESCE((
      SELECT SUM(oc.`monto`)
      FROM `otros_ingresos_egresos` oie
      JOIN `oie_comprobante` oc ON oc.`id` = oie.`oie_comprobante_id`
      WHERE oie.`oie_tipo_id` = 1
    ), 0) AS `total_ingresos`;

-- Egresos totales = otros egresos (OIE tipo 2) + gastos rendidos (rendicion)
CREATE OR REPLACE VIEW `vista_total_egresos` AS
SELECT
  COALESCE((
      SELECT SUM(oc.`monto`)
      FROM `otros_ingresos_egresos` oie
      JOIN `oie_comprobante` oc ON oc.`id` = oie.`oie_comprobante_id`
      WHERE oie.`oie_tipo_id` = 2
    ), 0)
  + COALESCE((SELECT SUM(`monto`) FROM `rendicion`), 0) AS `total_egresos`;

-- Saldo contable global = ingresos totales − egresos totales
CREATE OR REPLACE VIEW `vista_saldo_contable` AS
SELECT
  (SELECT `total_ingresos` FROM `vista_total_ingresos`)
  - (SELECT `total_egresos` FROM `vista_total_egresos`) AS `saldo_contable`;

-- Saldo por fuente = presupuesto de la fuente + ingresos OIE − egresos OIE − rendiciones,
-- todo imputado por ff_id.
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
