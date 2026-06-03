-- 012 · Seguridad A3 — Recuperación de contraseña segura
-- Portada desde db/migracion_recuperacion_segura.sql (rama seguridad/hardening-y-despliegue-local).
-- 1) reset_token se guarda HASHEADO (sha256 = 64 hex) y caduca (reset_token_expira).
--    El código en claro solo viaja al correo del usuario.
-- 2) Tabla recuperacion_intentos para rate-limit de /chgpsswd y /token_verify (por IP y email).
--
-- El ALTER de `usuario` corre UNA sola vez (el runner registra la versión y no la repite;
-- no usa IF NOT EXISTS por portabilidad MySQL/MariaDB).

ALTER TABLE `usuario`
  MODIFY `reset_token` varchar(64) DEFAULT NULL,
  ADD COLUMN `reset_token_expira` datetime DEFAULT NULL AFTER `reset_token`;

CREATE TABLE IF NOT EXISTS `recuperacion_intentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `tipo` varchar(20) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_tipo_fecha` (`ip`, `tipo`, `fecha`),
  KEY `idx_email_tipo_fecha` (`email`, `tipo`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('012');
