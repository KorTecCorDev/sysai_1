-- ============================================================================
--  seed_qa.sql — Fixture DETERMINISTA para la suite de QA HTTP (database/qa_*.ps1)
-- ----------------------------------------------------------------------------
--  Reproduce EXACTAMENTE el estado que asumen los tres arneses automatizados,
--  para que `qa_all.ps1` corra idéntico en cualquier máquina (portátil por estar
--  versionado en el repo). Es un fixture MÍNIMO y distinto del escenario de
--  demostración visual (ver database/seed_demo.sql): aquí NO hay POAs del año en
--  curso en el programa 1 (los arneses los crean y borran ellos mismos).
--
--  CONTRATO que consumen los arneses (no cambiar sin ajustarlos):
--    · Usuarios de QA:
--        contador@sysai.test    / admin1234   (cargo 2)
--        coordinador@sysai.test / Test1234*   (cargo 3, coordinador del programa 1)
--        coordinador5@sysai.test/ Test1234*   (cargo 3, coordinador del programa 5; usuario_id=6)
--    · Programa 1: jerarquía con ACTIVIDAD id=1 y RUBRO id=2; Σ rubro.monto = 28000
--      (qa_poa_presupuestal verifica presupuesto=28000); sobres financiados para las
--      fuentes 1 y 2 (qa_rendicion imputa contra el sobre programa1↔fuente1).
--    · Programa 5: coordinador propio (usuario_id=6), jerarquía hasta una actividad
--      (qa_rendicion cross-tenant) y un poa_indicadores id=1 (qa_poa_indicadores
--      cross-tenant hace GET /poa_indicadores/revisar?id=1 y espera 403).
--    · SIN poa/poa_indicadores del programa 1 del año en curso.
--
--  Requisitos previos: schema_baseline.sql + migraciones (001-024) + seed.sql
--  (catálogos + admin id=1). Es RE-EJECUTABLE: limpia datos previos (demo o QA),
--  preserva el admin id=1, y reinstala el fixture. Modo mutuamente excluyente con
--  seed_demo.sql sobre la misma BD `sysai`. SOLO para desarrollo/QA.
--
--  Contraseñas (bcrypt PASSWORD_BCRYPT). Para regenerar un hash:
--    php -r "echo password_hash('NUEVA', PASSWORD_BCRYPT);"
-- ============================================================================

SET @usuario_actual = 'SEEDER_QA';
SET NAMES utf8;

-- ---------------------------------------------------------------------------
-- 0) Limpieza de datos previos (preserva admin id=1 y los catálogos de seed.sql)
-- ---------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM tipo_cambio_dolar;
DELETE FROM tipo_cambio_euro;
DELETE FROM otros_ingresos_egresos;
DELETE FROM oie_comprobante;
DELETE FROM fuente_presupuesto_anual;
DELETE FROM rendicion;
DELETE FROM poa;
DELETE FROM poa_indicadores;
DELETE FROM detalle_actividad;
DELETE FROM rubro;
DELETE FROM actividad;
DELETE FROM producto;
DELETE FROM resultado;
DELETE FROM detalle_financiamiento;
DELETE FROM coordinador_programa;
DELETE FROM fuente_financiamiento;
DELETE FROM programa;
DELETE FROM usuario  WHERE id <> 1;   -- conserva el admin
DELETE FROM persona  WHERE id <> 1;   -- conserva la persona del admin
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- 1) PERSONAS y USUARIOS de QA   [admin = id 1 ya existe por seed.sql]
--    Contraseñas: contador -> admin1234 ; coordinadores -> Test1234*
-- ---------------------------------------------------------------------------
INSERT INTO persona (id, nro_documento, apellido_paterno, apellido_materno, nombres, telefono, fecha) VALUES
  (2, 70000002, 'QA', 'CONTADOR',    'CONTADOR QA',    NULL, NOW()),
  (3, 70000003, 'QA', 'COORDINADOR', 'COORDINADOR QA', NULL, NOW()),
  (6, 70000006, 'QA', 'COORDINADOR', 'COORDINADOR5 QA',NULL, NOW());

-- `usuario.descripcion` (código de usuario) fue retirado en la migración 024: login por email.
INSERT INTO usuario (id, persona_id, cargo_id, email, password, fecha) VALUES
  (2, 2, 2, 'contador@sysai.test',
     '$2y$10$UDUnNShjObLdfWgjxk6MTuZlbM7/guNFkupgQwe91G0BMDMFTpZYq', NOW()),   -- admin1234
  (3, 3, 3, 'coordinador@sysai.test',
     '$2y$10$mNtrMoJR.deJUWjraUMKFeijmk7IzS0Pa/k5bwN2yq7D3G1rUK4OO', NOW()),   -- Test1234*
  (6, 6, 3, 'coordinador5@sysai.test',
     '$2y$10$mNtrMoJR.deJUWjraUMKFeijmk7IzS0Pa/k5bwN2yq7D3G1rUK4OO', NOW());   -- Test1234*

-- ---------------------------------------------------------------------------
-- 2) PROGRAMAS  (ids fijos: 1 y 5 los referencian los arneses)
-- ---------------------------------------------------------------------------
INSERT INTO programa (id, codigo, nombre, descripcion, tipo_programa_id, fecha) VALUES
  (1, 'PRG001', 'PROGRAMA QA UNO',   'PROGRAMA DE PRUEBA PARA LA SUITE DE QA', 1, NOW()),
  (5, 'PRG005', 'PROGRAMA QA CINCO', 'PROGRAMA DE PRUEBA CROSS-TENANT',        1, NOW());

-- ---------------------------------------------------------------------------
-- 3) VÍNCULO COORDINADOR-PROGRAMA (uno activo por programa)
-- ---------------------------------------------------------------------------
INSERT INTO coordinador_programa (id, usuario_id, programa_id, activo, fecha) VALUES
  (1, 3, 1, 1, NOW()),   -- coordinador@sysai.test  -> programa 1
  (2, 6, 5, 1, NOW());   -- coordinador5@sysai.test -> programa 5

-- ---------------------------------------------------------------------------
-- 4) FUENTES DE FINANCIAMIENTO (presupuesto en S/; decimal(8,2) => máx 999,999.99)
-- ---------------------------------------------------------------------------
INSERT INTO fuente_financiamiento (id, codigo, nombre, descripcion, presupuesto, fecha) VALUES
  (1, 'FF001', 'FUENTE QA UNO', 'FUENTE DE PRUEBA 1', 200000.00, NOW()),
  (2, 'FF002', 'FUENTE QA DOS', 'FUENTE DE PRUEBA 2', 100000.00, NOW());

-- ---------------------------------------------------------------------------
-- 5) SOBRES del programa 1 (detalle_financiamiento.monto_asignado).
--    Financiados con holgura: qa_rendicion imputa una rendición de 3000 al sobre
--    (programa 1, fuente 1). Invariante Σ sobres por fuente <= fuente.presupuesto.
-- ---------------------------------------------------------------------------
INSERT INTO detalle_financiamiento (id, programa_id, fuente_financiamiento_id, monto_asignado, fecha) VALUES
  (1, 1, 1, 100000.00, NOW()),   -- programa 1 <- fuente 1 (sobre 100k de 200k)
  (2, 1, 2,  50000.00, NOW());   -- programa 1 <- fuente 2 (sobre 50k de 100k)

-- ===========================================================================
--  6) JERARQUÍA POA — PROGRAMA 1 (códigos jerárquicos; ACTIVIDAD id=1, RUBRO id=2)
--     Σ rubro.monto = 20000 + 8000 = 28000  (lo verifica qa_poa_presupuestal)
-- ===========================================================================
INSERT INTO resultado (id, programa_id, codigo, nombre, descripcion, fecha) VALUES
  (1, 1, '1', 'RESULTADO QA DEL PROGRAMA 1', 'RESULTADO DE PRUEBA', NOW());

INSERT INTO producto (id, resultado_id, codigo, nombre, descripcion, fecha) VALUES
  (1, 1, '1.1', 'PRODUCTO QA DEL PROGRAMA 1', NULL, NOW());

INSERT INTO actividad (id, producto_id, codigo, nombre, descripcion, fecha) VALUES
  (1, 1, '1.1.1', 'ACTIVIDAD QA DEL PROGRAMA 1', NULL, NOW());

INSERT INTO rubro (id, actividad_id, categoria_rubro_id, tipo_rubro_id, codigo, nombre, descripcion, monto, fecha) VALUES
  (1, 1, 1, 1, '1.1.1.01', 'RUBRO QA UNO', NULL, 20000.00, NOW()),
  (2, 1, 1, 2, '1.1.1.02', 'RUBRO QA DOS', NULL,  8000.00, NOW());

-- Indicadores de la actividad 1 (base del POA Indicadores del programa 1)
INSERT INTO detalle_actividad (id, actividad_id, indicador_medido, medio_verificacion, supuesto, responsable, fecha) VALUES
  (1, 1, 10, 'REGISTRO DE PRUEBA', 'SUPUESTO DE PRUEBA', 'RESPONSABLE QA', NOW());

-- ===========================================================================
--  7) JERARQUÍA POA — PROGRAMA 5 (cross-tenant: hasta una actividad)
-- ===========================================================================
INSERT INTO resultado (id, programa_id, codigo, nombre, descripcion, fecha) VALUES
  (2, 5, '1', 'RESULTADO QA DEL PROGRAMA 5', 'RESULTADO DE PRUEBA', NOW());

INSERT INTO producto (id, resultado_id, codigo, nombre, descripcion, fecha) VALUES
  (2, 2, '1.1', 'PRODUCTO QA DEL PROGRAMA 5', NULL, NOW());

INSERT INTO actividad (id, producto_id, codigo, nombre, descripcion, fecha) VALUES
  (2, 2, '1.1.1', 'ACTIVIDAD QA DEL PROGRAMA 5', NULL, NOW());

-- ---------------------------------------------------------------------------
-- 8) POA INDICADORES id=1 del programa 5 (cross-tenant: el coordinador del
--    programa 1 no debe poder revisarlo -> 403). Estado Enviado(1); año anterior
--    para no colisionar con nada del año en curso.
-- ---------------------------------------------------------------------------
INSERT INTO poa_indicadores (id, programa_id, usuario_id, anio, estado, observacion, fecha) VALUES
  (1, 5, 6, YEAR(CURDATE()) - 1, 1, NULL, NOW());

-- ============================================================================
--  FIN — Fixture de QA cargado:
--    · 3 usuarios QA (contador + 2 coordinadores) + admin id=1 preservado
--    · programa 1 (coord. usuario 3): resultado/producto/actividad(1)/rubros(1,2)=28000,
--      sobres para fuentes 1 y 2
--    · programa 5 (coord. usuario 6): resultado/producto/actividad(2) + poa_indicadores id=1
--    · SIN poa/poa_indicadores del programa 1 del año en curso (los crean los arneses)
-- ============================================================================
