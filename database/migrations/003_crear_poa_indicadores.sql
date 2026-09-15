-- 003 · Grupo 11 — POA Indicadores como documento
-- Registro propio con estados: 0=Borrador, 1=Enviado, 2=Observado, 3=Aprobado.
-- La jerarquía (resultado/producto/actividad) es COMPARTIDA con el POA
-- Presupuestal, por eso aquí solo se modela el documento y su flujo.

CREATE TABLE `poa_indicadores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programa_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `anio` char(4) NOT NULL,
  `estado` int(11) NOT NULL DEFAULT 0,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_poa_indicadores_programa_idx` (`programa_id`),
  KEY `fk_poa_indicadores_usuario_idx` (`usuario_id`),
  CONSTRAINT `fk_poa_indicadores_programa` FOREIGN KEY (`programa_id`) REFERENCES `programa` (`id`),
  CONSTRAINT `fk_poa_indicadores_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('003');
