-- 021 — Vista de saldo por SOBRE (programa, fuente).
-- Complemento de vista_saldo_fuente_financiamiento (nivel fuente) tras la migración
-- 020 (sub-presupuestos). Un "sobre" es una fila de detalle_financiamiento con su
-- monto_asignado. Saldo CONTABLE del sobre (mismo criterio que el nivel fuente:
-- solo rendiciones APROBADAS, estado=1):
--   saldo = monto_asignado + ingresos_OIE_al_sobre − egresos_OIE_del_sobre − rendiciones_aprobadas
-- Los ingresos OIE dirigidos al total de la fuente (programa_id = NULL) NO entran en
-- ningún sobre (pertenecen al remanente de la fuente).
-- (El "disponible para comprometer" del tope de gasto —que cuenta rendiciones de todo
--  estado— vive en DetalleFinanciamiento::saldoSobre(); es un concepto distinto.)

CREATE OR REPLACE VIEW `vista_saldo_sobre` AS
SELECT
    s.sobre_id                    AS sobre_id,
    s.programa_id                 AS programa_id,
    s.programa_codigo             AS programa_codigo,
    s.programa_nombre             AS programa_nombre,
    s.fuente_financiamiento_id    AS fuente_financiamiento_id,
    s.fuente_codigo               AS fuente_codigo,
    s.fuente_nombre               AS fuente_nombre,
    s.monto_asignado              AS monto_asignado,
    s.ingresos                    AS ingresos,
    s.egresos                     AS egresos,
    s.rendiciones_aprobadas       AS rendiciones_aprobadas,
    (s.monto_asignado + s.ingresos - s.egresos - s.rendiciones_aprobadas) AS saldo
FROM (
    SELECT
        df.id                        AS sobre_id,
        df.programa_id               AS programa_id,
        pg.codigo                    AS programa_codigo,
        pg.nombre                    AS programa_nombre,
        df.fuente_financiamiento_id  AS fuente_financiamiento_id,
        ff.codigo                    AS fuente_codigo,
        ff.nombre                    AS fuente_nombre,
        df.monto_asignado            AS monto_asignado,
        COALESCE((SELECT SUM(oc.monto)
                  FROM otros_ingresos_egresos oie
                  JOIN oie_comprobante oc ON oc.id = oie.oie_comprobante_id
                  WHERE oie.programa_id = df.programa_id
                    AND oie.ff_id = df.fuente_financiamiento_id
                    AND oie.oie_tipo_id = 1), 0) AS ingresos,
        COALESCE((SELECT SUM(oc.monto)
                  FROM otros_ingresos_egresos oie
                  JOIN oie_comprobante oc ON oc.id = oie.oie_comprobante_id
                  WHERE oie.programa_id = df.programa_id
                    AND oie.ff_id = df.fuente_financiamiento_id
                    AND oie.oie_tipo_id = 2), 0) AS egresos,
        COALESCE((SELECT SUM(r.monto)
                  FROM rendicion r
                  JOIN rubro ru ON ru.id = r.rubro_id
                  JOIN actividad a ON a.id = ru.actividad_id
                  JOIN producto p ON p.id = a.producto_id
                  JOIN resultado re ON re.id = p.resultado_id
                  WHERE re.programa_id = df.programa_id
                    AND r.ff_id = df.fuente_financiamiento_id
                    AND r.estado = 1), 0) AS rendiciones_aprobadas
    FROM detalle_financiamiento df
    JOIN programa pg ON pg.id = df.programa_id
    JOIN fuente_financiamiento ff ON ff.id = df.fuente_financiamiento_id
) s
ORDER BY s.programa_codigo, s.fuente_codigo;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('021');
