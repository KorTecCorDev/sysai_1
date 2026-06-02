-- ============================================================================
-- Migración C2 — Rate-limit de login persistente en BD.
-- ----------------------------------------------------------------------------
-- El lockout por intentos vivía solo en $_SESSION (evadible si el atacante no
-- envía cookies). Esta tabla registra los intentos FALLIDOS por IP y por email
-- para imponer un límite persistente (5 intentos / 5 min en cada dimensión).
-- Solo se insertan fallos; al autenticar con éxito la app borra los del IP/email
-- y purga oportunísticamente los registros más antiguos que la ventana.
--
-- Para Hostinger: ejecutar sobre la BD de producción (u612374195_sysai).
-- CREATE TABLE IF NOT EXISTS es idempotente.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `login_intentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_fecha` (`ip`, `fecha`),
  KEY `idx_email_fecha` (`email`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
