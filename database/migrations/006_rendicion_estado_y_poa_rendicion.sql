-- 006 · Grupo 9/12 — Vincular rendición a su POA Rendición + estado
-- estado: 0=Borrador/registrada, se bloquea al aprobar el POA Rendición.
-- poa_rendicion_id NULL: una rendición puede existir antes de adjuntarse al POA.
-- (RUC + razon_social ya existen en `rendicion`; Grupo 12 no requiere DDL.)
-- Depende de 005.

ALTER TABLE `rendicion`
  ADD COLUMN `estado` int(11) NOT NULL DEFAULT 0 AFTER `monto`,
  ADD COLUMN `poa_rendicion_id` int(11) DEFAULT NULL AFTER `estado`,
  ADD KEY `fk_rendicion_poa_rendicion_idx` (`poa_rendicion_id`),
  ADD CONSTRAINT `fk_rendicion_poa_rendicion` FOREIGN KEY (`poa_rendicion_id`) REFERENCES `poa_rendicion` (`id`);

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('006');
