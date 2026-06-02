-- 005 · Grupo 9 — POA Rendición como documento
-- Uno por programa por año. Estados: 0=Borrador,1=Enviado,2=Observado,3=Aprobado.
-- Tras aprobado, el Contador puede re-abrirlo (v1.1). Las rendiciones se
-- vinculan a este documento (ver 006).

CREATE TABLE `poa_rendicion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programa_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `anio` char(4) NOT NULL,
  `estado` int(11) NOT NULL DEFAULT 0,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_poa_rendicion_programa_idx` (`programa_id`),
  KEY `fk_poa_rendicion_usuario_idx` (`usuario_id`),
  CONSTRAINT `fk_poa_rendicion_programa` FOREIGN KEY (`programa_id`) REFERENCES `programa` (`id`),
  CONSTRAINT `fk_poa_rendicion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('005');
