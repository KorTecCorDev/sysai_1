-- ============================================================================
-- SysAI — Seeder de datos de PRUEBA (datos ficticios)
-- Requiere haber cargado primero db/schema.sql.
-- Uso:  mysql -u <user> -p <bd> < db/seed.sql
--
-- CREDENCIALES DE PRUEBA (todas con la misma contraseña):  Test1234*
--   Administrador : admin@sysai.test
--   Contador      : contador@sysai.test
--   Coordinador   : coordinador@sysai.test
-- (Hash bcrypt de "Test1234*" reutilizado en los 3 usuarios.)
--
-- Conjunto mínimo pero coherente: 1 programa con 2 fuentes, 1 POA (coordinador),
-- resultado→producto→2 actividades→4 rubros, 3 rendiciones y 2 OIE, para poder
-- ejercitar todos los reportes.
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- Limpieza de datos (respeta el esquema, vacía las tablas)
TRUNCATE TABLE `otros_ingresos_egresos`;
TRUNCATE TABLE `oie_comprobante`;
TRUNCATE TABLE `rendicion`;
TRUNCATE TABLE `rubro`;
TRUNCATE TABLE `actividad`;
TRUNCATE TABLE `producto`;
TRUNCATE TABLE `resultado`;
TRUNCATE TABLE `detalle_financiamiento`;
TRUNCATE TABLE `poa`;
TRUNCATE TABLE `tipo_cambio_dolar`;
TRUNCATE TABLE `tipo_cambio_euro`;
TRUNCATE TABLE `usuario`;
TRUNCATE TABLE `persona`;
TRUNCATE TABLE `programa`;
TRUNCATE TABLE `fuente_financiamiento`;
TRUNCATE TABLE `categoria_rubro`;
TRUNCATE TABLE `subcategoria_rubro`;
TRUNCATE TABLE `tipo_rubro`;
TRUNCATE TABLE `tipo_comprobante`;
TRUNCATE TABLE `oie_tipo`;
TRUNCATE TABLE `oie_tipo_comprobante`;
TRUNCATE TABLE `tipo_programa`;
TRUNCATE TABLE `cargo`;
TRUNCATE TABLE `auditoria`;

-- ----------------------------------------------------------------------------
-- Catálogos
-- ----------------------------------------------------------------------------
INSERT INTO `cargo` (`id`,`descripcion`) VALUES
  (1,'Administrador'),(2,'Contador'),(3,'Coordinador');

INSERT INTO `tipo_programa` (`id`,`descripcion`,`fecha`) VALUES
  (1,'Nacional','2025-01-01 09:00:00'),(2,'Internacional','2025-01-01 09:00:00');

INSERT INTO `tipo_rubro` (`id`,`codigo`,`nombre`,`descripcion`) VALUES
  (1,'TRB001','Bien',''),(2,'TRB002','Servicio','');

INSERT INTO `tipo_comprobante` (`id`,`codigo`,`descripcion`) VALUES
  (1,'TCM001','Factura'),(2,'TCM002','Boleta'),(3,'TCM003','Declaración Jurada'),(4,'TCM004','Recibo por honorarios');

INSERT INTO `oie_tipo` (`id`,`nombre`) VALUES (1,'Ingreso'),(2,'Egreso');

INSERT INTO `oie_tipo_comprobante` (`id`,`codigo`,`nombre`) VALUES
  (1,'OTC00001','Factura'),(2,'OTC00002','Boleta'),(3,'OTC00003','Declaración jurada'),(4,'OTC00004','Recibo por Honorarios');

INSERT INTO `subcategoria_rubro` (`id`,`codigo`,`nombre`,`descripcion`,`fecha`) VALUES
  (1,'SCR001','Costos directos','','2025-01-05 09:00:00'),
  (2,'SCR002','Costos indirectos','','2025-01-05 09:00:00');

INSERT INTO `categoria_rubro` (`id`,`subcategoria_rubro_id`,`codigo`,`nombre`,`descripcion`,`fecha`) VALUES
  (1,1,'CRB001','Asistencia técnica','','2025-01-06 09:00:00'),
  (2,1,'CRB002','Equipamiento','','2025-01-06 09:00:00'),
  (3,1,'CRB003','Infraestructura','','2025-01-06 09:00:00'),
  (4,1,'CRB004','Desplazamiento','Viajes y viáticos','2025-01-06 09:00:00'),
  (5,1,'CRB005','Monitoreo y evaluación','','2025-01-06 09:00:00'),
  (6,1,'CRB006','Otros','','2025-01-06 09:00:00'),
  (7,2,'CRB007','Personal','Permanente','2025-01-06 09:00:00'),
  (8,2,'CRB008','Servicios de terceros','Contabilidad y asesoría','2025-01-06 09:00:00'),
  (9,2,'CRB009','Implementación de oficina','Mobiliario o equipo informático','2025-01-06 09:00:00'),
  (10,2,'CRB010','Administración del proyecto','Servicios y otros','2025-01-06 09:00:00');

-- ----------------------------------------------------------------------------
-- Identidad (contraseña de los 3: Test1234*)
-- ----------------------------------------------------------------------------
INSERT INTO `persona` (`id`,`nro_documento`,`apellido_paterno`,`apellido_materno`,`nombres`,`telefono`,`fecha`) VALUES
  (1,10000001,'DEMO','ADMIN','ANA','900000001','2025-01-10 09:00:00'),
  (2,10000002,'DEMO','CONTADOR','CARLOS','900000002','2025-01-10 09:00:00'),
  (3,10000003,'DEMO','COORDINADOR','CECILIA','900000003','2025-01-10 09:00:00');

INSERT INTO `usuario` (`id`,`persona_id`,`cargo_id`,`descripcion`,`email`,`password`,`reset_token`,`fecha`) VALUES
  (1,1,1,'ADMN','admin@sysai.test','$2y$10$RM9YpRekhckVrbejQ8H0seKMuhDyTe5Jka.2HIx50ld0QaTfEw6pi',NULL,'2025-01-10 09:00:00'),
  (2,2,2,'CONT','contador@sysai.test','$2y$10$RM9YpRekhckVrbejQ8H0seKMuhDyTe5Jka.2HIx50ld0QaTfEw6pi',NULL,'2025-01-10 09:00:00'),
  (3,3,3,'COOR','coordinador@sysai.test','$2y$10$RM9YpRekhckVrbejQ8H0seKMuhDyTe5Jka.2HIx50ld0QaTfEw6pi',NULL,'2025-01-10 09:00:00');

-- ----------------------------------------------------------------------------
-- Programa, fuentes y POA
-- ----------------------------------------------------------------------------
INSERT INTO `programa` (`id`,`codigo`,`nombre`,`descripcion`,`fecha`,`tipo_programa_id`) VALUES
  (1,'PROG001','PROGRAMA DEMO EDUCACIÓN','PROGRAMA DE PRUEBA PARA DESARROLLO','2025-01-12 09:00:00',1);

INSERT INTO `fuente_financiamiento` (`id`,`codigo`,`nombre`,`descripcion`,`presupuesto`,`fecha`) VALUES
  (1,'FUEF001','COOPERANTE ALFA','FUENTE DE PRUEBA ALFA',50000.00,'2025-01-12 09:00:00'),
  (2,'FUEF002','COOPERANTE BETA','FUENTE DE PRUEBA BETA',30000.00,'2025-01-12 09:00:00');

INSERT INTO `detalle_financiamiento` (`id`,`programa_id`,`fuente_financiamiento_id`,`fecha`) VALUES
  (1,1,1,'2025-01-12 09:00:00'),
  (2,1,2,'2025-01-12 09:00:00');

INSERT INTO `poa` (`id`,`programa_id`,`anio`,`presupuesto`,`estado`,`fecha`,`usuario_id`) VALUES
  (1,1,'2025',0.00,0,'2025-01-12 09:00:00',3);

-- Tipos de cambio (último registro = el usado en reportes)
INSERT INTO `tipo_cambio_dolar` (`id`,`usuario_id`,`tipo_cambio`,`fecha`) VALUES
  (1,1,3.75,'2025-01-13 09:00:00');
INSERT INTO `tipo_cambio_euro` (`id`,`usuario_id`,`tipo_cambio`,`fecha`) VALUES
  (1,1,4.05,'2025-01-13 09:00:00');

-- ----------------------------------------------------------------------------
-- Jerarquía POA: resultado -> producto -> actividades -> rubros
-- ----------------------------------------------------------------------------
INSERT INTO `resultado` (`id`,`programa_id`,`codigo`,`nombre`,`descripcion`,`fecha`) VALUES
  (1,1,'RESU001','RESULTADO DEMO 01','RESULTADO DE PRUEBA','2025-01-14 09:00:00');

INSERT INTO `producto` (`id`,`resultado_id`,`codigo`,`nombre`,`descripcion`,`fecha`) VALUES
  (1,1,'PROD001','PRODUCTO DEMO 01','PRODUCTO DE PRUEBA','2025-01-14 09:00:00');

INSERT INTO `actividad` (`id`,`producto_id`,`codigo`,`nombre`,`descripcion`,`fecha`) VALUES
  (1,1,'ACTV001','ACTIVIDAD DEMO 01','EQUIPAMIENTO Y CAPACITACIÓN','2025-01-15 09:00:00'),
  (2,1,'ACTV002','ACTIVIDAD DEMO 02','MOBILIARIO Y HONORARIOS','2025-01-15 09:00:00');

INSERT INTO `rubro` (`id`,`actividad_id`,`categoria_rubro_id`,`tipo_rubro_id`,`codigo`,`nombre`,`descripcion`,`monto`,`fecha`) VALUES
  (1,1,2,1,'BIEN001','LAPTOPS','EQUIPOS PARA EL PROGRAMA',12000.00,'2025-01-16 09:00:00'),
  (2,1,8,2,'SERV001','CAPACITACIÓN DOCENTE','SERVICIO DE TERCEROS',3500.00,'2025-01-16 09:00:00'),
  (3,2,2,1,'BIEN002','MOBILIARIO','MESAS Y SILLAS',8000.00,'2025-01-16 09:00:00'),
  (4,2,7,2,'SERV002','HONORARIOS FACILITADOR','PERSONAL',4500.00,'2025-01-16 09:00:00');

-- ----------------------------------------------------------------------------
-- Rendiciones (gasto rendido por actividad/fuente)
-- ----------------------------------------------------------------------------
INSERT INTO `rendicion` (`id`,`actividad_id`,`tipo_comprobante_id`,`ff_id`,`codigo`,`serie`,`numero`,`detalle`,`descripcion`,`ruc`,`razon_social`,`monto`,`fecha_original`,`fecha`) VALUES
  (1,1,1,1,'REND001','F001','000123','COMPRA DE LAPTOPS','',  '20512345671','TECNOLOGÍA SAC',11800.00,'2025-04-10','2025-04-10 10:00:00'),
  (2,1,2,2,'REND002','B001','000045','MATERIALES DE CAPACITACIÓN','','20512345672','LIBRERÍA EIRL',1200.00,'2025-04-15','2025-04-15 10:00:00'),
  (3,2,4,1,'REND003','RH01','000010','PAGO A FACILITADOR','','10456789012','JUAN PÉREZ GARCÍA',4500.00,'2025-04-20','2025-04-20 10:00:00');

-- ----------------------------------------------------------------------------
-- Otros Ingresos / Egresos (con su comprobante)
-- ----------------------------------------------------------------------------
INSERT INTO `oie_comprobante` (`id`,`oie_tipo_comprobante_id`,`serie`,`numero`,`descripcion`,`ruc`,`razon_social`,`monto`,`fecha_original`,`fecha`) VALUES
  (1,1,'F002','000900','DONACIÓN RECIBIDA','20512345673','COOPERANTE ALFA',5000.00,'2025-04-01','2025-04-01 10:00:00'),
  (2,2,'B100','000077','COMISIÓN BANCARIA','20512345674','BANCO XYZ',150.00,'2025-04-05','2025-04-05 10:00:00');

INSERT INTO `otros_ingresos_egresos` (`id`,`poa_id`,`oie_comprobante_id`,`oie_tipo_id`,`ff_id`,`codigo`,`descripcion`,`fecha`) VALUES
  (1,1,1,1,1,'OIE001','INGRESO POR DONACIÓN','2025-04-01 10:05:00'),
  (2,1,2,2,1,'OIE002','EGRESO COMISIÓN BANCARIA','2025-04-05 10:05:00');

SET FOREIGN_KEY_CHECKS = 1;

-- Fin del seeder. Datos cargados: 3 usuarios, 1 programa, 2 fuentes, 1 POA,
-- 1 resultado, 1 producto, 2 actividades, 4 rubros, 3 rendiciones, 2 OIE.
