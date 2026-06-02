
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `actividad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actividad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_actividad_producto1_idx` (`producto_id`),
  CONSTRAINT `fk_actividad_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(8) NOT NULL,
  `tabla` varchar(50) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha_original` datetime NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `avance_actividad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avance_actividad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actividad_id` int(11) NOT NULL,
  `indicador_cumplimiento` varchar(500) DEFAULT NULL,
  `avance` decimal(5,2) DEFAULT NULL,
  `comentarios` varchar(500) DEFAULT NULL,
  `no_programados` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_avance_actividad_actividad1_idx` (`actividad_id`),
  CONSTRAINT `fk_avance_actividad_actividad1` FOREIGN KEY (`actividad_id`) REFERENCES `actividad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `avance_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avance_producto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_detalle_id` int(11) NOT NULL,
  `indicador_cumplimiento` varchar(500) DEFAULT NULL,
  `avance` decimal(7,2) DEFAULT NULL,
  `comentarios` varchar(500) DEFAULT NULL,
  `no_programados` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_avance_producto_producto_detalle1_idx` (`producto_detalle_id`),
  CONSTRAINT `fk_avance_producto_producto_detalle1` FOREIGN KEY (`producto_detalle_id`) REFERENCES `producto_detalle` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `avance_resultado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avance_resultado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resultado_detalle_id` int(11) NOT NULL,
  `cumplimiento_indicador` varchar(500) DEFAULT NULL,
  `avance` decimal(5,2) DEFAULT NULL,
  `comentarios` varchar(500) DEFAULT NULL,
  `no_programados` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_avance_resultado_resultado_detalle1_idx` (`resultado_detalle_id`),
  CONSTRAINT `fk_avance_resultado_resultado_detalle1` FOREIGN KEY (`resultado_detalle_id`) REFERENCES `resultado_detalle` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cantidad_fuentes_rendicion`;
/*!50001 DROP VIEW IF EXISTS `cantidad_fuentes_rendicion`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `cantidad_fuentes_rendicion` AS SELECT
 1 AS `numero` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `cargo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cargo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(350) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categoria_rubro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categoria_rubro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subcategoria_rubro_id` int(11) NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_categoria_rubro_subcategoria_rubro1_idx` (`subcategoria_rubro_id`),
  CONSTRAINT `fk_categoria_rubro_subcategoria_rubro1` FOREIGN KEY (`subcategoria_rubro_id`) REFERENCES `subcategoria_rubro` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_actividad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_actividad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actividad_id` int(11) NOT NULL,
  `indicador_medido` int(11) DEFAULT NULL,
  `medio_verificacion` varchar(500) DEFAULT NULL,
  `supuesto` varchar(500) DEFAULT NULL,
  `responsable` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_actividad_actividad1_idx` (`actividad_id`),
  CONSTRAINT `fk_detalle_actividad_actividad1` FOREIGN KEY (`actividad_id`) REFERENCES `actividad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_financiamiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_financiamiento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programa_id` int(11) NOT NULL,
  `fuente_financiamiento_id` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_programa_has_fuente_financiamiento_programa1_idx` (`programa_id`),
  KEY `fk_detalle_financiamiento_fuente_financiamiento1_idx` (`fuente_financiamiento_id`),
  CONSTRAINT `fk_detalle_financiamiento_fuente_financiamiento1` FOREIGN KEY (`fuente_financiamiento_id`) REFERENCES `fuente_financiamiento` (`id`),
  CONSTRAINT `fk_programa_has_fuente_financiamiento_programa1` FOREIGN KEY (`programa_id`) REFERENCES `programa` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fuente_financiamiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fuente_financiamiento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `presupuesto` decimal(8,2) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fuente_por_actividad_vista`;
/*!50001 DROP VIEW IF EXISTS `fuente_por_actividad_vista`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `fuente_por_actividad_vista` AS SELECT
 1 AS `fuente_id`,
  1 AS `actividad_id`,
  1 AS `fuente_nombre`,
  1 AS `fuente_presupuesto` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `fuentes_por_poa_id`;
/*!50001 DROP VIEW IF EXISTS `fuentes_por_poa_id`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `fuentes_por_poa_id` AS SELECT
 1 AS `poa_id`,
  1 AS `fuente_financiamiento_id`,
  1 AS `fuente_financiamiento_codigo`,
  1 AS `fuente_financiamiento_nombre` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `indicador_actividad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indicador_actividad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `detalle_actividad_id` int(11) NOT NULL,
  `nombre` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_indicador_actividad_detalle_actividad1_idx` (`detalle_actividad_id`),
  CONSTRAINT `fk_indicador_actividad_detalle_actividad1` FOREIGN KEY (`detalle_actividad_id`) REFERENCES `detalle_actividad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `indicador_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indicador_producto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_detalle_id` int(11) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_indicador_producto_producto_detalle1_idx` (`producto_detalle_id`),
  CONSTRAINT `fk_indicador_producto_producto_detalle1` FOREIGN KEY (`producto_detalle_id`) REFERENCES `producto_detalle` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `indicador_resultado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indicador_resultado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resultado_detalle_id` int(11) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_indicador_resultado_detalle_resultado1_idx` (`resultado_detalle_id`),
  CONSTRAINT `fk_indicador_resultado_detalle_resultado1` FOREIGN KEY (`resultado_detalle_id`) REFERENCES `resultado_detalle` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `login_session_vista`;
/*!50001 DROP VIEW IF EXISTS `login_session_vista`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `login_session_vista` AS SELECT
 1 AS `id`,
  1 AS `cargo_id`,
  1 AS `poa_id`,
  1 AS `email`,
  1 AS `password`,
  1 AS `reset_token`,
  1 AS `datos`,
  1 AS `cargo`,
  1 AS `programa_id` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `oie_comprobante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oie_comprobante` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oie_tipo_comprobante_id` int(11) NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oie_tipo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oie_tipo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oie_tipo_comprobante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oie_tipo_comprobante` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otros_ingresos_egresos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otros_ingresos_egresos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poa_id` int(11) NOT NULL,
  `oie_comprobante_id` int(11) NOT NULL,
  `oie_tipo_id` int(11) NOT NULL,
  `ff_id` int(11) NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_otros_ingresos_egresos_poa1_idx` (`poa_id`),
  KEY `fk_otros_ingresos_egresos_oie_comprobante1_idx` (`oie_comprobante_id`),
  KEY `fk_otros_ingresos_egresos_oie_tipo1_idx` (`oie_tipo_id`),
  KEY `fk_otros_ingresos_egresos_ff_idx` (`ff_id`),
  CONSTRAINT `fk_otros_ingresos_egresos_ff` FOREIGN KEY (`ff_id`) REFERENCES `fuente_financiamiento` (`id`),
  CONSTRAINT `fk_otros_ingresos_egresos_oie_comprobante1` FOREIGN KEY (`oie_comprobante_id`) REFERENCES `oie_comprobante` (`id`),
  CONSTRAINT `fk_otros_ingresos_egresos_oie_tipo1` FOREIGN KEY (`oie_tipo_id`) REFERENCES `oie_tipo` (`id`),
  CONSTRAINT `fk_otros_ingresos_egresos_poa1` FOREIGN KEY (`poa_id`) REFERENCES `poa` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otros_ingresos_egresos_admin_vista`;
/*!50001 DROP VIEW IF EXISTS `otros_ingresos_egresos_admin_vista`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `otros_ingresos_egresos_admin_vista` AS SELECT
 1 AS `id`,
  1 AS `codigo`,
  1 AS `tipo`,
  1 AS `tipo_comprobante_codigo`,
  1 AS `comprobante_monto`,
  1 AS `comprobante_fecha` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `persona`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `persona` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nro_documento` int(11) NOT NULL,
  `apellido_paterno` varchar(500) NOT NULL,
  `apellido_materno` varchar(500) NOT NULL,
  `nombres` varchar(500) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nro_documento_UNIQUE` (`nro_documento`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `poa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programa_id` int(11) NOT NULL,
  `anio` char(4) NOT NULL,
  `presupuesto` decimal(7,2) NOT NULL,
  `estado` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_programa_usuario` (`usuario_id`),
  CONSTRAINT `fk_programa_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resultado_id` int(11) NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_producto_resultado1_idx` (`resultado_id`),
  CONSTRAINT `fk_producto_resultado1` FOREIGN KEY (`resultado_id`) REFERENCES `resultado` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `producto_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producto_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) NOT NULL,
  `indicador_medido` int(11) DEFAULT NULL,
  `medio_verificacion` varchar(500) DEFAULT NULL,
  `supuesto` varchar(500) DEFAULT NULL,
  `responsable` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_producto_producto1_idx` (`producto_id`),
  CONSTRAINT `fk_detalle_producto_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `programa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `tipo_programa_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `programa_poa_vista`;
/*!50001 DROP VIEW IF EXISTS `programa_poa_vista`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `programa_poa_vista` AS SELECT
 1 AS `poa_id`,
  1 AS `programa_id`,
  1 AS `programa_codigo`,
  1 AS `programa_nombre`,
  1 AS `poa_anio` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `programas_sin_coordinador_vista`;
/*!50001 DROP VIEW IF EXISTS `programas_sin_coordinador_vista`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `programas_sin_coordinador_vista` AS SELECT
 1 AS `programa_id`,
  1 AS `programa_codigo`,
  1 AS `programa_nombre` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `rendicion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rendicion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actividad_id` int(11) NOT NULL,
  `tipo_comprobante_id` int(11) NOT NULL,
  `ff_id` int(11) NOT NULL,
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
  CONSTRAINT `fk_rendicion_fuente_financiamiento` FOREIGN KEY (`ff_id`) REFERENCES `fuente_financiamiento` (`id`),
  CONSTRAINT `fk_rendicion_tipo_comprobante1` FOREIGN KEY (`tipo_comprobante_id`) REFERENCES `tipo_comprobante` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rendicion_admin`;
/*!50001 DROP VIEW IF EXISTS `rendicion_admin`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `rendicion_admin` AS SELECT
 1 AS `id`,
  1 AS `producto_id`,
  1 AS `actividad_id`,
  1 AS `codigo`,
  1 AS `tipo_comprobante`,
  1 AS `serie`,
  1 AS `numero`,
  1 AS `detalle`,
  1 AS `ruc`,
  1 AS `razon_social`,
  1 AS `monto`,
  1 AS `fuente_financiamiento_id`,
  1 AS `fuente_financiamiento`,
  1 AS `fecha_comprobante` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `reporte_egresos`;
/*!50001 DROP VIEW IF EXISTS `reporte_egresos`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `reporte_egresos` AS SELECT
 1 AS `otros_ingresos_egresos_id`,
  1 AS `otros_ingresos_egresos_fecha`,
  1 AS `otros_ingresos_egresos_codigo`,
  1 AS `otros_ingresos_egresos_descripcion`,
  1 AS `oie_tipo_comprobante_id`,
  1 AS `oie_tipo_comprobante_codigo`,
  1 AS `otros_ingresos_egresos_oie_tipo_id`,
  1 AS `fuente_financiamiento_id`,
  1 AS `fuente_financiamiento_codigo`,
  1 AS `oie_comprobante_fecha_original`,
  1 AS `oie_comprobante_ruc`,
  1 AS `oie_comprobante_razon_social`,
  1 AS `oie_comprobante_serie`,
  1 AS `oie_comprobante_numero`,
  1 AS `oie_comprobante_descripcion`,
  1 AS `oie_comprobante_monto` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `reporte_fuentes`;
/*!50001 DROP VIEW IF EXISTS `reporte_fuentes`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `reporte_fuentes` AS SELECT
 1 AS `fecha`,
  1 AS `codigo`,
  1 AS `descripcion`,
  1 AS `monto` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `reporte_fuentes_programa`;
/*!50001 DROP VIEW IF EXISTS `reporte_fuentes_programa`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `reporte_fuentes_programa` AS SELECT
 1 AS `id`,
  1 AS `actividad_id`,
  1 AS `tipo_comprobante_id`,
  1 AS `ff_id`,
  1 AS `codigo`,
  1 AS `serie`,
  1 AS `numero`,
  1 AS `detalle`,
  1 AS `descripcion`,
  1 AS `ruc`,
  1 AS `razon_social`,
  1 AS `monto`,
  1 AS `fecha_original`,
  1 AS `fecha` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `reporte_fuentes_programa_rendicion`;
/*!50001 DROP VIEW IF EXISTS `reporte_fuentes_programa_rendicion`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `reporte_fuentes_programa_rendicion` AS SELECT
 1 AS `programa_id`,
  1 AS `fuente_financiamiento_id`,
  1 AS `fuente_financiamiento_nombre` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `reporte_ingresos`;
/*!50001 DROP VIEW IF EXISTS `reporte_ingresos`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `reporte_ingresos` AS SELECT
 1 AS `otros_ingresos_egresos_id`,
  1 AS `otros_ingresos_egresos_fecha`,
  1 AS `otros_ingresos_egresos_codigo`,
  1 AS `otros_ingresos_egresos_descripcion`,
  1 AS `otros_ingresos_egresos_oie_tipo_id`,
  1 AS `fuente_financiamiento_id`,
  1 AS `fuente_financiamiento_codigo`,
  1 AS `oie_comprobante_fecha_original`,
  1 AS `oie_comprobante_ruc`,
  1 AS `oie_comprobante_razon_social`,
  1 AS `oie_comprobante_serie`,
  1 AS `oie_comprobante_numero`,
  1 AS `oie_comprobante_descripcion`,
  1 AS `oie_comprobante_monto` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `reporte_poa_rendicion`;
/*!50001 DROP VIEW IF EXISTS `reporte_poa_rendicion`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `reporte_poa_rendicion` AS SELECT
 1 AS `actividad_id`,
  1 AS `actividad_nombre`,
  1 AS `fuente_financiamiento_id`,
  1 AS `fuente_financiamiento_nombre`,
  1 AS `suma_monto_rendiciones` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `reporte_poa_rubros`;
/*!50001 DROP VIEW IF EXISTS `reporte_poa_rubros`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `reporte_poa_rubros` AS SELECT
 1 AS `id_programa`,
  1 AS `programa`,
  1 AS `id`,
  1 AS `producto_codigo`,
  1 AS `producto`,
  1 AS `actividad_id`,
  1 AS `actividad_codigo`,
  1 AS `actividad`,
  1 AS `rubros`,
  1 AS `id_tipo_rubro`,
  1 AS `monto` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `reporte_poa_rubros_sumas`;
/*!50001 DROP VIEW IF EXISTS `reporte_poa_rubros_sumas`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `reporte_poa_rubros_sumas` AS SELECT
 1 AS `id_programa`,
  1 AS `id_actividad`,
  1 AS `actividad`,
  1 AS `suma_monto` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `reporte_rendiciones`;
/*!50001 DROP VIEW IF EXISTS `reporte_rendiciones`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `reporte_rendiciones` AS SELECT
 1 AS `rendicion_id`,
  1 AS `rendicion_fecha`,
  1 AS `rendicion_codigo`,
  1 AS `rendicion_descripcion`,
  1 AS `rendicion_tipo_comprobante_id`,
  1 AS `tipo_comprobante_codigo`,
  1 AS `rendicion_monto`,
  1 AS `fuente_financiamiento_id`,
  1 AS `fuente_financiamiento_codigo`,
  1 AS `rendicion_fecha_original`,
  1 AS `rendicion_ruc`,
  1 AS `rendicion_razon_social`,
  1 AS `rendicion_serie`,
  1 AS `rendicion_numero`,
  1 AS `rendicion_detalle`,
  1 AS `rendicion_comprobante_monto` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `resultado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resultado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `programa_id` int(11) NOT NULL,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`),
  KEY `fk_resultado_programa1_idx` (`programa_id`),
  CONSTRAINT `fk_resultado_programa1` FOREIGN KEY (`programa_id`) REFERENCES `programa` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resultado_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resultado_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resultado_id` int(11) NOT NULL,
  `indicador_medido` int(11) DEFAULT NULL,
  `medio_verificacion` varchar(500) DEFAULT NULL,
  `supuesto` varchar(500) DEFAULT NULL,
  `responsable` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_resultado_resultado1_idx` (`resultado_id`),
  CONSTRAINT `fk_detalle_resultado_resultado1` FOREIGN KEY (`resultado_id`) REFERENCES `resultado` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rubro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rubro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actividad_id` int(11) NOT NULL,
  `categoria_rubro_id` int(11) NOT NULL,
  `tipo_rubro_id` int(11) NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rubro_admin_vista`;
/*!50001 DROP VIEW IF EXISTS `rubro_admin_vista`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `rubro_admin_vista` AS SELECT
 1 AS `id`,
  1 AS `actividad_id`,
  1 AS `categoria_rubro`,
  1 AS `subcategoria_rubro`,
  1 AS `tipo_rubro`,
  1 AS `codigo`,
  1 AS `nombre`,
  1 AS `descripcion`,
  1 AS `monto` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `subcategoria_rubro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subcategoria_rubro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipo_cambio_dolar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_cambio_dolar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo_cambio` decimal(7,2) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tipo_cambio_dolar_usuario1_idx` (`usuario_id`),
  CONSTRAINT `fk_tipo_cambio_dolar_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipo_cambio_euro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_cambio_euro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo_cambio` decimal(7,2) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tipo_cambio_euro_usuario1_idx` (`usuario_id`),
  CONSTRAINT `fk_tipo_cambio_euro_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipo_comprobante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_comprobante` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `descripcion` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipo_programa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_programa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) NOT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipo_rubro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_rubro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(8) NOT NULL,
  `nombre` varchar(500) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_UNIQUE` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipo_rubro_vista`;
/*!50001 DROP VIEW IF EXISTS `tipo_rubro_vista`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `tipo_rubro_vista` AS SELECT
 1 AS `id`,
  1 AS `nombre` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `total_monto_rendiciones_por_actividad`;
/*!50001 DROP VIEW IF EXISTS `total_monto_rendiciones_por_actividad`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `total_monto_rendiciones_por_actividad` AS SELECT
 1 AS `actividad_id`,
  1 AS `actividad_codigo`,
  1 AS `actividad_nombre`,
  1 AS `total_monto_rendiciones` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `persona_id` int(11) NOT NULL,
  `cargo_id` int(11) NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuario_admin_vista`;
/*!50001 DROP VIEW IF EXISTS `usuario_admin_vista`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `usuario_admin_vista` AS SELECT
 1 AS `id`,
  1 AS `descripcion`,
  1 AS `datos`,
  1 AS `email`,
  1 AS `telefono`,
  1 AS `cargo` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `usuario_id_disponible_programa_vista`;
/*!50001 DROP VIEW IF EXISTS `usuario_id_disponible_programa_vista`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `usuario_id_disponible_programa_vista` AS SELECT
 1 AS `id`,
  1 AS `persona_id`,
  1 AS `cargo_id`,
  1 AS `codigo` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `usuarios_coordinador_vista`;
/*!50001 DROP VIEW IF EXISTS `usuarios_coordinador_vista`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `usuarios_coordinador_vista` AS SELECT
 1 AS `usuario_id`,
  1 AS `persona_id`,
  1 AS `cargo_id`,
  1 AS `usuario_codigo`,
  1 AS `nombres` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vista_dolar`;
/*!50001 DROP VIEW IF EXISTS `vista_dolar`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_dolar` AS SELECT
 1 AS `id`,
  1 AS `usuario`,
  1 AS `tipo_cambio`,
  1 AS `fecha` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vista_euro`;
/*!50001 DROP VIEW IF EXISTS `vista_euro`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_euro` AS SELECT
 1 AS `id`,
  1 AS `usuario`,
  1 AS `tipo_cambio`,
  1 AS `fecha` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vista_fuentes_financiamiento_por_actividad`;
/*!50001 DROP VIEW IF EXISTS `vista_fuentes_financiamiento_por_actividad`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_fuentes_financiamiento_por_actividad` AS SELECT
 1 AS `actividad_id`,
  1 AS `actividad_codigo`,
  1 AS `actividad_nombre`,
  1 AS `fuente_financiamiento_id`,
  1 AS `fuente_financiamiento_codigo`,
  1 AS `fuente_financiamiento_nombre`,
  1 AS `presupuesto_fuente_financiamiento`,
  1 AS `programa_codigo`,
  1 AS `programa_nombre` */;
SET character_set_client = @saved_cs_client;
/*!50001 DROP VIEW IF EXISTS `cantidad_fuentes_rendicion`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `cantidad_fuentes_rendicion` AS select count(distinct `rendicion`.`ff_id`) AS `numero` from `rendicion` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `fuente_por_actividad_vista`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `fuente_por_actividad_vista` AS select `f`.`id` AS `fuente_id`,`a`.`id` AS `actividad_id`,`f`.`nombre` AS `fuente_nombre`,`f`.`presupuesto` AS `fuente_presupuesto` from ((((((`fuente_financiamiento` `f` join `detalle_financiamiento` `d` on(`f`.`id` = `d`.`fuente_financiamiento_id`)) join `programa` `p` on(`d`.`programa_id` = `p`.`id`)) join `poa` `i` on(`p`.`id` = `i`.`programa_id`)) join `resultado` `r` on(`p`.`id` = `r`.`programa_id`)) join `producto` `o` on(`r`.`id` = `o`.`resultado_id`)) join `actividad` `a` on(`o`.`id` = `a`.`producto_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `fuentes_por_poa_id`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `fuentes_por_poa_id` AS select `p`.`id` AS `poa_id`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`codigo` AS `fuente_financiamiento_codigo`,`f`.`nombre` AS `fuente_financiamiento_nombre` from (((`poa` `p` join `programa` `r` on(`p`.`programa_id` = `r`.`id`)) join `detalle_financiamiento` `d` on(`r`.`id` = `d`.`programa_id`)) join `fuente_financiamiento` `f` on(`d`.`fuente_financiamiento_id` = `f`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `login_session_vista`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `login_session_vista` AS select `u`.`id` AS `id`,`c`.`id` AS `cargo_id`,coalesce(`o`.`id`,NULL) AS `poa_id`,`u`.`email` AS `email`,`u`.`password` AS `password`,`u`.`reset_token` AS `reset_token`,concat(substring_index(`p`.`nombres`,' ',1),' ',`p`.`apellido_paterno`) AS `datos`,`c`.`descripcion` AS `cargo`,coalesce(`r`.`id`,NULL) AS `programa_id` from ((((`usuario` `u` join `persona` `p` on(`u`.`persona_id` = `p`.`id`)) join `cargo` `c` on(`u`.`cargo_id` = `c`.`id`)) left join `poa` `o` on(`u`.`id` = `o`.`usuario_id`)) left join `programa` `r` on(`o`.`programa_id` = `r`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `otros_ingresos_egresos_admin_vista`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `otros_ingresos_egresos_admin_vista` AS select `o`.`id` AS `id`,`o`.`codigo` AS `codigo`,`p`.`nombre` AS `tipo`,`t`.`codigo` AS `tipo_comprobante_codigo`,`i`.`monto` AS `comprobante_monto`,`i`.`fecha_original` AS `comprobante_fecha` from ((((`otros_ingresos_egresos` `o` join `oie_comprobante` `i` on(`o`.`oie_comprobante_id` = `i`.`id`)) join `oie_tipo_comprobante` `t` on(`i`.`oie_tipo_comprobante_id` = `t`.`id`)) join `oie_tipo` `p` on(`o`.`oie_tipo_id` = `p`.`id`)) join `poa` `a` on(`o`.`poa_id` = `a`.`id`)) order by `o`.`id` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `programa_poa_vista`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `programa_poa_vista` AS select `p`.`id` AS `poa_id`,`r`.`id` AS `programa_id`,`r`.`codigo` AS `programa_codigo`,`r`.`nombre` AS `programa_nombre`,`p`.`anio` AS `poa_anio` from (`poa` `p` join `programa` `r` on(`p`.`programa_id` = `r`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `programas_sin_coordinador_vista`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `programas_sin_coordinador_vista` AS select `p`.`id` AS `programa_id`,`p`.`codigo` AS `programa_codigo`,`p`.`nombre` AS `programa_nombre` from (`programa` `p` left join (`usuario` `u` join `poa` `o` on(`u`.`id` = `o`.`usuario_id`)) on(`o`.`programa_id` = `p`.`id`)) where `u`.`id` is null */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `rendicion_admin`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `rendicion_admin` AS select `r`.`id` AS `id`,`a`.`producto_id` AS `producto_id`,`r`.`actividad_id` AS `actividad_id`,`r`.`codigo` AS `codigo`,`t`.`descripcion` AS `tipo_comprobante`,`r`.`serie` AS `serie`,`r`.`numero` AS `numero`,`r`.`detalle` AS `detalle`,`r`.`ruc` AS `ruc`,`r`.`razon_social` AS `razon_social`,`r`.`monto` AS `monto`,`r`.`ff_id` AS `fuente_financiamiento_id`,`f`.`nombre` AS `fuente_financiamiento`,`r`.`fecha_original` AS `fecha_comprobante` from (((`rendicion` `r` join `tipo_comprobante` `t`) join `actividad` `a`) join `fuente_financiamiento` `f`) where `t`.`id` = `r`.`tipo_comprobante_id` and `a`.`id` = `r`.`actividad_id` and `f`.`id` = `r`.`ff_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `reporte_egresos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `reporte_egresos` AS select distinct `o`.`id` AS `otros_ingresos_egresos_id`,cast(`o`.`fecha` as date) AS `otros_ingresos_egresos_fecha`,`o`.`codigo` AS `otros_ingresos_egresos_codigo`,`o`.`descripcion` AS `otros_ingresos_egresos_descripcion`,`p`.`id` AS `oie_tipo_comprobante_id`,`p`.`codigo` AS `oie_tipo_comprobante_codigo`,`o`.`oie_tipo_id` AS `otros_ingresos_egresos_oie_tipo_id`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`codigo` AS `fuente_financiamiento_codigo`,`c`.`fecha_original` AS `oie_comprobante_fecha_original`,`c`.`ruc` AS `oie_comprobante_ruc`,`c`.`razon_social` AS `oie_comprobante_razon_social`,`c`.`serie` AS `oie_comprobante_serie`,`c`.`numero` AS `oie_comprobante_numero`,`c`.`descripcion` AS `oie_comprobante_descripcion`,`c`.`monto` AS `oie_comprobante_monto` from ((((((`otros_ingresos_egresos` `o` left join `oie_comprobante` `c` on(`o`.`oie_comprobante_id` = `c`.`id`)) join `oie_tipo_comprobante` `p` on(`p`.`id` = `c`.`oie_tipo_comprobante_id`)) join `detalle_financiamiento` `d` on(`d`.`fuente_financiamiento_id` = `o`.`ff_id`)) join `fuente_financiamiento` `f` on(`f`.`id` = `d`.`fuente_financiamiento_id`)) join `programa` `g` on(`g`.`id` = `d`.`programa_id`)) join `oie_tipo` `t` on(`t`.`id` = `o`.`oie_tipo_id`)) where `o`.`oie_tipo_id` = 2 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `reporte_fuentes`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `reporte_fuentes` AS select cast(`fuente_financiamiento`.`fecha` as date) AS `fecha`,`fuente_financiamiento`.`codigo` AS `codigo`,`fuente_financiamiento`.`nombre` AS `descripcion`,`fuente_financiamiento`.`presupuesto` AS `monto` from `fuente_financiamiento` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `reporte_fuentes_programa`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `reporte_fuentes_programa` AS select `rendicion`.`id` AS `id`,`rendicion`.`actividad_id` AS `actividad_id`,`rendicion`.`tipo_comprobante_id` AS `tipo_comprobante_id`,`rendicion`.`ff_id` AS `ff_id`,`rendicion`.`codigo` AS `codigo`,`rendicion`.`serie` AS `serie`,`rendicion`.`numero` AS `numero`,`rendicion`.`detalle` AS `detalle`,`rendicion`.`descripcion` AS `descripcion`,`rendicion`.`ruc` AS `ruc`,`rendicion`.`razon_social` AS `razon_social`,`rendicion`.`monto` AS `monto`,`rendicion`.`fecha_original` AS `fecha_original`,`rendicion`.`fecha` AS `fecha` from `rendicion` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `reporte_fuentes_programa_rendicion`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `reporte_fuentes_programa_rendicion` AS select `p`.`id` AS `programa_id`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`nombre` AS `fuente_financiamiento_nombre` from ((`fuente_financiamiento` `f` join `detalle_financiamiento` `d` on(`f`.`id` = `d`.`fuente_financiamiento_id`)) join `programa` `p` on(`d`.`programa_id` = `p`.`id`)) order by `p`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `reporte_ingresos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `reporte_ingresos` AS select distinct `o`.`id` AS `otros_ingresos_egresos_id`,`o`.`fecha` AS `otros_ingresos_egresos_fecha`,`o`.`codigo` AS `otros_ingresos_egresos_codigo`,`o`.`descripcion` AS `otros_ingresos_egresos_descripcion`,`o`.`oie_tipo_id` AS `otros_ingresos_egresos_oie_tipo_id`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`codigo` AS `fuente_financiamiento_codigo`,`c`.`fecha_original` AS `oie_comprobante_fecha_original`,`c`.`ruc` AS `oie_comprobante_ruc`,`c`.`razon_social` AS `oie_comprobante_razon_social`,`c`.`serie` AS `oie_comprobante_serie`,`c`.`numero` AS `oie_comprobante_numero`,`c`.`descripcion` AS `oie_comprobante_descripcion`,`c`.`monto` AS `oie_comprobante_monto` from (((((`otros_ingresos_egresos` `o` left join `oie_comprobante` `c` on(`o`.`oie_comprobante_id` = `c`.`id`)) join `detalle_financiamiento` `d` on(`d`.`fuente_financiamiento_id` = `o`.`ff_id`)) join `fuente_financiamiento` `f` on(`f`.`id` = `d`.`fuente_financiamiento_id`)) join `programa` `g` on(`g`.`id` = `d`.`programa_id`)) join `oie_tipo` `t` on(`t`.`id` = `o`.`oie_tipo_id`)) where `o`.`oie_tipo_id` = 1 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `reporte_poa_rendicion`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `reporte_poa_rendicion` AS select `a`.`id` AS `actividad_id`,`a`.`nombre` AS `actividad_nombre`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`nombre` AS `fuente_financiamiento_nombre`,sum(`r`.`monto`) AS `suma_monto_rendiciones` from ((`rendicion` `r` join `actividad` `a` on(`r`.`actividad_id` = `a`.`id`)) join `fuente_financiamiento` `f` on(`r`.`ff_id` = `f`.`id`)) group by `f`.`id`,`f`.`nombre`,`a`.`id`,`a`.`nombre` order by `a`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `reporte_poa_rubros`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `reporte_poa_rubros` AS select `p`.`id` AS `id_programa`,`p`.`nombre` AS `programa`,`u`.`id` AS `id`,`o`.`codigo` AS `producto_codigo`,`o`.`nombre` AS `producto`,`a`.`id` AS `actividad_id`,`a`.`codigo` AS `actividad_codigo`,`a`.`nombre` AS `actividad`,`u`.`nombre` AS `rubros`,`t`.`id` AS `id_tipo_rubro`,`u`.`monto` AS `monto` from (((((`programa` `p` join `resultado` `r` on(`p`.`id` = `r`.`programa_id`)) join `producto` `o` on(`r`.`id` = `o`.`resultado_id`)) join `actividad` `a` on(`o`.`id` = `a`.`producto_id`)) join `rubro` `u` on(`a`.`id` = `u`.`actividad_id`)) join `tipo_rubro` `t` on(`t`.`id` = `u`.`tipo_rubro_id`)) order by `a`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `reporte_poa_rubros_sumas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `reporte_poa_rubros_sumas` AS select `p`.`id` AS `id_programa`,`a`.`id` AS `id_actividad`,`a`.`nombre` AS `actividad`,sum(`u`.`monto`) AS `suma_monto` from ((((`programa` `p` join `resultado` `r` on(`p`.`id` = `r`.`programa_id`)) join `producto` `o` on(`r`.`id` = `o`.`resultado_id`)) join `actividad` `a` on(`o`.`id` = `a`.`producto_id`)) join `rubro` `u` on(`a`.`id` = `u`.`actividad_id`)) group by `p`.`id`,`a`.`id`,`a`.`nombre` order by `a`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `reporte_rendiciones`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `reporte_rendiciones` AS select `r`.`id` AS `rendicion_id`,cast(`r`.`fecha` as date) AS `rendicion_fecha`,`r`.`codigo` AS `rendicion_codigo`,`r`.`descripcion` AS `rendicion_descripcion`,`t`.`id` AS `rendicion_tipo_comprobante_id`,`t`.`codigo` AS `tipo_comprobante_codigo`,`r`.`monto` AS `rendicion_monto`,`f`.`id` AS `fuente_financiamiento_id`,`f`.`codigo` AS `fuente_financiamiento_codigo`,`r`.`fecha_original` AS `rendicion_fecha_original`,`r`.`ruc` AS `rendicion_ruc`,`r`.`razon_social` AS `rendicion_razon_social`,`r`.`serie` AS `rendicion_serie`,`r`.`numero` AS `rendicion_numero`,`r`.`detalle` AS `rendicion_detalle`,`r`.`monto` AS `rendicion_comprobante_monto` from ((((((`rendicion` `r` join `tipo_comprobante` `t` on(`r`.`tipo_comprobante_id` = `t`.`id`)) join `actividad` `a` on(`r`.`actividad_id` = `a`.`id`)) join `producto` `o` on(`a`.`producto_id` = `o`.`id`)) join `resultado` `e` on(`o`.`resultado_id` = `e`.`id`)) join `programa` `g` on(`e`.`programa_id` = `g`.`id`)) join `fuente_financiamiento` `f` on(`f`.`id` = `r`.`ff_id`)) group by `r`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `rubro_admin_vista`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `rubro_admin_vista` AS select `r`.`id` AS `id`,`a`.`id` AS `actividad_id`,`c`.`codigo` AS `categoria_rubro`,`s`.`codigo` AS `subcategoria_rubro`,`t`.`codigo` AS `tipo_rubro`,`r`.`codigo` AS `codigo`,`r`.`nombre` AS `nombre`,`r`.`descripcion` AS `descripcion`,`r`.`monto` AS `monto` from ((((`rubro` `r` join `actividad` `a`) join `categoria_rubro` `c`) join `tipo_rubro` `t`) join `subcategoria_rubro` `s`) where `r`.`actividad_id` = `a`.`id` and `r`.`categoria_rubro_id` = `c`.`id` and `r`.`tipo_rubro_id` = `t`.`id` and `c`.`subcategoria_rubro_id` = `s`.`id` order by `r`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `tipo_rubro_vista`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `tipo_rubro_vista` AS select `tipo_rubro`.`id` AS `id`,`tipo_rubro`.`nombre` AS `nombre` from `tipo_rubro` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `total_monto_rendiciones_por_actividad`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `total_monto_rendiciones_por_actividad` AS select `r`.`actividad_id` AS `actividad_id`,`a`.`codigo` AS `actividad_codigo`,`a`.`nombre` AS `actividad_nombre`,sum(`r`.`monto`) AS `total_monto_rendiciones` from (`rendicion` `r` join `actividad` `a` on(`r`.`actividad_id` = `a`.`id`)) group by `r`.`actividad_id`,`a`.`codigo`,`a`.`nombre` order by `r`.`actividad_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `usuario_admin_vista`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `usuario_admin_vista` AS select `u`.`id` AS `id`,`u`.`descripcion` AS `descripcion`,concat(`p`.`apellido_paterno`,' ',`p`.`apellido_materno`,' ',`p`.`nombres`) AS `datos`,`u`.`email` AS `email`,`p`.`telefono` AS `telefono`,`c`.`descripcion` AS `cargo` from ((`usuario` `u` join `persona` `p`) join `cargo` `c`) where `u`.`persona_id` = `p`.`id` and `u`.`cargo_id` = `c`.`id` order by `u`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `usuario_id_disponible_programa_vista`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `usuario_id_disponible_programa_vista` AS select `u`.`id` AS `id`,`u`.`persona_id` AS `persona_id`,`u`.`cargo_id` AS `cargo_id`,`u`.`descripcion` AS `codigo` from `usuario` `u` where `u`.`cargo_id` = 3 and `u`.`id` in (select `poa`.`usuario_id` from `poa`) is false */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `usuarios_coordinador_vista`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `usuarios_coordinador_vista` AS select `u`.`id` AS `usuario_id`,`p`.`id` AS `persona_id`,`u`.`cargo_id` AS `cargo_id`,`u`.`descripcion` AS `usuario_codigo`,concat(`p`.`apellido_paterno`,' ',`p`.`apellido_materno`,' ',`p`.`nombres`) AS `nombres` from (`usuario` `u` join `persona` `p`) where `u`.`cargo_id` = 3 and `p`.`id` = `u`.`persona_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vista_dolar`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_dolar` AS select `t`.`id` AS `id`,`u`.`descripcion` AS `usuario`,`t`.`tipo_cambio` AS `tipo_cambio`,`t`.`fecha` AS `fecha` from (`usuario` `u` join `tipo_cambio_dolar` `t`) where `u`.`id` = `t`.`usuario_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vista_euro`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_euro` AS select `t`.`id` AS `id`,`u`.`descripcion` AS `usuario`,`t`.`tipo_cambio` AS `tipo_cambio`,`t`.`fecha` AS `fecha` from (`usuario` `u` join `tipo_cambio_euro` `t`) where `u`.`id` = `t`.`usuario_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vista_fuentes_financiamiento_por_actividad`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_fuentes_financiamiento_por_actividad` AS select distinct `a`.`id` AS `actividad_id`,`a`.`codigo` AS `actividad_codigo`,`a`.`nombre` AS `actividad_nombre`,`ff`.`id` AS `fuente_financiamiento_id`,`ff`.`codigo` AS `fuente_financiamiento_codigo`,`ff`.`nombre` AS `fuente_financiamiento_nombre`,`ff`.`presupuesto` AS `presupuesto_fuente_financiamiento`,`p`.`codigo` AS `programa_codigo`,`p`.`nombre` AS `programa_nombre` from (((((`actividad` `a` join `producto` `pr` on(`a`.`producto_id` = `pr`.`id`)) join `resultado` `r` on(`pr`.`resultado_id` = `r`.`id`)) join `programa` `p` on(`r`.`programa_id` = `p`.`id`)) join `detalle_financiamiento` `df` on(`p`.`id` = `df`.`programa_id`)) join `fuente_financiamiento` `ff` on(`df`.`fuente_financiamiento_id` = `ff`.`id`)) order by `a`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

