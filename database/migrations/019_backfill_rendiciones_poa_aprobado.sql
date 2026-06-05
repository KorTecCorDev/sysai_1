-- 019 · Item 6 — backfill: aprobar rendiciones de programas con POA Presupuestal ya APROBADO.
--
-- Tras la 018 el saldo contable solo resta rendiciones con estado=1 (Aprobada). Las
-- rendiciones registradas ANTES de este item quedaron en estado=0 aunque el POA
-- Presupuestal de su programa ya esté Aprobado (estado=3). Este backfill las pone en
-- estado=1 para mantener la invariante "POA aprobado ⇒ sus rendiciones aprobadas/descontadas".
--
-- Idempotente (solo toca filas no aprobadas). En despliegue greenfield (BD vacía) no afecta.

UPDATE `rendicion` r
SET r.`estado` = 1
WHERE r.`estado` <> 1
  AND EXISTS (
    SELECT 1
    FROM `rubro` ru
    JOIN `actividad` a ON a.`id` = ru.`actividad_id`
    JOIN `producto` p ON p.`id` = a.`producto_id`
    JOIN `resultado` re ON re.`id` = p.`resultado_id`
    JOIN `poa` po ON po.`programa_id` = re.`programa_id`
    WHERE ru.`id` = r.`rubro_id` AND po.`estado` = 3
  );

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('019');
