-- ============================================================================
-- SysAI — Esquema de base de datos (estructura: tablas + vistas, SIN datos)
-- Reconstruido desde el dump del 2025-03-26 (MySQL 8).
-- Cambios respecto al dump original:
--   * Sin INSERTs (los datos de prueba están en db/seed.sql).
--   * Vistas sin `DEFINER=root@localhost` (usan CREATE OR REPLACE → portable).
--   * Sin valores AUTO_INCREMENT iniciales (las tablas arrancan en 1).
-- Uso:  mysql -u <user> -p <bd> < db/schema.sql
--       (o importar en phpMyAdmin)
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- TABLAS
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS `cargo`;
CREATE TABLE `cargo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(350) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `tipo_programa`;
CREATE TABLE `tipo_programa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
-- NOTA: el dump original usaba utf8mb4_0900_ai_ci (solo MySQL 8). Unificado a
--       utf8mb3 como el resto de tablas para portabilidad (MySQL 8 / MariaDB).

DROP TABLE IF EXISTS `programa`;
CREATE TABLE `programa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `tipo_programa_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `persona`;
CREATE TABLE `persona` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nro_documento` int NOT NULL,
  `apellido_paterno` varchar(500) NOT NULL,
  `apellido_materno` varchar(500) NOT NULL,
  `nombres` varchar(500) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nro_documento_UNIQUE` (`nro_documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE `usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `persona_id` int NOT NULL,
  `cargo_id` int NOT NULL,
  `descripcion` varchar(8) NOT NULL,
  `email` varchar(500) NOT NULL,
  `password` char(60) NOT NULL,
  `reset_token` varchar(20) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `persona_id_UNIQUE` (`persona_id`),
  UNIQUE KEY `descripcion_UNIQUE` (`descripcion`),
  KEY `fk_usuario_cargo1_idx` (`cargo_id`),
  KEY `fk_usuario_persona1_idx` (`persona_id`),
  CONSTRAINT `fk_usuario_cargo1` FOREIGN KEY (`cargo_id`) REFERENCES `cargo` (`id`),
  CONSTRAINT `fk_usuario_persona1` FOREIGN KEY (`persona_id`) REFERENCES `persona` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `poa`;
CREATE TABLE `poa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `programa_id` int NOT NULL,
  `anio` char(4) NOT NULL,
  `presupuesto` decimal(7,2) NOT NULL,
  `estado` int NOT NULL,
  `fecha` datetime NOT NULL,
  `usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_programa_usuario` (`usuario_id`),
  CONSTRAINT `fk_programa_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `fuente_financiamiento`;
CREATE TABLE `fuente_financiamiento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `presupuesto` decimal(8,2) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `detalle_financiamiento`;
CREATE TABLE `detalle_financiamiento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `programa_id` int NOT NULL,
  `fuente_financiamiento_id` int NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_programa_has_fuente_financiamiento_programa1_idx` (`programa_id`),
  KEY `fk_detalle_financiamiento_fuente_financiamiento1_idx` (`fuente_financiamiento_id`),
  CONSTRAINT `fk_detalle_financiamiento_fuente_financiamiento1` FOREIGN KEY (`fuente_financiamiento_id`) REFERENCES `fuente_financiamiento` (`id`),
  CONSTRAINT `fk_programa_has_fuente_financiamiento_programa1` FOREIGN KEY (`programa_id`) REFERENCES `programa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `resultado`;
CREATE TABLE `resultado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `programa_id` int NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_resultado_programa1_idx` (`programa_id`),
  CONSTRAINT `fk_resultado_programa1` FOREIGN KEY (`programa_id`) REFERENCES `programa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `resultado_detalle`;
CREATE TABLE `resultado_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resultado_id` int NOT NULL,
  `indicador_medido` int DEFAULT NULL,
  `medio_verificacion` varchar(500) DEFAULT NULL,
  `supuesto` varchar(500) DEFAULT NULL,
  `responsable` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_resultado_resultado1_idx` (`resultado_id`),
  CONSTRAINT `fk_detalle_resultado_resultado1` FOREIGN KEY (`resultado_id`) REFERENCES `resultado` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `indicador_resultado`;
CREATE TABLE `indicador_resultado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resultado_detalle_id` int NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_indicador_resultado_detalle_resultado1_idx` (`resultado_detalle_id`),
  CONSTRAINT `fk_indicador_resultado_detalle_resultado1` FOREIGN KEY (`resultado_detalle_id`) REFERENCES `resultado_detalle` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `avance_resultado`;
CREATE TABLE `avance_resultado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resultado_detalle_id` int NOT NULL,
  `cumplimiento_indicador` varchar(500) DEFAULT NULL,
  `avance` decimal(5,2) DEFAULT NULL,
  `comentarios` varchar(500) DEFAULT NULL,
  `no_programados` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_avance_resultado_resultado_detalle1_idx` (`resultado_detalle_id`),
  CONSTRAINT `fk_avance_resultado_resultado_detalle1` FOREIGN KEY (`resultado_detalle_id`) REFERENCES `resultado_detalle` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
-- NOTA: `avance` corregido a decimal(5,2) (el dump tenía decimal(2,2), máx 0.99 → no admitía 100%).

DROP TABLE IF EXISTS `producto`;
CREATE TABLE `producto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resultado_id` int NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_producto_resultado1_idx` (`resultado_id`),
  CONSTRAINT `fk_producto_resultado1` FOREIGN KEY (`resultado_id`) REFERENCES `resultado` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `producto_detalle`;
CREATE TABLE `producto_detalle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `indicador_medido` int DEFAULT NULL,
  `medio_verificacion` varchar(500) DEFAULT NULL,
  `supuesto` varchar(500) DEFAULT NULL,
  `responsable` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_producto_producto1_idx` (`producto_id`),
  CONSTRAINT `fk_detalle_producto_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `indicador_producto`;
CREATE TABLE `indicador_producto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_detalle_id` int NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_indicador_producto_producto_detalle1_idx` (`producto_detalle_id`),
  CONSTRAINT `fk_indicador_producto_producto_detalle1` FOREIGN KEY (`producto_detalle_id`) REFERENCES `producto_detalle` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `avance_producto`;
CREATE TABLE `avance_producto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_detalle_id` int NOT NULL,
  `indicador_cumplimiento` varchar(500) DEFAULT NULL,
  `avance` decimal(7,2) DEFAULT NULL,
  `comentarios` varchar(500) DEFAULT NULL,
  `no_programados` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_avance_producto_producto_detalle1_idx` (`producto_detalle_id`),
  CONSTRAINT `fk_avance_producto_producto_detalle1` FOREIGN KEY (`producto_detalle_id`) REFERENCES `producto_detalle` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `actividad`;
CREATE TABLE `actividad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_actividad_producto1_idx` (`producto_id`),
  CONSTRAINT `fk_actividad_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `detalle_actividad`;
CREATE TABLE `detalle_actividad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actividad_id` int NOT NULL,
  `indicador_medido` int DEFAULT NULL,
  `medio_verificacion` varchar(500) DEFAULT NULL,
  `supuesto` varchar(500) DEFAULT NULL,
  `responsable` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_actividad_actividad1_idx` (`actividad_id`),
  CONSTRAINT `fk_detalle_actividad_actividad1` FOREIGN KEY (`actividad_id`) REFERENCES `actividad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `indicador_actividad`;
CREATE TABLE `indicador_actividad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `detalle_actividad_id` int NOT NULL,
  `nombre` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_indicador_actividad_detalle_actividad1_idx` (`detalle_actividad_id`),
  CONSTRAINT `fk_indicador_actividad_detalle_actividad1` FOREIGN KEY (`detalle_actividad_id`) REFERENCES `detalle_actividad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `avance_actividad`;
CREATE TABLE `avance_actividad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actividad_id` int NOT NULL,
  `indicador_cumplimiento` varchar(500) DEFAULT NULL,
  `avance` decimal(5,2) DEFAULT NULL,
  `comentarios` varchar(500) DEFAULT NULL,
  `no_programados` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_avance_actividad_actividad1_idx` (`actividad_id`),
  CONSTRAINT `fk_avance_actividad_actividad1` FOREIGN KEY (`actividad_id`) REFERENCES `actividad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
-- NOTA: `avance` corregido a decimal(5,2) (el dump tenía decimal(2,2)).

DROP TABLE IF EXISTS `subcategoria_rubro`;
CREATE TABLE `subcategoria_rubro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `categoria_rubro`;
CREATE TABLE `categoria_rubro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subcategoria_rubro_id` int NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_categoria_rubro_subcategoria_rubro1_idx` (`subcategoria_rubro_id`),
  CONSTRAINT `fk_categoria_rubro_subcategoria_rubro1` FOREIGN KEY (`subcategoria_rubro_id`) REFERENCES `subcategoria_rubro` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `tipo_rubro`;
CREATE TABLE `tipo_rubro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `rubro`;
CREATE TABLE `rubro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actividad_id` int NOT NULL,
  `categoria_rubro_id` int NOT NULL,
  `tipo_rubro_id` int NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_rubro_tipo_rubro1_idx` (`tipo_rubro_id`),
  KEY `fk_rubro_categoria_rubro1_idx` (`categoria_rubro_id`),
  KEY `fk_rubro_actividad1_idx` (`actividad_id`),
  CONSTRAINT `fk_rubro_actividad1` FOREIGN KEY (`actividad_id`) REFERENCES `actividad` (`id`),
  CONSTRAINT `fk_rubro_categoria_rubro1` FOREIGN KEY (`categoria_rubro_id`) REFERENCES `categoria_rubro` (`id`),
  CONSTRAINT `fk_rubro_tipo_rubro1` FOREIGN KEY (`tipo_rubro_id`) REFERENCES `tipo_rubro` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
-- NOTA: `monto` ampliado a decimal(12,2) (el dump tenía decimal(7,2), máx 99 999.99).

DROP TABLE IF EXISTS `tipo_comprobante`;
CREATE TABLE `tipo_comprobante` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `descripcion` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `rendicion`;
CREATE TABLE `rendicion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actividad_id` int NOT NULL,
  `tipo_comprobante_id` int NOT NULL,
  `ff_id` int NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `serie` char(10) NOT NULL,
  `numero` char(20) NOT NULL,
  `detalle` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `ruc` varchar(20) NOT NULL,
  `razon_social` varchar(500) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha_original` date NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_rendicion_tipo_comprobante1_idx` (`tipo_comprobante_id`),
  KEY `fk_rendicion_actividad1_idx` (`actividad_id`),
  KEY `fk_rendicion_fuente_financiamiento_idx` (`ff_id`),
  CONSTRAINT `fk_rendicion_actividad1` FOREIGN KEY (`actividad_id`) REFERENCES `actividad` (`id`),
  CONSTRAINT `fk_rendicion_tipo_comprobante1` FOREIGN KEY (`tipo_comprobante_id`) REFERENCES `tipo_comprobante` (`id`),
  CONSTRAINT `fk_rendicion_fuente_financiamiento` FOREIGN KEY (`ff_id`) REFERENCES `fuente_financiamiento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
-- NOTAS: `monto` a decimal(12,2); `fecha_original` a DATE (el dump tenía varchar(500));
--        añadida FK `ff_id` → fuente_financiamiento (faltaba en el dump).

DROP TABLE IF EXISTS `oie_tipo`;
CREATE TABLE `oie_tipo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `oie_tipo_comprobante`;
CREATE TABLE `oie_tipo_comprobante` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `oie_comprobante`;
CREATE TABLE `oie_comprobante` (
  `id` int NOT NULL AUTO_INCREMENT,
  `oie_tipo_comprobante_id` int NOT NULL,
  `serie` char(10) NOT NULL,
  `numero` char(20) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `ruc` varchar(20) NOT NULL,
  `razon_social` varchar(500) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha_original` date NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_oie_comprobante_oie_tipo_comprobante1_idx` (`oie_tipo_comprobante_id`),
  CONSTRAINT `fk_oie_comprobante_oie_tipo_comprobante1` FOREIGN KEY (`oie_tipo_comprobante_id`) REFERENCES `oie_tipo_comprobante` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
-- NOTA: `monto` ampliado a decimal(12,2).

DROP TABLE IF EXISTS `otros_ingresos_egresos`;
CREATE TABLE `otros_ingresos_egresos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `poa_id` int NOT NULL,
  `oie_comprobante_id` int NOT NULL,
  `oie_tipo_id` int NOT NULL,
  `ff_id` int NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_otros_ingresos_egresos_poa1_idx` (`poa_id`),
  KEY `fk_otros_ingresos_egresos_oie_comprobante1_idx` (`oie_comprobante_id`),
  KEY `fk_otros_ingresos_egresos_oie_tipo1_idx` (`oie_tipo_id`),
  KEY `fk_otros_ingresos_egresos_ff_idx` (`ff_id`),
  CONSTRAINT `fk_otros_ingresos_egresos_oie_comprobante1` FOREIGN KEY (`oie_comprobante_id`) REFERENCES `oie_comprobante` (`id`),
  CONSTRAINT `fk_otros_ingresos_egresos_oie_tipo1` FOREIGN KEY (`oie_tipo_id`) REFERENCES `oie_tipo` (`id`),
  CONSTRAINT `fk_otros_ingresos_egresos_poa1` FOREIGN KEY (`poa_id`) REFERENCES `poa` (`id`),
  CONSTRAINT `fk_otros_ingresos_egresos_ff` FOREIGN KEY (`ff_id`) REFERENCES `fuente_financiamiento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
-- NOTA: añadida FK `ff_id` → fuente_financiamiento (faltaba en el dump).

DROP TABLE IF EXISTS `tipo_cambio_dolar`;
CREATE TABLE `tipo_cambio_dolar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `tipo_cambio` decimal(7,2) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tipo_cambio_dolar_usuario1_idx` (`usuario_id`),
  CONSTRAINT `fk_tipo_cambio_dolar_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `tipo_cambio_euro`;
CREATE TABLE `tipo_cambio_euro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `tipo_cambio` decimal(7,2) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tipo_cambio_euro_usuario1_idx` (`usuario_id`),
  CONSTRAINT `fk_tipo_cambio_euro_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario` varchar(8) NOT NULL,
  `tabla` varchar(50) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha_original` datetime NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
-- NOTA: `id` ahora AUTO_INCREMENT (el dump no lo tenía) y `monto` a decimal(12,2).

-- ----------------------------------------------------------------------------
-- VISTAS  (definidas tras las tablas; sin DEFINER explícito → portables)
-- ----------------------------------------------------------------------------

CREATE OR REPLACE VIEW `login_session_vista` AS
  select `u`.`id` AS `id`,`c`.`id` AS `cargo_id`,coalesce(`o`.`id`,NULL) AS `poa_id`,`u`.`email` AS `email`,`u`.`password` AS `password`,`u`.`reset_token` AS `reset_token`,concat(substring_index(`p`.`nombres`,' ',1),' ',`p`.`apellido_paterno`) AS `datos`,`c`.`descripcion` AS `cargo`,coalesce(`r`.`id`,NULL) AS `programa_id`
  from ((((`usuario` `u` join `persona` `p` on((`u`.`persona_id` = `p`.`id`))) join `cargo` `c` on((`u`.`cargo_id` = `c`.`id`))) left join `poa` `o` on((`u`.`id` = `o`.`usuario_id`))) left join `programa` `r` on((`o`.`programa_id` = `r`.`id`)));

CREATE OR REPLACE VIEW `usuario_admin_vista` AS
  select `u`.`id` AS `id`,`u`.`descripcion` AS `descripcion`,concat(`p`.`apellido_paterno`,' ',`p`.`apellido_materno`,' ',`p`.`nombres`) AS `datos`,`u`.`email` AS `email`,`p`.`telefono` AS `telefono`,`c`.`descripcion` AS `cargo`
  from ((`usuario` `u` join `persona` `p`) join `cargo` `c`) where ((`u`.`persona_id` = `p`.`id`) and (`u`.`cargo_id` = `c`.`id`)) order by `u`.`id`;

CREATE OR REPLACE VIEW `usuario_id_disponible_programa_vista` AS
  select `u`.`id` AS `id`,`u`.`persona_id` AS `persona_id`,`u`.`cargo_id` AS `cargo_id`,`u`.`descripcion` AS `codigo`
  from `usuario` `u` where ((`u`.`cargo_id` = 3) and (`u`.`id` in (select `poa`.`usuario_id` from `poa`)) is false);

CREATE OR REPLACE VIEW `usuarios_coordinador_vista` AS
  select `u`.`id` AS `usuario_id`,`p`.`id` AS `persona_id`,`u`.`cargo_id` AS `cargo_id`,`u`.`descripcion` AS `usuario_codigo`,concat(`p`.`apellido_paterno`,' ',`p`.`apellido_materno`,' ',`p`.`nombres`) AS `nombres`
  from (`usuario` `u` join `persona` `p`) where ((`u`.`cargo_id` = 3) and (`p`.`id` = `u`.`persona_id`));

CREATE OR REPLACE VIEW `programa_poa_vista` AS
  select `p`.`id` AS `poa_id`,`r`.`id` AS `programa_id`,`r`.`codigo` AS `programa_codigo`,`r`.`nombre` AS `programa_nombre`,`p`.`anio` AS `poa_anio`
  from (`poa` `p` join `programa` `r` on((`p`.`programa_id` = `r`.`id`)));

CREATE OR REPLACE VIEW `programas_sin_coordinador_vista` AS
  select `p`.`id` AS `programa_id`,`p`.`codigo` AS `programa_codigo`,`p`.`nombre` AS `programa_nombre`
  from (`programa` `p` left join (`usuario` `u` join `poa` `o` on((`u`.`id` = `o`.`usuario_id`))) on((`o`.`programa_id` = `p`.`id`))) where (`u`.`id` is null);

CREATE OR REPLACE VIEW `fuente_por_actividad_vista` AS
  select `f`.`id` AS `fuente_id`,`a`.`id` AS `actividad_id`,`f`.`nombre` AS `fuente_nombre`,`f`.`presupuesto` AS `fuente_presupuesto`
  from ((((((`fuente_financiamiento` `f` join `detalle_financiamiento` `d` on((`f`.`id` = `d`.`fuente_financiamiento_id`))) join `programa` `p` on((`d`.`programa_id` = `p`.`id`))) join `poa` `i` on((`p`.`id` = `i`.`programa_id`))) join `resultado` `r` on((`p`.`id` = `r`.`programa_id`))) join `producto` `o` on((`r`.`id` = `o`.`resultado_id`))) join `actividad` `a` on((`o`.`id` = `a`.`producto_id`)));

CREATE OR REPLACE VIEW `fuentes_por_poa_id` AS
  select `p`.`id` AS `poa_id`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`codigo` AS `fuente_financiamiento_codigo`,`f`.`nombre` AS `fuente_financiamiento_nombre`
  from (((`poa` `p` join `programa` `r` on((`p`.`programa_id` = `r`.`id`))) join `detalle_financiamiento` `d` on((`r`.`id` = `d`.`programa_id`))) join `fuente_financiamiento` `f` on((`d`.`fuente_financiamiento_id` = `f`.`id`)));

CREATE OR REPLACE VIEW `vista_fuentes_financiamiento_por_actividad` AS
  select distinct `a`.`id` AS `actividad_id`,`a`.`codigo` AS `actividad_codigo`,`a`.`nombre` AS `actividad_nombre`,`ff`.`id` AS `fuente_financiamiento_id`,`ff`.`codigo` AS `fuente_financiamiento_codigo`,`ff`.`nombre` AS `fuente_financiamiento_nombre`,`ff`.`presupuesto` AS `presupuesto_fuente_financiamiento`,`p`.`codigo` AS `programa_codigo`,`p`.`nombre` AS `programa_nombre`
  from (((((`actividad` `a` join `producto` `pr` on((`a`.`producto_id` = `pr`.`id`))) join `resultado` `r` on((`pr`.`resultado_id` = `r`.`id`))) join `programa` `p` on((`r`.`programa_id` = `p`.`id`))) join `detalle_financiamiento` `df` on((`p`.`id` = `df`.`programa_id`))) join `fuente_financiamiento` `ff` on((`df`.`fuente_financiamiento_id` = `ff`.`id`))) order by `a`.`id`;

CREATE OR REPLACE VIEW `rendicion_admin` AS
  select `r`.`id` AS `id`,`a`.`producto_id` AS `producto_id`,`r`.`actividad_id` AS `actividad_id`,`r`.`codigo` AS `codigo`,`t`.`descripcion` AS `tipo_comprobante`,`r`.`serie` AS `serie`,`r`.`numero` AS `numero`,`r`.`detalle` AS `detalle`,`r`.`ruc` AS `ruc`,`r`.`razon_social` AS `razon_social`,`r`.`monto` AS `monto`,`r`.`ff_id` AS `fuente_financiamiento_id`,`f`.`nombre` AS `fuente_financiamiento`,`r`.`fecha_original` AS `fecha_comprobante`
  from (((`rendicion` `r` join `tipo_comprobante` `t`) join `actividad` `a`) join `fuente_financiamiento` `f`) where ((`t`.`id` = `r`.`tipo_comprobante_id`) and (`a`.`id` = `r`.`actividad_id`) and (`f`.`id` = `r`.`ff_id`));

CREATE OR REPLACE VIEW `rubro_admin_vista` AS
  select `r`.`id` AS `id`,`a`.`id` AS `actividad_id`,`c`.`codigo` AS `categoria_rubro`,`s`.`codigo` AS `subcategoria_rubro`,`t`.`codigo` AS `tipo_rubro`,`r`.`codigo` AS `codigo`,`r`.`nombre` AS `nombre`,`r`.`descripcion` AS `descripcion`,`r`.`monto` AS `monto`
  from ((((`rubro` `r` join `actividad` `a`) join `categoria_rubro` `c`) join `tipo_rubro` `t`) join `subcategoria_rubro` `s`) where ((`r`.`actividad_id` = `a`.`id`) and (`r`.`categoria_rubro_id` = `c`.`id`) and (`r`.`tipo_rubro_id` = `t`.`id`) and (`c`.`subcategoria_rubro_id` = `s`.`id`)) order by `r`.`id`;

CREATE OR REPLACE VIEW `tipo_rubro_vista` AS
  select `tipo_rubro`.`id` AS `id`,`tipo_rubro`.`nombre` AS `nombre` from `tipo_rubro`;

CREATE OR REPLACE VIEW `otros_ingresos_egresos_admin_vista` AS
  select `o`.`id` AS `id`,`o`.`codigo` AS `codigo`,`p`.`nombre` AS `tipo`,`t`.`codigo` AS `tipo_comprobante_codigo`,`i`.`monto` AS `comprobante_monto`,`i`.`fecha_original` AS `comprobante_fecha`
  from ((((`otros_ingresos_egresos` `o` join `oie_comprobante` `i` on((`o`.`oie_comprobante_id` = `i`.`id`))) join `oie_tipo_comprobante` `t` on((`i`.`oie_tipo_comprobante_id` = `t`.`id`))) join `oie_tipo` `p` on((`o`.`oie_tipo_id` = `p`.`id`))) join `poa` `a` on((`o`.`poa_id` = `a`.`id`))) order by `o`.`id` desc;

CREATE OR REPLACE VIEW `reporte_egresos` AS
  select distinct `o`.`id` AS `otros_ingresos_egresos_id`,cast(`o`.`fecha` as date) AS `otros_ingresos_egresos_fecha`,`o`.`codigo` AS `otros_ingresos_egresos_codigo`,`o`.`descripcion` AS `otros_ingresos_egresos_descripcion`,`p`.`id` AS `oie_tipo_comprobante_id`,`p`.`codigo` AS `oie_tipo_comprobante_codigo`,`o`.`oie_tipo_id` AS `otros_ingresos_egresos_oie_tipo_id`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`codigo` AS `fuente_financiamiento_codigo`,`c`.`fecha_original` AS `oie_comprobante_fecha_original`,`c`.`ruc` AS `oie_comprobante_ruc`,`c`.`razon_social` AS `oie_comprobante_razon_social`,`c`.`serie` AS `oie_comprobante_serie`,`c`.`numero` AS `oie_comprobante_numero`,`c`.`descripcion` AS `oie_comprobante_descripcion`,`c`.`monto` AS `oie_comprobante_monto`
  from ((((((`otros_ingresos_egresos` `o` left join `oie_comprobante` `c` on((`o`.`oie_comprobante_id` = `c`.`id`))) join `oie_tipo_comprobante` `p` on((`p`.`id` = `c`.`oie_tipo_comprobante_id`))) join `detalle_financiamiento` `d` on((`d`.`fuente_financiamiento_id` = `o`.`ff_id`))) join `fuente_financiamiento` `f` on((`f`.`id` = `d`.`fuente_financiamiento_id`))) join `programa` `g` on((`g`.`id` = `d`.`programa_id`))) join `oie_tipo` `t` on((`t`.`id` = `o`.`oie_tipo_id`))) where (`o`.`oie_tipo_id` = 2);

CREATE OR REPLACE VIEW `reporte_ingresos` AS
  select distinct `o`.`id` AS `otros_ingresos_egresos_id`,`o`.`fecha` AS `otros_ingresos_egresos_fecha`,`o`.`codigo` AS `otros_ingresos_egresos_codigo`,`o`.`descripcion` AS `otros_ingresos_egresos_descripcion`,`o`.`oie_tipo_id` AS `otros_ingresos_egresos_oie_tipo_id`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`codigo` AS `fuente_financiamiento_codigo`,`c`.`fecha_original` AS `oie_comprobante_fecha_original`,`c`.`ruc` AS `oie_comprobante_ruc`,`c`.`razon_social` AS `oie_comprobante_razon_social`,`c`.`serie` AS `oie_comprobante_serie`,`c`.`numero` AS `oie_comprobante_numero`,`c`.`descripcion` AS `oie_comprobante_descripcion`,`c`.`monto` AS `oie_comprobante_monto`
  from (((((`otros_ingresos_egresos` `o` left join `oie_comprobante` `c` on((`o`.`oie_comprobante_id` = `c`.`id`))) join `detalle_financiamiento` `d` on((`d`.`fuente_financiamiento_id` = `o`.`ff_id`))) join `fuente_financiamiento` `f` on((`f`.`id` = `d`.`fuente_financiamiento_id`))) join `programa` `g` on((`g`.`id` = `d`.`programa_id`))) join `oie_tipo` `t` on((`t`.`id` = `o`.`oie_tipo_id`))) where (`o`.`oie_tipo_id` = 1);

CREATE OR REPLACE VIEW `reporte_fuentes` AS
  select cast(`fuente_financiamiento`.`fecha` as date) AS `fecha`,`fuente_financiamiento`.`codigo` AS `codigo`,`fuente_financiamiento`.`nombre` AS `descripcion`,`fuente_financiamiento`.`presupuesto` AS `monto` from `fuente_financiamiento`;

CREATE OR REPLACE VIEW `reporte_fuentes_programa` AS
  select `rendicion`.`id` AS `id`,`rendicion`.`actividad_id` AS `actividad_id`,`rendicion`.`tipo_comprobante_id` AS `tipo_comprobante_id`,`rendicion`.`ff_id` AS `ff_id`,`rendicion`.`codigo` AS `codigo`,`rendicion`.`serie` AS `serie`,`rendicion`.`numero` AS `numero`,`rendicion`.`detalle` AS `detalle`,`rendicion`.`descripcion` AS `descripcion`,`rendicion`.`ruc` AS `ruc`,`rendicion`.`razon_social` AS `razon_social`,`rendicion`.`monto` AS `monto`,`rendicion`.`fecha_original` AS `fecha_original`,`rendicion`.`fecha` AS `fecha` from `rendicion`;

CREATE OR REPLACE VIEW `reporte_fuentes_programa_rendicion` AS
  select `p`.`id` AS `programa_id`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`nombre` AS `fuente_financiamiento_nombre`
  from ((`fuente_financiamiento` `f` join `detalle_financiamiento` `d` on((`f`.`id` = `d`.`fuente_financiamiento_id`))) join `programa` `p` on((`d`.`programa_id` = `p`.`id`))) order by `p`.`id`;

CREATE OR REPLACE VIEW `reporte_poa_rendicion` AS
  select `a`.`id` AS `actividad_id`,`a`.`nombre` AS `actividad_nombre`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`nombre` AS `fuente_financiamiento_nombre`,sum(`r`.`monto`) AS `suma_monto_rendiciones`
  from ((`rendicion` `r` join `actividad` `a` on((`r`.`actividad_id` = `a`.`id`))) join `fuente_financiamiento` `f` on((`r`.`ff_id` = `f`.`id`))) group by `f`.`id`,`f`.`nombre`,`a`.`id`,`a`.`nombre` order by `a`.`id`;

CREATE OR REPLACE VIEW `reporte_poa_rubros` AS
  select `p`.`id` AS `id_programa`,`p`.`nombre` AS `programa`,`u`.`id` AS `id`,`o`.`codigo` AS `producto_codigo`,`o`.`nombre` AS `producto`,`a`.`id` AS `actividad_id`,`a`.`codigo` AS `actividad_codigo`,`a`.`nombre` AS `actividad`,`u`.`nombre` AS `rubros`,`t`.`id` AS `id_tipo_rubro`,`u`.`monto` AS `monto`
  from (((((`programa` `p` join `resultado` `r` on((`p`.`id` = `r`.`programa_id`))) join `producto` `o` on((`r`.`id` = `o`.`resultado_id`))) join `actividad` `a` on((`o`.`id` = `a`.`producto_id`))) join `rubro` `u` on((`a`.`id` = `u`.`actividad_id`))) join `tipo_rubro` `t` on((`t`.`id` = `u`.`tipo_rubro_id`))) order by `a`.`id`;
-- CORRECCIÓN: el dump unía fuente_financiamiento+detalle_financiamiento, lo que
-- duplicaba cada rubro por cada fuente del programa (fan-out). El monto planificado
-- de un rubro NO depende del nº de fuentes → se eliminan esos joins. El `SELECT DISTINCT`
-- original era un parche para esa duplicación.

CREATE OR REPLACE VIEW `reporte_poa_rubros_sumas` AS
  select `p`.`id` AS `id_programa`,`a`.`id` AS `id_actividad`,`a`.`nombre` AS `actividad`,sum(`u`.`monto`) AS `suma_monto`
  from ((((`programa` `p` join `resultado` `r` on((`p`.`id` = `r`.`programa_id`))) join `producto` `o` on((`r`.`id` = `o`.`resultado_id`))) join `actividad` `a` on((`o`.`id` = `a`.`producto_id`))) join `rubro` `u` on((`a`.`id` = `u`.`actividad_id`))) group by `p`.`id`,`a`.`id`,`a`.`nombre` order by `a`.`id`;
-- CORRECCIÓN: mismo fan-out por fuentes. El dump lo "compensaba" con
-- `sum(distinct u.monto)`, que descartaba rubros de igual monto. Solución real:
-- quitar el join a las tablas de financiamiento y usar `sum(u.monto)`.

CREATE OR REPLACE VIEW `reporte_rendiciones` AS
  select `r`.`id` AS `rendicion_id`,cast(`r`.`fecha` as date) AS `rendicion_fecha`,`r`.`codigo` AS `rendicion_codigo`,`r`.`descripcion` AS `rendicion_descripcion`,`t`.`id` AS `rendicion_tipo_comprobante_id`,`t`.`codigo` AS `tipo_comprobante_codigo`,`r`.`monto` AS `rendicion_monto`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`codigo` AS `fuente_financiamiento_codigo`,`r`.`fecha_original` AS `rendicion_fecha_original`,`r`.`ruc` AS `rendicion_ruc`,`r`.`razon_social` AS `rendicion_razon_social`,`r`.`serie` AS `rendicion_serie`,`r`.`numero` AS `rendicion_numero`,`r`.`detalle` AS `rendicion_detalle`,`r`.`monto` AS `rendicion_comprobante_monto`
  from ((((((`rendicion` `r` join `tipo_comprobante` `t` on((`r`.`tipo_comprobante_id` = `t`.`id`))) join `actividad` `a` on((`r`.`actividad_id` = `a`.`id`))) join `producto` `o` on((`a`.`producto_id` = `o`.`id`))) join `resultado` `e` on((`o`.`resultado_id` = `e`.`id`))) join `programa` `g` on((`e`.`programa_id` = `g`.`id`))) join `fuente_financiamiento` `f` on((`f`.`id` = `r`.`ff_id`))) group by `r`.`id`;

CREATE OR REPLACE VIEW `total_monto_rendiciones_por_actividad` AS
  select `r`.`actividad_id` AS `actividad_id`,`a`.`codigo` AS `actividad_codigo`,`a`.`nombre` AS `actividad_nombre`,sum(`r`.`monto`) AS `total_monto_rendiciones`
  from (`rendicion` `r` join `actividad` `a` on((`r`.`actividad_id` = `a`.`id`))) group by `r`.`actividad_id`,`a`.`codigo`,`a`.`nombre` order by `r`.`actividad_id`;

CREATE OR REPLACE VIEW `cantidad_fuentes_rendicion` AS
  select count(distinct `rendicion`.`ff_id`) AS `numero` from `rendicion`;

CREATE OR REPLACE VIEW `vista_dolar` AS
  select `t`.`id` AS `id`,`u`.`descripcion` AS `usuario`,`t`.`tipo_cambio` AS `tipo_cambio`,`t`.`fecha` AS `fecha`
  from (`usuario` `u` join `tipo_cambio_dolar` `t`) where (`u`.`id` = `t`.`usuario_id`);

CREATE OR REPLACE VIEW `vista_euro` AS
  select `t`.`id` AS `id`,`u`.`descripcion` AS `usuario`,`t`.`tipo_cambio` AS `tipo_cambio`,`t`.`fecha` AS `fecha`
  from (`usuario` `u` join `tipo_cambio_euro` `t`) where (`u`.`id` = `t`.`usuario_id`);

SET FOREIGN_KEY_CHECKS = 1;
