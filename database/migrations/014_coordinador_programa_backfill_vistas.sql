-- 014 · Grupo 8 (Fase 1 código) — Migrar el vínculo coordinador-programa a
-- `coordinador_programa` y rehacer las vistas que lo deducían desde `poa`.
--
-- Contexto: históricamente el vínculo coordinador↔programa se materializaba
-- creando una fila en `poa` (usuario_id + programa_id) al asignar el programa.
-- La tabla `coordinador_programa` (migración 001) es ahora la fuente de verdad.
--
-- 1) Backfill: por cada coordinador (cargo 3) con poa(s), crear su vínculo activo.
--    DISTINCT colapsa años repetidos del mismo programa. En datos sanos un
--    coordinador tiene un solo programa, así que queda un único vínculo activo.
INSERT INTO `coordinador_programa` (`usuario_id`, `programa_id`, `activo`, `fecha`)
SELECT DISTINCT `po`.`usuario_id`, `po`.`programa_id`, 1, NOW()
FROM `poa` `po`
  JOIN `usuario` `u` ON `po`.`usuario_id` = `u`.`id`
WHERE `u`.`cargo_id` = 3
  AND NOT EXISTS (
    SELECT 1 FROM `coordinador_programa` `cp`
    WHERE `cp`.`usuario_id` = `po`.`usuario_id`
      AND `cp`.`programa_id` = `po`.`programa_id`
  );

-- 2) Programas sin coordinador = sin vínculo ACTIVO en coordinador_programa.
CREATE OR REPLACE VIEW `programas_sin_coordinador_vista` AS
SELECT
  `p`.`id`     AS `programa_id`,
  `p`.`codigo` AS `programa_codigo`,
  `p`.`nombre` AS `programa_nombre`
FROM `programa` `p`
WHERE `p`.`id` NOT IN (
  SELECT `cp`.`programa_id` FROM `coordinador_programa` `cp` WHERE `cp`.`activo` = 1
);

-- 3) Coordinadores disponibles = cargo 3 sin vínculo ACTIVO.
CREATE OR REPLACE VIEW `usuario_id_disponible_programa_vista` AS
SELECT
  `u`.`id`          AS `id`,
  `u`.`persona_id`  AS `persona_id`,
  `u`.`cargo_id`    AS `cargo_id`,
  `u`.`descripcion` AS `codigo`
FROM `usuario` `u`
WHERE `u`.`cargo_id` = 3
  AND `u`.`id` NOT IN (
    SELECT `cp`.`usuario_id` FROM `coordinador_programa` `cp` WHERE `cp`.`activo` = 1
  );

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('014');
