-- =============================================================================
-- SysAI · Arco Iris — Seed de despliegue desde cero
-- =============================================================================
-- Datos de catálogo (reference data) + usuario administrador inicial.
-- NO contiene datos transaccionales (programas, POAs, rendiciones, OIE).
--
-- Orden de despliegue desde cero (ver database/README.md):
--   1) schema_baseline.sql   (estructura, sin datos)
--   2) php database/migrate.php  (migraciones 001-013)
--   3) database/seed.sql     (este archivo)
--
-- Idempotente: todo va con INSERT IGNORE (claves UNIQUE) → re-ejecutar es seguro.
-- El catálogo tipo_comprobante refleja el estado FINAL (TCM002 = "Boleta de
-- venta"), coherente con la migración 013 sin importar el orden seed↔migrate.
--
-- Convención: textos de dominio en MAYÚSCULAS (como fuerza la app); email en
-- minúsculas (excepción declarada en ActiveRecord::$columnasSinMayuscula).
-- =============================================================================

-- ---------------------------------------------------------------------------
-- Catálogos
-- ---------------------------------------------------------------------------

-- Roles (cargo_id usado por el front controller para cargar rutas por rol)
INSERT IGNORE INTO `cargo` (`id`, `descripcion`) VALUES
  (1, 'Administrador'),
  (2, 'Contador'),
  (3, 'Coordinador');

-- Tipo de programa
INSERT IGNORE INTO `tipo_programa` (`id`, `descripcion`, `fecha`) VALUES
  (1, 'Nacional',      NOW()),
  (2, 'Internacional', NOW());

-- Tipo de rubro (Bien / Servicio)
INSERT IGNORE INTO `tipo_rubro` (`id`, `codigo`, `nombre`, `descripcion`) VALUES
  (1, 'TRB001', 'Bien',     ''),
  (2, 'TRB002', 'Servicio', '');

-- Tipo de movimiento OIE
INSERT IGNORE INTO `oie_tipo` (`id`, `nombre`) VALUES
  (1, 'Ingreso'),
  (2, 'Egreso');

-- Tipo de comprobante para OIE
INSERT IGNORE INTO `oie_tipo_comprobante` (`id`, `codigo`, `nombre`) VALUES
  (1, 'OTC00001', 'Factura'),
  (2, 'OTC00002', 'Boleta'),
  (3, 'OTC00003', 'Declaración jurada'),
  (4, 'OTC00004', 'Recibo por Honorarios');

-- Tipo de comprobante para rendiciones (estado final — incluye split de boleta
-- y recibos formalizados; ver decisión 2026-06-03 y migración 013)
INSERT IGNORE INTO `tipo_comprobante` (`id`, `codigo`, `descripcion`) VALUES
  (1,  'TCM001', 'Factura'),
  (2,  'TCM002', 'Boleta de venta'),
  (3,  'TCM003', 'Declaración Jurada'),
  (4,  'TCM004', 'Recibo por honorarios'),
  (5,  'TCM005', 'Boleta de viaje'),
  (6,  'TCM006', 'Recibo de caja'),
  (7,  'TCM007', 'Recibo de servicio básico'),
  (8,  'TCM008', 'Recibo de viaje'),
  (9,  'TCM009', 'Recibo de pago de servicios'),
  (10, 'TCM010', 'Recibo general');

-- Subcategorías y categorías de rubro (subcategoria primero por la FK)
INSERT IGNORE INTO `subcategoria_rubro` (`id`, `codigo`, `nombre`, `descripcion`, `fecha`) VALUES
  (1, 'SCR001', 'Costos directos',   '', NOW()),
  (2, 'SCR002', 'Costos indirectos', '', NOW());

INSERT IGNORE INTO `categoria_rubro` (`id`, `subcategoria_rubro_id`, `codigo`, `nombre`, `descripcion`, `fecha`) VALUES
  (1,  1, 'CRB001', 'Asistencia técnica',          '',                                NOW()),
  (2,  1, 'CRB002', 'Equipamiento',                '',                                NOW()),
  (3,  1, 'CRB003', 'Infraestructura',             '',                                NOW()),
  (4,  1, 'CRB004', 'Desplazamiento',              'Viajes y viáticos',               NOW()),
  (5,  1, 'CRB005', 'Monitoreo y evaluación',      '',                                NOW()),
  (6,  1, 'CRB006', 'Otros',                       '',                                NOW()),
  (7,  2, 'CRB007', 'Personal',                    'Permanente',                      NOW()),
  (8,  2, 'CRB008', 'Servicios de terceros',       'Contabilidad y asesoría',         NOW()),
  (9,  2, 'CRB009', 'Implementación de oficina',   'Mobiliario o equipo informático', NOW()),
  (10, 2, 'CRB010', 'Administración del proyecto', 'Servicios y otros',               NOW());

-- ---------------------------------------------------------------------------
-- Usuario administrador inicial (bootstrap)
-- ---------------------------------------------------------------------------
-- ⚠️ Credenciales TEMPORALES — cambiar en el primer acceso.
--   email:    admin@arcoiris.pe
--   password: Arcoiris2026*   (hash bcrypt embebido abajo)
-- El hash corresponde a 'Arcoiris2026*'. Para regenerarlo:
--   php -r "echo password_hash('NUEVA', PASSWORD_BCRYPT);"
INSERT IGNORE INTO `persona`
  (`id`, `nro_documento`, `apellido_paterno`, `apellido_materno`, `nombres`, `telefono`, `fecha`) VALUES
  (1, 99999999, 'ADMINISTRADOR', 'SISTEMA', 'ADMIN', NULL, NOW());

INSERT IGNORE INTO `usuario`
  (`id`, `persona_id`, `cargo_id`, `descripcion`, `email`, `password`, `fecha`) VALUES
  (1, 1, 1, 'ADM001', 'admin@arcoiris.pe',
   '$2y$10$o2eDTocv2MTDD/DdXGFb9.9vjLxMXdGvjZ.zF.1P.PSS7xSTMrEEm', NOW());
