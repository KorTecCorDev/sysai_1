-- 001 · Grupo 8 — Vínculo Coordinador-Programa
-- Tabla intermedia con `activo` para gestionar reemplazos.
-- Un coordinador tiene un solo programa activo a la vez (se valida en la app).

CREATE TABLE `coordinador_programa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `programa_id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_coordinador_programa_usuario_idx` (`usuario_id`),
  KEY `fk_coordinador_programa_programa_idx` (`programa_id`),
  CONSTRAINT `fk_coordinador_programa_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  CONSTRAINT `fk_coordinador_programa_programa` FOREIGN KEY (`programa_id`) REFERENCES `programa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('001');
