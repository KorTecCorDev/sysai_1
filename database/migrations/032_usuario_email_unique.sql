-- 032 · Item 10 (Usuarios) — email único a nivel de BD (cierra la deuda B3).
-- El login busca por email con LIMIT 1 y todo el flujo de recuperación de
-- contraseña asume un email = un usuario; la app ya lo pre-valida
-- (Usuario::validar → existeDato) pero sin UNIQUE en BD un duplicado por
-- carrera o carga directa dejaría a un usuario inaccesible en silencio.
-- Verificado: la BD local no tiene emails duplicados (y el despliegue es
-- greenfield), así que el índice entra sin conflicto.

ALTER TABLE `usuario`
  ADD UNIQUE KEY `uq_usuario_email` (`email`);

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('032');
