-- ============================================================================
--  seed_demo.sql — Datos de demostración para SysAI (ONG Arco Iris, Huaraz)
-- ----------------------------------------------------------------------------
--  Objetivo: poblar el sistema con un escenario REALISTA y coherente que
--  ejercite el flujo implementado hasta hoy (items 2-6):
--    · Usuarios de los 3 roles (Administrador, Contador, Coordinador)
--    · Fuentes de financiamiento = organizaciones reales de apoyo a familias
--      en Sudamérica (Alianza Solidaria, Latin Link, Compassion, Visión
--      Mundial, Tearfund).
--    · Programas "Comunidad" y "Casa Hogar", cada uno con su coordinador.
--    · Jerarquía POA: Resultado -> Producto -> Actividad -> Rubro + indicadores.
--    · POA Indicadores + POA Presupuestal en distintos estados.
--    · Rendiciones (aprobadas y pendientes) y Otros Ingresos/Egresos (OIE).
--    · Tipos de cambio USD/EUR y snapshot anual de fuentes.
--
--  ESCENARIO:
--    - COMUNIDAD  -> POA Presupuestal APROBADO(3): rendiciones APROBADAS(1)
--                    que descuentan el saldo contable + OIE del Contador.
--    - CASA HOGAR -> POA Presupuestal ENVIADO(1): coordinador bloqueado,
--                    rendiciones PENDIENTES(0) que aún NO afectan el saldo.
--
--  Requisitos previos: haber corrido schema_baseline.sql + migraciones + seed.sql
--  (deja el admin `admin@arcoiris.pe` y los catálogos base).
--
--  Es RE-EJECUTABLE: limpia los datos de demo (preservando admin y catálogos)
--  y los vuelve a insertar. Usar SOLO en entornos de desarrollo/demo.
-- ============================================================================

SET @usuario_actual = 'SEEDER_DEMO';
SET NAMES utf8;

-- ---------------------------------------------------------------------------
-- 0) Limpieza de datos de demo previos (preserva admin id=1 y catálogos)
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
-- 1) PERSONAS y USUARIOS (Contador + 2 Coordinadores)   [admin = id 1 ya existe]
--    Contraseñas (bcrypt PASSWORD_DEFAULT):
--      contador@arcoiris.pe                -> Contador2026*
--      coordinador.comunidad@arcoiris.pe   -> Comunidad2026*
--      coordinador.casahogar@arcoiris.pe   -> CasaHogar2026*
-- ---------------------------------------------------------------------------
INSERT INTO persona (id, nro_documento, apellido_paterno, apellido_materno, nombres, telefono, fecha) VALUES
  (2, 31601234, 'RONDÓN',    'VEGA',     'MARÍA ELENA',      '943215678', NOW()),
  (3, 31752980, 'MAGUIÑA',   'TARAZONA', 'JOSÉ LUIS',        '944882301', NOW()),
  (4, 32048715, 'COCHACHÍN', 'MILLA',    'ROSA ANGÉLICA',    '945770142', NOW());

INSERT INTO usuario (id, persona_id, cargo_id, descripcion, email, password, fecha) VALUES
  (2, 2, 2, 'CTB001', 'contador@arcoiris.pe',
     '$2y$10$tRtbOZnB6/e6BOqLaPo8ee6a19xO.ZLelQY69vjghVEKQuUJJe5RK', NOW()),
  (3, 3, 3, 'CRD001', 'coordinador.comunidad@arcoiris.pe',
     '$2y$10$YS2HxxHLG9hS/j.O/Ewn6OlfKVmEL23BjMN777D65/8pXj2HuYjqi', NOW()),
  (4, 4, 3, 'CRD002', 'coordinador.casahogar@arcoiris.pe',
     '$2y$10$77nIuyAtRiG/kUwqxc3Z6.7s/l01Kg7PZaE/0dbbWLlpQ73EOYowm', NOW());

-- ---------------------------------------------------------------------------
-- 2) PROGRAMAS  (tipo_programa: 1=Nacional, 2=Internacional)
-- ---------------------------------------------------------------------------
INSERT INTO programa (id, codigo, nombre, descripcion, tipo_programa_id, fecha) VALUES
  (1, 'PRG001', 'COMUNIDAD',
      'DESARROLLO INTEGRAL DE FAMILIAS EN SITUACIÓN DE VULNERABILIDAD EN LOS CASERÍOS DE HUARAZ', 2, NOW()),
  (2, 'PRG002', 'CASA HOGAR',
      'ACOGIMIENTO RESIDENCIAL Y ATENCIÓN INTEGRAL DE NIÑOS, NIÑAS Y ADOLESCENTES EN RIESGO', 2, NOW());

-- ---------------------------------------------------------------------------
-- 3) VÍNCULO COORDINADOR-PROGRAMA (un coordinador activo por programa)
-- ---------------------------------------------------------------------------
INSERT INTO coordinador_programa (id, usuario_id, programa_id, activo, fecha) VALUES
  (1, 3, 1, 1, NOW()),   -- José Luis Maguiña -> COMUNIDAD
  (2, 4, 2, 1, NOW());   -- Rosa Cochachín    -> CASA HOGAR

-- ---------------------------------------------------------------------------
-- 4) FUENTES DE FINANCIAMIENTO (organizaciones reales; presupuesto en S/)
--    OJO: fuente_financiamiento.presupuesto es decimal(8,2) => máx 999,999.99
-- ---------------------------------------------------------------------------
INSERT INTO fuente_financiamiento (id, codigo, nombre, descripcion, presupuesto, fecha) VALUES
  (1, 'FF001', 'ALIANZA SOLIDARIA',
      'RED MISIONERA DE APOYO A FAMILIAS Y COMUNIDADES EN AMÉRICA LATINA', 450000.00, NOW()),
  (2, 'FF002', 'LATIN LINK',
      'ORGANIZACIÓN INTERNACIONAL DE DESARROLLO COMUNITARIO EN LATINOAMÉRICA', 220000.00, NOW()),
  (3, 'FF003', 'COMPASSION INTERNATIONAL',
      'PROGRAMA DE APADRINAMIENTO Y DESARROLLO INTEGRAL DE LA NIÑEZ', 380000.00, NOW()),
  (4, 'FF004', 'VISIÓN MUNDIAL',
      'ORGANIZACIÓN HUMANITARIA DE AYUDA A LA NIÑEZ Y FAMILIAS (WORLD VISION)', 300000.00, NOW()),
  (5, 'FF005', 'TEARFUND',
      'ORGANIZACIÓN DE AYUDA HUMANITARIA Y DESARROLLO COMUNITARIO', 180000.00, NOW());

-- ---------------------------------------------------------------------------
-- 5) DETALLE FINANCIAMIENTO (N:M programa <-> fuente) + SUB-PRESUPUESTO ("sobre")
--    Solo las fuentes vinculadas al programa pueden usarse en rendiciones/OIE.
--    monto_asignado = porción del presupuesto de la fuente reservada al programa.
--    Invariante: Σ monto_asignado por fuente <= fuente.presupuesto (resto = remanente).
--    (migración 020)
-- ---------------------------------------------------------------------------
INSERT INTO detalle_financiamiento (id, programa_id, fuente_financiamiento_id, monto_asignado, fecha) VALUES
  (1, 1, 1, 300000.00, NOW()),   -- COMUNIDAD  <- Alianza Solidaria      (sobre 300k de 450k; remanente 150k)
  (2, 1, 3, 250000.00, NOW()),   -- COMUNIDAD  <- Compassion International (sobre 250k de 380k; remanente 130k)
  (3, 2, 2, 150000.00, NOW()),   -- CASA HOGAR <- Latin Link             (sobre 150k de 220k; remanente 70k)
  (4, 2, 4, 200000.00, NOW()),   -- CASA HOGAR <- Visión Mundial         (sobre 200k de 300k; remanente 100k)
  (5, 2, 5, 120000.00, NOW());   -- CASA HOGAR <- Tearfund               (sobre 120k de 180k; remanente 60k)

-- ===========================================================================
--  6) JERARQUÍA POA — PROGRAMA 1: COMUNIDAD
-- ===========================================================================
INSERT INTO resultado (id, programa_id, codigo, nombre, descripcion, fecha) VALUES
  (1, 1, 'RES001', 'FAMILIAS FORTALECEN SUS CAPACIDADES ECONÓMICAS Y PRODUCTIVAS',
        'RESULTADO ORIENTADO A LA GENERACIÓN DE INGRESOS FAMILIARES', NOW()),
  (2, 1, 'RES002', 'COMUNIDAD MEJORA SU ACCESO A SERVICIOS BÁSICOS DE SALUD',
        'RESULTADO ORIENTADO A LA SALUD PREVENTIVA COMUNITARIA', NOW());

INSERT INTO producto (id, resultado_id, codigo, nombre, descripcion, fecha) VALUES
  (1, 1, 'PRD001', 'FAMILIAS CAPACITADAS EN EMPRENDIMIENTO Y GESTIÓN FAMILIAR', NULL, NOW()),
  (2, 1, 'PRD002', 'FAMILIAS IMPLEMENTAN HUERTOS FAMILIARES SOSTENIBLES', NULL, NOW()),
  (3, 2, 'PRD003', 'POBLACIÓN ACCEDE A JORNADAS DE SALUD PREVENTIVA', NULL, NOW());

INSERT INTO actividad (id, producto_id, codigo, nombre, descripcion, fecha) VALUES
  (1, 1, 'ACT001', 'EJECUTAR TALLERES DE CAPACITACIÓN EN EMPRENDIMIENTO FAMILIAR', NULL, NOW()),
  (2, 2, 'ACT002', 'BRINDAR ASISTENCIA TÉCNICA EN INSTALACIÓN DE HUERTOS FAMILIARES', NULL, NOW()),
  (3, 3, 'ACT003', 'REALIZAR CAMPAÑAS DE SALUD PREVENTIVA EN LA COMUNIDAD', NULL, NOW());

-- Rubros (categoria_rubro_id 1..10; tipo_rubro_id: 1=Bien, 2=Servicio)
INSERT INTO rubro (id, actividad_id, categoria_rubro_id, tipo_rubro_id, codigo, nombre, descripcion, monto, fecha) VALUES
  (1, 1, 7, 2, 'RUB001', 'HONORARIOS DE FACILITADORES DE TALLERES',      NULL, 12000.00, NOW()),
  (2, 1, 2, 1, 'RUB002', 'KITS Y MATERIALES DE CAPACITACIÓN',            NULL,  4500.00, NOW()),
  (3, 2, 1, 1, 'RUB003', 'SEMILLAS, PLANTONES E INSUMOS AGRÍCOLAS',      NULL,  6000.00, NOW()),
  (4, 3, 8, 2, 'RUB004', 'SERVICIOS PROFESIONALES DE SALUD',             NULL,  8000.00, NOW()),
  (5, 3, 4, 2, 'RUB005', 'VIÁTICOS Y TRANSPORTE DE BRIGADA DE SALUD',    NULL,  3500.00, NOW());
-- Presupuesto POA COMUNIDAD = 12000 + 4500 + 6000 + 8000 + 3500 = 34000.00

-- ===========================================================================
--  7) JERARQUÍA POA — PROGRAMA 2: CASA HOGAR
-- ===========================================================================
INSERT INTO resultado (id, programa_id, codigo, nombre, descripcion, fecha) VALUES
  (3, 2, 'RES003', 'NIÑOS Y ADOLESCENTES EN ACOGIMIENTO RECIBEN ATENCIÓN INTEGRAL',
        'RESULTADO ORIENTADO AL BIENESTAR DE LOS RESIDENTES', NOW()),
  (4, 2, 'RES004', 'INFRAESTRUCTURA DE LA CASA HOGAR MANTENIDA Y SEGURA',
        'RESULTADO ORIENTADO A AMBIENTES DIGNOS Y SEGUROS', NOW());

INSERT INTO producto (id, resultado_id, codigo, nombre, descripcion, fecha) VALUES
  (4, 3, 'PRD004', 'RESIDENTES RECIBEN ALIMENTACIÓN BALANCEADA DIARIA', NULL, NOW()),
  (5, 3, 'PRD005', 'RESIDENTES CUENTAN CON APOYO EDUCATIVO Y PSICOLÓGICO', NULL, NOW()),
  (6, 4, 'PRD006', 'AMBIENTES DE LA CASA HOGAR EQUIPADOS Y EN BUEN ESTADO', NULL, NOW());

INSERT INTO actividad (id, producto_id, codigo, nombre, descripcion, fecha) VALUES
  (4, 4, 'ACT004', 'PROVEER ALIMENTACIÓN DIARIA A LOS RESIDENTES', NULL, NOW()),
  (5, 5, 'ACT005', 'CONTRATAR SERVICIO DE APOYO PSICOPEDAGÓGICO', NULL, NOW()),
  (6, 6, 'ACT006', 'EJECUTAR MANTENIMIENTO Y EQUIPAMIENTO DE DORMITORIOS', NULL, NOW());

INSERT INTO rubro (id, actividad_id, categoria_rubro_id, tipo_rubro_id, codigo, nombre, descripcion, monto, fecha) VALUES
  (6, 4, 6, 1, 'RUB006', 'VÍVERES Y ALIMENTOS DE PRIMERA NECESIDAD',        NULL, 18000.00, NOW()),
  (7, 5, 7, 2, 'RUB007', 'HONORARIOS DE PSICÓLOGO Y TUTOR PEDAGÓGICO',      NULL, 14400.00, NOW()),
  (8, 6, 3, 1, 'RUB008', 'MANTENIMIENTO E IMPLEMENTACIÓN DE AMBIENTES',     NULL,  9000.00, NOW());
-- Presupuesto POA CASA HOGAR = 18000 + 14400 + 9000 = 41400.00

-- ---------------------------------------------------------------------------
-- 8) INDICADORES DE ACTIVIDAD (base del POA Indicadores)
-- ---------------------------------------------------------------------------
INSERT INTO detalle_actividad (id, actividad_id, indicador_medido, medio_verificacion, supuesto, responsable, fecha) VALUES
  (1, 1, 40, 'LISTAS DE ASISTENCIA Y REGISTRO FOTOGRÁFICO', 'LAS FAMILIAS MANTIENEN DISPONIBILIDAD PARA CAPACITARSE', 'COORDINADOR DEL PROGRAMA', NOW()),
  (2, 2, 30, 'FICHAS DE VISITA TÉCNICA Y ACTA DE ENTREGA',   'CONDICIONES CLIMÁTICAS FAVORABLES PARA LOS HUERTOS',    'TÉCNICO AGROPECUARIO',      NOW()),
  (3, 3, 200,'REGISTRO DE ATENCIONES Y FICHAS DE SALUD',      'LA POBLACIÓN ASISTE A LAS JORNADAS PROGRAMADAS',       'COORDINADOR DEL PROGRAMA', NOW()),
  (4, 4, 25, 'REPORTES DE RACIONES Y KÁRDEX DE ALMACÉN',      'PROVEEDORES CUMPLEN CON LA ENTREGA OPORTUNA',          'ADMINISTRADOR DE LA CASA HOGAR', NOW()),
  (5, 5, 25, 'INFORMES PSICOPEDAGÓGICOS MENSUALES',           'LOS RESIDENTES PARTICIPAN DE LAS SESIONES',           'COORDINADOR DEL PROGRAMA', NOW()),
  (6, 6, 4,  'ACTAS DE MANTENIMIENTO Y FOTOGRAFÍAS',          'DISPONIBILIDAD DE PROVEEDORES LOCALES',                'ADMINISTRADOR DE LA CASA HOGAR', NOW());

-- ---------------------------------------------------------------------------
-- 9) POA INDICADORES (estado: 0=Borrador,1=Enviado,2=Observado,3=Aprobado)
--    Ambos aprobados: el POA Indicadores precede al Presupuestal.
-- ---------------------------------------------------------------------------
INSERT INTO poa_indicadores (id, programa_id, usuario_id, anio, estado, observacion, fecha) VALUES
  (1, 1, 3, '2026', 3, NULL, NOW()),   -- COMUNIDAD  (coord. José Luis) - APROBADO
  (2, 2, 4, '2026', 3, NULL, NOW());   -- CASA HOGAR (coord. Rosa)      - APROBADO

-- ---------------------------------------------------------------------------
-- 10) POA PRESUPUESTAL (estado: 0=Borrador,1=Enviado,2=Observado,3=Aprobado)
--     presupuesto = Σ rubro.monto del programa (congelado al enviar).
-- ---------------------------------------------------------------------------
INSERT INTO poa (id, programa_id, anio, presupuesto, estado, observacion, usuario_id, fecha) VALUES
  (1, 1, '2026', 34000.00, 3, NULL, 3, NOW()),   -- COMUNIDAD  - APROBADO(3)
  (2, 2, '2026', 41400.00, 1, NULL, 4, NOW());   -- CASA HOGAR - ENVIADO(1)

-- ---------------------------------------------------------------------------
-- 11) RENDICIONES
--     estado: 0=Pendiente, 1=Aprobada. ff_id debe estar vinculada al programa.
--     Σ rendiciones por rubro <= rubro.monto.
--     COMUNIDAD (POA aprobado)  -> rendiciones APROBADAS(1) (descuentan saldo).
--     CASA HOGAR (POA enviado)  -> rendiciones PENDIENTES(0) (no afectan saldo).
-- ---------------------------------------------------------------------------
-- --- COMUNIDAD (fuentes válidas: 1=Alianza, 3=Compassion) ---
INSERT INTO rendicion
  (id, rubro_id, tipo_comprobante_id, ff_id, codigo, serie, numero, detalle, descripcion, ruc, razon_social, monto, estado, poa_rendicion_id, fecha_original, fecha) VALUES
  (1, 1, 1, 1, 'REN001', 'F001', '0001234', 'PAGO A FACILITADOR TALLER DE EMPRENDIMIENTO - MÓDULO I',  NULL, '20481234567', 'CONSULTORA DE DESARROLLO ANDINO S.A.C.', 4000.00, 1, NULL, '2026-03-15', NOW()),
  (2, 1, 4, 1, 'REN002', 'E001', '0000045', 'HONORARIOS FACILITADOR TALLER - MÓDULO II',              NULL, '10327654321', 'MAGUIÑA VIDAL CARLOS ALBERTO',           3500.00, 1, NULL, '2026-04-10', NOW()),
  (3, 2, 2, 3, 'REN003', 'B001', '0004521', 'COMPRA DE KITS Y MATERIALES DE CAPACITACIÓN',            NULL, '20510987654', 'COMERCIAL LIBRERÍA SANTA ROSA E.I.R.L.', 2800.00, 1, NULL, '2026-03-20', NOW()),
  (4, 3, 1, 3, 'REN004', 'F002', '0000876', 'ADQUISICIÓN DE SEMILLAS E INSUMOS AGRÍCOLAS',            NULL, '20486549871', 'AGROVETERINARIA EL CAMPO S.A.C.',        3200.00, 1, NULL, '2026-04-05', NOW()),
  (5, 4, 4, 1, 'REN005', 'E002', '0000112', 'SERVICIOS PROFESIONALES CAMPAÑA DE SALUD',               NULL, '10453218769', 'COCHACHÍN ROSALES MARÍA ISABEL',         5000.00, 1, NULL, '2026-05-12', NOW()),
  (6, 5, 5, 1, 'REN006', 'V001', '0000078', 'TRANSPORTE Y VIÁTICOS BRIGADA DE SALUD A CASERÍOS',      NULL, '20531267840', 'TRANSPORTES ANDINOS HUARAZ S.A.C.',      1200.00, 1, NULL, '2026-05-13', NOW());
-- --- CASA HOGAR (fuentes válidas: 2=Latin Link, 4=Visión Mundial, 5=Tearfund) ---
INSERT INTO rendicion
  (id, rubro_id, tipo_comprobante_id, ff_id, codigo, serie, numero, detalle, descripcion, ruc, razon_social, monto, estado, poa_rendicion_id, fecha_original, fecha) VALUES
  (7, 6, 1, 4, 'REN007', 'F003', '0002145', 'COMPRA MENSUAL DE VÍVERES - MARZO',                      NULL, '20492817364', 'DISTRIBUIDORA DE ALIMENTOS EL SOL S.A.C.', 6500.00, 0, NULL, '2026-03-31', NOW()),
  (8, 7, 4, 2, 'REN008', 'E003', '0000203', 'HONORARIOS PSICÓLOGO - MARZO Y ABRIL',                   NULL, '10419283746', 'TARAZONA MILLA JUAN CARLOS',               4800.00, 0, NULL, '2026-04-30', NOW());

-- ---------------------------------------------------------------------------
-- 12) OTROS INGRESOS / EGRESOS (OIE) — solo los registra el Contador.
--     Aprobación automática: afectan el saldo contable de inmediato.
--     El monto vive en oie_comprobante.monto. oie_tipo: 1=Ingreso, 2=Egreso.
--     (Programa COMUNIDAD; fuentes vinculadas: 1=Alianza, 3=Compassion)
-- ---------------------------------------------------------------------------
INSERT INTO oie_comprobante (id, oie_tipo_comprobante_id, serie, numero, descripcion, ruc, razon_social, monto, fecha_original, fecha) VALUES
  (1, 1, 'F010', '0000500', 'DONACIÓN EXTRAORDINARIA PARA EQUIPAMIENTO', '20100066603', 'ALIANZA SOLIDARIA - OFICINA REGIONAL',   15000.00, '2026-02-01', NOW()),
  (2, 4, 'E020', '0000015', 'GASTO ADMINISTRATIVO NO CONTEMPLADO EN POA','10412345678', 'ASESORÍA CONTABLE INDEPENDIENTE',         2200.00, '2026-06-15', NOW());

INSERT INTO otros_ingresos_egresos (id, programa_id, oie_comprobante_id, oie_tipo_id, ff_id, codigo, descripcion, fecha) VALUES
  (1, 1, 1, 1, 1, 'OIE001', 'INGRESO POR DONACIÓN EXTRAORDINARIA DE ALIANZA SOLIDARIA', NOW()),
  (2, 1, 2, 2, 3, 'OIE002', 'EGRESO ADMINISTRATIVO FUERA DEL POA',                     NOW());

-- ---------------------------------------------------------------------------
-- 13) TIPOS DE CAMBIO (se usa el último registrado). Los registra el Contador.
-- ---------------------------------------------------------------------------
INSERT INTO tipo_cambio_dolar (id, usuario_id, tipo_cambio, fecha) VALUES
  (1, 2, 3.75, NOW());
INSERT INTO tipo_cambio_euro (id, usuario_id, tipo_cambio, fecha) VALUES
  (1, 2, 4.05, NOW());

-- ---------------------------------------------------------------------------
-- 14) SNAPSHOT ANUAL DE FUENTES (histórico / cierre anual — uso futuro v1.1)
--     monto_inicial = presupuesto de apertura 2026 de cada fuente.
--     comprometido/contable se recalculan cuando se implemente el módulo de
--     Saldos (item 8); las vistas de saldo actuales leen en vivo de las tablas.
-- ---------------------------------------------------------------------------
INSERT INTO fuente_presupuesto_anual (id, fuente_financiamiento_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable, fecha) VALUES
  (1, 1, '2026', 450000.00, 0.00, 0.00, NOW()),
  (2, 2, '2026', 220000.00, 0.00, 0.00, NOW()),
  (3, 3, '2026', 380000.00, 0.00, 0.00, NOW()),
  (4, 4, '2026', 300000.00, 0.00, 0.00, NOW()),
  (5, 5, '2026', 180000.00, 0.00, 0.00, NOW());

-- ============================================================================
--  FIN — Resumen del escenario cargado:
--    · 4 usuarios (1 admin, 1 contador, 2 coordinadores)
--    · 2 programas, 5 fuentes, 5 vínculos de financiamiento
--    · 4 resultados, 6 productos, 6 actividades, 8 rubros, 6 indicadores
--    · 2 POA Indicadores (aprobados) + 2 POA Presupuestales (1 aprobado, 1 enviado)
--    · 8 rendiciones (6 aprobadas COMUNIDAD, 2 pendientes CASA HOGAR)
--    · 2 OIE (1 ingreso, 1 egreso) + tipos de cambio USD/EUR
-- ============================================================================
