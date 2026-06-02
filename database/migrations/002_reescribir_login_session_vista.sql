-- 002 · Grupo 8 — Rediseño de login_session_vista
-- Antes derivaba programa_id desde el POA del usuario (frágil: un coordinador
-- sin POA quedaba sin programa). Ahora lo deriva desde coordinador_programa
-- (vínculo activo). Depende de 001.

CREATE OR REPLACE VIEW `login_session_vista` AS
SELECT
  `u`.`id`           AS `id`,
  `c`.`id`           AS `cargo_id`,
  COALESCE(`o`.`id`, NULL) AS `poa_id`,
  `u`.`email`        AS `email`,
  `u`.`password`     AS `password`,
  `u`.`reset_token`  AS `reset_token`,
  CONCAT(SUBSTRING_INDEX(`p`.`nombres`, ' ', 1), ' ', `p`.`apellido_paterno`) AS `datos`,
  `c`.`descripcion`  AS `cargo`,
  COALESCE(`cp`.`programa_id`, NULL) AS `programa_id`
FROM `usuario` `u`
  JOIN `persona` `p` ON `u`.`persona_id` = `p`.`id`
  JOIN `cargo` `c` ON `u`.`cargo_id` = `c`.`id`
  LEFT JOIN `coordinador_programa` `cp` ON `cp`.`usuario_id` = `u`.`id` AND `cp`.`activo` = 1
  LEFT JOIN `poa` `o` ON `o`.`usuario_id` = `u`.`id` AND `o`.`programa_id` = `cp`.`programa_id`;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('002');
