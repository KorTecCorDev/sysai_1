-- 011 · Seguridad C2 — Rate-limit de login persistente en BD
-- Portada desde db/migracion_rate_limit_login.sql (rama seguridad/hardening-y-despliegue-local).
-- El lockout por intentos vivía solo en $_SESSION (evadible sin cookies). Esta tabla
-- registra los intentos FALLIDOS por IP y por email (5 intentos / 5 min en cada dimensión).
-- Solo se insertan fallos; al autenticar con éxito la app los limpia y purga los antiguos.
-- CREATE TABLE IF NOT EXISTS es idempotente.

CREATE TABLE IF NOT EXISTS `login_intentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_fecha` (`ip`, `fecha`),
  KEY `idx_email_fecha` (`email`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('011');
