-- ============================================================================
-- Migración A3 — Recuperación de contraseña segura.
-- ----------------------------------------------------------------------------
-- 1) El reset_token ahora se guarda HASHEADO (sha256 = 64 hex) en vez de texto
--    plano, y caduca (reset_token_expira). El código en claro solo viaja al
--    correo del usuario.
-- 2) Tabla recuperacion_intentos para rate-limit: solicitudes de token
--    (/chgpsswd) y verificaciones de código (/token_verify), por IP y email.
--
-- Para Hostinger: ejecutar sobre la BD de producción (u612374195_sysai).
-- El ALTER de `usuario` se ejecuta UNA sola vez (no usa IF NOT EXISTS por
-- portabilidad MySQL/MariaDB); si ya se aplicó, omitir esas dos líneas.
-- ============================================================================

-- 1) Esquema de usuario: token hasheado (64) + expiración.
ALTER TABLE `usuario`
  MODIFY `reset_token` varchar(64) DEFAULT NULL,
  ADD COLUMN `reset_token_expira` datetime DEFAULT NULL AFTER `reset_token`;

-- 2) Tabla de rate-limit de recuperación (idempotente).
CREATE TABLE IF NOT EXISTS `recuperacion_intentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `tipo` varchar(20) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_tipo_fecha` (`ip`, `tipo`, `fecha`),
  KEY `idx_email_tipo_fecha` (`email`, `tipo`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Nota: los reset_token en texto plano que existieran quedan inservibles (la
-- verificación ahora compara contra el hash). No hay que migrarlos; al solicitar
-- de nuevo la recuperación se generará un token hasheado y con expiración.
