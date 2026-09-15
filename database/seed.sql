-- =============================================================================
-- SysAI · Arco Iris — Seed de despliegue desde cero
-- =============================================================================
-- Datos de catálogo (reference data). SIN usuarios: el administrador inicial se
-- crea con database/crear_admin.php (2026-09-14).
-- NO contiene datos transaccionales (programas, POAs, rendiciones, OIE).
--
-- Orden de despliegue desde cero (ver database/README.md):
--   1) schema_baseline.sql   (estructura, sin datos)
--   2) php database/migrate.php  (migraciones pendientes)
--   3) database/seed.sql     (este archivo)
--   4) php database/crear_admin.php --email <correo real> ... --probar-correo
--
-- Idempotente: todo va con INSERT IGNORE (claves UNIQUE) → re-ejecutar es seguro.
-- El catálogo tipo_comprobante refleja el estado FINAL (TCM002 = "Boleta de
-- venta"), coherente con la migración 013 sin importar el orden seed↔migrate.
--
-- Convención: los catálogos sembrados van en MAYÚSCULAS (estilo histórico del
-- dominio); email en minúsculas. Desde B4 (2026-07-16) la app ya NO fuerza
-- mayúsculas: los datos del usuario se guardan tal como se ingresan.
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

-- Códigos CAT### para coincidir con el generador (CategoriaRubro::siguienteCodigo →
-- 'CAT'); así las categorías creadas después continúan la secuencia (CAT011, ...).
INSERT IGNORE INTO `categoria_rubro` (`id`, `subcategoria_rubro_id`, `codigo`, `nombre`, `descripcion`, `fecha`) VALUES
  (1,  1, 'CAT001', 'Asistencia técnica',          '',                                NOW()),
  (2,  1, 'CAT002', 'Equipamiento',                '',                                NOW()),
  (3,  1, 'CAT003', 'Infraestructura',             '',                                NOW()),
  (4,  1, 'CAT004', 'Desplazamiento',              'Viajes y viáticos',               NOW()),
  (5,  1, 'CAT005', 'Monitoreo y evaluación',      '',                                NOW()),
  (6,  1, 'CAT006', 'Otros',                       '',                                NOW()),
  (7,  2, 'CAT007', 'Personal',                    'Permanente',                      NOW()),
  (8,  2, 'CAT008', 'Servicios de terceros',       'Contabilidad y asesoría',         NOW()),
  (9,  2, 'CAT009', 'Implementación de oficina',   'Mobiliario o equipo informático', NOW()),
  (10, 2, 'CAT010', 'Administración del proyecto', 'Servicios y otros',               NOW());

-- ---------------------------------------------------------------------------
-- Administrador inicial: YA NO se siembra aquí (2026-09-14)
-- ---------------------------------------------------------------------------
-- Antes se insertaba admin@arcoiris.pe con una contraseña escrita en este archivo:
-- quedaba publicada en el repositorio y ese buzón no existe, así que el admin no podía
-- activar su cuenta por correo. Ahora:
--   php database/crear_admin.php --email <correo real> --nombres "..." \
--       --apellido-paterno "..." --dni <numero> --probar-correo
-- que crea el admin con contraseña aleatoria oculta y le envía el código de activación.
-- Los fixtures de desarrollo (seed_demo.sql, seed_qa.sql) crean su propio admin id=1.
