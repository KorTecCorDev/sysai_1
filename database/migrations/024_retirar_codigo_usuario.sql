-- 024 — Retiro del código de usuario (`usuario.descripcion`).
-- El login es por EMAIL, no hay joins por código y el listado identifica por
-- nombre + email → el código de usuario se elimina. Antes de dropear la columna
-- se recrean las 5 vistas que la referenciaban:
--   * usuario_admin_vista        -> se ELIMINA la columna `descripcion` (ya no se muestra).
--   * usuarios_coordinador_vista -> `usuario_codigo`  pasa a exponer el email.
--   * usuario_id_disponible_programa_vista -> `codigo` pasa a exponer el email.
--   * vista_dolar / vista_euro   -> `usuario` pasa a exponer el email.
-- (Alias conservados salvo en usuario_admin_vista para no tocar los modelos-vista.)
-- La auditoría `SET @usuario_actual` pasa a usar el email (ActiveRecord::setUsuarioActual).

CREATE OR REPLACE VIEW `usuario_admin_vista` AS
  SELECT `u`.`id` AS `id`,
         CONCAT(`p`.`apellido_paterno`, ' ', `p`.`apellido_materno`, ' ', `p`.`nombres`) AS `datos`,
         `u`.`email` AS `email`,
         `p`.`telefono` AS `telefono`,
         `c`.`descripcion` AS `cargo`
  FROM ((`usuario` `u` JOIN `persona` `p`) JOIN `cargo` `c`)
  WHERE `u`.`persona_id` = `p`.`id` AND `u`.`cargo_id` = `c`.`id`
  ORDER BY `u`.`id`;

CREATE OR REPLACE VIEW `usuarios_coordinador_vista` AS
  SELECT `u`.`id` AS `usuario_id`, `p`.`id` AS `persona_id`, `u`.`cargo_id` AS `cargo_id`,
         `u`.`email` AS `usuario_codigo`,
         CONCAT(`p`.`apellido_paterno`, ' ', `p`.`apellido_materno`, ' ', `p`.`nombres`) AS `nombres`
  FROM (`usuario` `u` JOIN `persona` `p`)
  WHERE `u`.`cargo_id` = 3 AND `p`.`id` = `u`.`persona_id`;

CREATE OR REPLACE VIEW `usuario_id_disponible_programa_vista` AS
  SELECT `u`.`id` AS `id`, `u`.`persona_id` AS `persona_id`, `u`.`cargo_id` AS `cargo_id`,
         `u`.`email` AS `codigo`
  FROM `usuario` `u`
  WHERE `u`.`cargo_id` = 3
    AND !(`u`.`id` IN (SELECT `cp`.`usuario_id` FROM `coordinador_programa` `cp` WHERE `cp`.`activo` = 1));

CREATE OR REPLACE VIEW `vista_dolar` AS
  SELECT `t`.`id` AS `id`, `u`.`email` AS `usuario`, `t`.`tipo_cambio` AS `tipo_cambio`, `t`.`fecha` AS `fecha`
  FROM (`usuario` `u` JOIN `tipo_cambio_dolar` `t`)
  WHERE `u`.`id` = `t`.`usuario_id`;

CREATE OR REPLACE VIEW `vista_euro` AS
  SELECT `t`.`id` AS `id`, `u`.`email` AS `usuario`, `t`.`tipo_cambio` AS `tipo_cambio`, `t`.`fecha` AS `fecha`
  FROM (`usuario` `u` JOIN `tipo_cambio_euro` `t`)
  WHERE `u`.`id` = `t`.`usuario_id`;

-- Ya sin dependencias sobre la columna: retirar UNIQUE y columna.
ALTER TABLE `usuario` DROP INDEX `descripcion_UNIQUE`;
ALTER TABLE `usuario` DROP COLUMN `descripcion`;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('024');
