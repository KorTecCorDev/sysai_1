-- 017 — Rendiciones imputadas al RUBRO (item 5).
-- Decisión 2026-06-04: la rendición se imputa directamente a un rubro (Bien/Servicio),
-- no a la actividad. La actividad se deriva por rubro -> actividad. Se limpia la tabla
-- (datos de prueba) y se sustituye `actividad_id` por `rubro_id`. Las 5 vistas que
-- dependían de rendicion.actividad_id se reescriben para derivar la actividad desde el
-- rubro, CONSERVANDO sus columnas de salida (incl. `actividad_id`) y agregando `rubro_id`
-- donde es útil, para no romper los listados ni los reportes Excel.

-- 1) Limpieza de datos de prueba (no hay FKs entrantes hacia rendicion).
DELETE FROM `rendicion`;

-- 2) Esquema: rendicion.actividad_id -> rendicion.rubro_id.
ALTER TABLE `rendicion` DROP FOREIGN KEY `fk_rendicion_actividad1`;
ALTER TABLE `rendicion` DROP COLUMN `actividad_id`;
ALTER TABLE `rendicion`
    ADD COLUMN `rubro_id` INT(11) NOT NULL AFTER `id`,
    ADD CONSTRAINT `fk_rendicion_rubro` FOREIGN KEY (`rubro_id`) REFERENCES `rubro` (`id`);

-- 3) Vistas reescritas (derivan actividad desde el rubro; mismas columnas de salida).

CREATE OR REPLACE VIEW `rendicion_admin` AS
SELECT r.id AS id, ru.id AS rubro_id, a.producto_id AS producto_id, a.id AS actividad_id,
       r.codigo AS codigo, t.descripcion AS tipo_comprobante, r.serie AS serie, r.numero AS numero,
       r.detalle AS detalle, r.ruc AS ruc, r.razon_social AS razon_social, r.monto AS monto,
       r.ff_id AS fuente_financiamiento_id, f.nombre AS fuente_financiamiento,
       r.fecha_original AS fecha_comprobante
FROM rendicion r
JOIN rubro ru ON ru.id = r.rubro_id
JOIN actividad a ON a.id = ru.actividad_id
JOIN tipo_comprobante t ON t.id = r.tipo_comprobante_id
JOIN fuente_financiamiento f ON f.id = r.ff_id;

CREATE OR REPLACE VIEW `total_monto_rendiciones_por_actividad` AS
SELECT a.id AS actividad_id, a.codigo AS actividad_codigo, a.nombre AS actividad_nombre,
       SUM(r.monto) AS total_monto_rendiciones
FROM rendicion r
JOIN rubro ru ON ru.id = r.rubro_id
JOIN actividad a ON a.id = ru.actividad_id
GROUP BY a.id, a.codigo, a.nombre
ORDER BY a.id;

CREATE OR REPLACE VIEW `reporte_rendiciones` AS
SELECT r.id AS rendicion_id, CAST(r.fecha AS DATE) AS rendicion_fecha, r.codigo AS rendicion_codigo,
       r.descripcion AS rendicion_descripcion, t.id AS rendicion_tipo_comprobante_id,
       t.codigo AS tipo_comprobante_codigo, r.monto AS rendicion_monto, f.id AS fuente_financiamiento_id,
       f.codigo AS fuente_financiamiento_codigo, r.fecha_original AS rendicion_fecha_original,
       r.ruc AS rendicion_ruc, r.razon_social AS rendicion_razon_social, r.serie AS rendicion_serie,
       r.numero AS rendicion_numero, r.detalle AS rendicion_detalle,
       r.monto AS rendicion_comprobante_monto
FROM rendicion r
JOIN tipo_comprobante t ON r.tipo_comprobante_id = t.id
JOIN rubro ru ON ru.id = r.rubro_id
JOIN actividad a ON a.id = ru.actividad_id
JOIN producto o ON a.producto_id = o.id
JOIN resultado e ON o.resultado_id = e.id
JOIN programa g ON e.programa_id = g.id
JOIN fuente_financiamiento f ON f.id = r.ff_id
GROUP BY r.id;

CREATE OR REPLACE VIEW `reporte_poa_rendicion` AS
SELECT a.id AS actividad_id, a.nombre AS actividad_nombre, f.id AS fuente_financiamiento_id,
       f.nombre AS fuente_financiamiento_nombre, SUM(r.monto) AS suma_monto_rendiciones
FROM rendicion r
JOIN rubro ru ON ru.id = r.rubro_id
JOIN actividad a ON a.id = ru.actividad_id
JOIN fuente_financiamiento f ON r.ff_id = f.id
GROUP BY f.id, f.nombre, a.id, a.nombre
ORDER BY a.id;

CREATE OR REPLACE VIEW `reporte_fuentes_programa` AS
SELECT r.id AS id, ru.actividad_id AS actividad_id, r.rubro_id AS rubro_id,
       r.tipo_comprobante_id AS tipo_comprobante_id, r.ff_id AS ff_id, r.codigo AS codigo,
       r.serie AS serie, r.numero AS numero, r.detalle AS detalle, r.descripcion AS descripcion,
       r.ruc AS ruc, r.razon_social AS razon_social, r.monto AS monto,
       r.fecha_original AS fecha_original, r.fecha AS fecha
FROM rendicion r
JOIN rubro ru ON ru.id = r.rubro_id;
