-- 015 · POA Indicadores — comentario de observación del Contador
-- Cuando el Contador "Observa" (devuelve) el documento al Coordinador, registra
-- el motivo para que el Coordinador sepa qué subsanar. Cambia la regla previa
-- "retorno sin comentario" (Grupo 11) por "retorno con comentario".

ALTER TABLE `poa_indicadores`
  ADD COLUMN `observacion` VARCHAR(500) DEFAULT NULL AFTER `estado`;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('015');
