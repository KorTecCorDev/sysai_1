-- =============================================================================
-- SysAI · Arco Iris — Dejar la base EN BLANCO para la capacitación
-- =============================================================================
-- Deja la base con lo mínimo imprescindible para que la aplicación arranque y
-- se pueda llenar EN VIVO durante la sesión:
--
--   ✓ Los catálogos            (cargo, tipos, categorías de rubro…)
--   ✓ El programa INSTITUCIONAL (PRG000, es_institucional = 1)
--   ✓ El/los usuario(s) Administrador (cargo_id = 1) con su persona
--   ✓ Los tipos de cambio      (ver el aviso de la sección 3 — NO borrarlos a la ligera)
--   ✓ schema_migrations        (intacta: el esquema no cambia, solo los datos)
--
--   ✗ Todo lo demás: programas, coordinadores, contadores, fuentes, sobres,
--     POAs, resultados/productos/actividades/rubros, rendiciones, OIE,
--     transferencias, cierres anuales, auditoría y contadores de rate-limit.
--
-- -----------------------------------------------------------------------------
-- ⚠️ ESTO BORRA DATOS. Toma un respaldo ANTES:
--     powershell -File database/respaldo.ps1 guardar -Nota antes-de-limpiar
--
-- USO
--     "C:\xampp\mysql\bin\mysql.exe" -u root sysai < database/preparar_base_capacitacion.sql
--
-- Es RE-EJECUTABLE: correrlo dos veces deja el mismo resultado.
--
-- -----------------------------------------------------------------------------
-- POR QUÉ NO ES UNA MIGRACIÓN DE database/migrations/
-- -----------------------------------------------------------------------------
-- Las migraciones describen el ESQUEMA y el runner las anota en
-- `schema_migrations` para no repetirlas. Un borrado de datos no encaja ahí por
-- dos razones concretas:
--   1) Se aplicaría UNA sola vez. Entre tanda y tanda de la capacitación hay
--      que volver a limpiar, y el runner ya la daría por hecha.
--   2) Quedaría enganchada a la cadena de despliegue: el próximo despliegue
--      greenfield en Hostinger la ejecutaría sin que nadie lo pida.
-- Por eso vive suelto en database/ y se ejecuta a mano, a propósito.
--
-- Para RE-CREAR el escenario en vez de vaciarlo: database/seed_demo.sql.
-- Para dar de alta a los participantes: php database/preparar_capacitacion.php
-- =============================================================================


-- -----------------------------------------------------------------------------
-- 1. Movimientos y planificación — se vacían por completo
-- -----------------------------------------------------------------------------
-- Orden hijo → padre, respetando las claves foráneas. NO se desactiva
-- FOREIGN_KEY_CHECKS a propósito: si algún DELETE falla por una FK, es que el
-- esquema tiene una relación que este script no contempla, y quiero enterarme
-- aquí y no a mitad de la capacitación.

START TRANSACTION;

-- Rendiciones y sus dependencias (rendicion → rubro, ff, poa_rendicion)
DELETE FROM `rendicion`;

-- Otros Ingresos / Egresos (el monto vive en el comprobante)
DELETE FROM `otros_ingresos_egresos`;
DELETE FROM `oie_comprobante`;

-- Rubros e indicadores colgados de la actividad
DELETE FROM `rubro`;
DELETE FROM `indicador_actividad`;
DELETE FROM `detalle_actividad`;
DELETE FROM `avance_actividad`;

-- Jerarquía de planificación: Actividad → Producto → Resultado
DELETE FROM `actividad`;
DELETE FROM `avance_producto`;
DELETE FROM `indicador_producto`;
DELETE FROM `producto_detalle`;
DELETE FROM `producto`;
DELETE FROM `avance_resultado`;
DELETE FROM `indicador_resultado`;
DELETE FROM `resultado_detalle`;
DELETE FROM `resultado`;

-- Documentos POA (los tres: presupuestal, indicadores y el vestigial de rendición)
DELETE FROM `poa`;
DELETE FROM `poa_indicadores`;
DELETE FROM `poa_rendicion`;

-- Dinero: sobres, transferencias, cierres anuales y las fuentes mismas
DELETE FROM `transferencia_institucional`;
DELETE FROM `detalle_financiamiento`;
DELETE FROM `fuente_presupuesto_anual`;
DELETE FROM `fuente_financiamiento`;

-- Vínculo coordinador ↔ programa
DELETE FROM `coordinador_programa`;


-- -----------------------------------------------------------------------------
-- 2. Programas, usuarios y personas — borrado PARCIAL
-- -----------------------------------------------------------------------------

-- 2.1 Programas: sobrevive solo el INSTITUCIONAL, identificado SIEMPRE por el
--     flag (nunca por nombre ni por id — convención del proyecto, migr. 033).
--     Si por lo que sea no existiera, la sección 4 lo vuelve a sembrar.
DELETE FROM `programa` WHERE `es_institucional` = 0;

-- 2.2 Usuarios: sobreviven los Administradores (cargo_id = 1). Se van los
--     contadores (2) y coordinadores (3) — los de la capacitación se dan de alta
--     en vivo con `php database/preparar_capacitacion.php`.
--     Sus contraseñas NO se tocan: el admin entra con la que ya usa.
DELETE FROM `usuario` WHERE `cargo_id` <> 1;

-- 2.3 Personas huérfanas (las de los usuarios recién borrados).
DELETE FROM `persona`
 WHERE `id` NOT IN (SELECT `persona_id` FROM `usuario`);

-- 2.4 OPCIONAL — el nombre real del administrador.
--     La persona del admin viene del fixture de QA: se llama "ANA DEMO ADMIN"
--     con documento 10000001. Ese nombre es el que la aplicación muestra en
--     /usuario/admin y en la cabecera durante la sesión. Si prefieres que los
--     participantes vean tu nombre real, descomenta y rellena:
--
-- UPDATE `persona` p
--   JOIN `usuario` u ON u.`persona_id` = p.`id`
--    SET p.`nombres`          = 'TU NOMBRE',
--        p.`apellido_paterno` = 'APELLIDO PATERNO',
--        p.`apellido_materno` = 'APELLIDO MATERNO',
--        p.`nro_documento`    = 12345678,
--        p.`telefono`         = '999888777'
--  WHERE u.`email` = 'robertokar97@gmail.com';


-- -----------------------------------------------------------------------------
-- 3. Rastros de sesión y de auditoría
-- -----------------------------------------------------------------------------
-- Los contadores de rate-limit se vacían a propósito: `login_intentos` bloquea
-- tras 5 fallos en 5 min y `recuperacion_intentos` corta a 5 pedidos de código
-- por IP cada 15 min. Empezar la sesión con contadores heredados de las pruebas
-- puede dejar fuera a un participante sin explicación visible en pantalla.
DELETE FROM `login_intentos`;
DELETE FROM `recuperacion_intentos`;
DELETE FROM `auditoria`;

-- Tokens de recuperación a medio usar de cualquier admin que sobreviva.
UPDATE `usuario` SET `reset_token` = NULL, `reset_token_expira` = NULL;

-- ⚠️ `tipo_cambio` NO se vacía, y es deliberado.
-- Sin un TC vigente, toda rendición nace "pendiente de TC" (tc_usd/tc_eur NULL)
-- y el Contador NO PUEDE APROBAR EL POA (resultado=22). La capacitación se
-- frenaría justo en el paso de aprobación, con un mensaje que nadie sabría leer.
-- Si aun así quieres que el Contador los registre en vivo como parte del
-- ejercicio, descomenta la línea siguiente Y enséñale /tcambio ANTES de que
-- alguien registre rendiciones:
-- DELETE FROM `tipo_cambio`;

COMMIT;


-- -----------------------------------------------------------------------------
-- 4. Red de seguridad: el programa INSTITUCIONAL debe existir
-- -----------------------------------------------------------------------------
-- Misma siembra que la migración 033. INSERT IGNORE → no hace nada si ya está.
INSERT IGNORE INTO `programa` (`codigo`, `nombre`, `descripcion`, `fecha`, `tipo_programa_id`, `es_institucional`)
VALUES ('PRG000', 'INSTITUCIONAL', 'Programa institucional: gastos de oficina y administrativos.', NOW(), 1, 1);


-- -----------------------------------------------------------------------------
-- 5. Reinicio de los contadores AUTO_INCREMENT
-- -----------------------------------------------------------------------------
-- Cosmético pero útil en vivo: los ids arrancan en 1 y las pantallas no muestran
-- números heredados de las pruebas. Va FUERA de la transacción porque ALTER TABLE
-- hace COMMIT implícito en MySQL.
-- `programa`, `usuario` y `persona` NO se reinician: conservan filas, y MySQL
-- ajustaría el contador a MAX(id)+1 de todos modos.
ALTER TABLE `rendicion`                   AUTO_INCREMENT = 1;
ALTER TABLE `otros_ingresos_egresos`      AUTO_INCREMENT = 1;
ALTER TABLE `oie_comprobante`             AUTO_INCREMENT = 1;
ALTER TABLE `rubro`                       AUTO_INCREMENT = 1;
ALTER TABLE `indicador_actividad`         AUTO_INCREMENT = 1;
ALTER TABLE `detalle_actividad`           AUTO_INCREMENT = 1;
ALTER TABLE `avance_actividad`            AUTO_INCREMENT = 1;
ALTER TABLE `actividad`                   AUTO_INCREMENT = 1;
ALTER TABLE `avance_producto`             AUTO_INCREMENT = 1;
ALTER TABLE `indicador_producto`          AUTO_INCREMENT = 1;
ALTER TABLE `producto_detalle`            AUTO_INCREMENT = 1;
ALTER TABLE `producto`                    AUTO_INCREMENT = 1;
ALTER TABLE `avance_resultado`            AUTO_INCREMENT = 1;
ALTER TABLE `indicador_resultado`         AUTO_INCREMENT = 1;
ALTER TABLE `resultado_detalle`           AUTO_INCREMENT = 1;
ALTER TABLE `resultado`                   AUTO_INCREMENT = 1;
ALTER TABLE `poa`                         AUTO_INCREMENT = 1;
ALTER TABLE `poa_indicadores`             AUTO_INCREMENT = 1;
ALTER TABLE `poa_rendicion`               AUTO_INCREMENT = 1;
ALTER TABLE `transferencia_institucional` AUTO_INCREMENT = 1;
ALTER TABLE `detalle_financiamiento`      AUTO_INCREMENT = 1;
ALTER TABLE `fuente_presupuesto_anual`    AUTO_INCREMENT = 1;
ALTER TABLE `fuente_financiamiento`       AUTO_INCREMENT = 1;
ALTER TABLE `coordinador_programa`        AUTO_INCREMENT = 1;
ALTER TABLE `login_intentos`              AUTO_INCREMENT = 1;
ALTER TABLE `recuperacion_intentos`       AUTO_INCREMENT = 1;
ALTER TABLE `auditoria`                   AUTO_INCREMENT = 1;


-- -----------------------------------------------------------------------------
-- 6. Verificación — debe salir todo en 0 salvo lo que se conserva
-- -----------------------------------------------------------------------------
SELECT 'programas (solo el institucional)' AS comprobacion, COUNT(*) AS filas FROM `programa`
UNION ALL SELECT '  ...de ellos institucional',  COUNT(*) FROM `programa` WHERE `es_institucional` = 1
UNION ALL SELECT 'usuarios (solo administradores)', COUNT(*) FROM `usuario`
UNION ALL SELECT '  ...de ellos cargo_id = 1',   COUNT(*) FROM `usuario` WHERE `cargo_id` = 1
UNION ALL SELECT 'personas',                     COUNT(*) FROM `persona`
UNION ALL SELECT 'tipos de cambio (se conservan)', COUNT(*) FROM `tipo_cambio`
UNION ALL SELECT 'fuentes de financiamiento',    COUNT(*) FROM `fuente_financiamiento`
UNION ALL SELECT 'sobres',                       COUNT(*) FROM `detalle_financiamiento`
UNION ALL SELECT 'POAs presupuestales',          COUNT(*) FROM `poa`
UNION ALL SELECT 'POAs de indicadores',          COUNT(*) FROM `poa_indicadores`
UNION ALL SELECT 'resultados',                   COUNT(*) FROM `resultado`
UNION ALL SELECT 'productos',                    COUNT(*) FROM `producto`
UNION ALL SELECT 'actividades',                  COUNT(*) FROM `actividad`
UNION ALL SELECT 'rubros',                       COUNT(*) FROM `rubro`
UNION ALL SELECT 'rendiciones',                  COUNT(*) FROM `rendicion`
UNION ALL SELECT 'otros ingresos/egresos',       COUNT(*) FROM `otros_ingresos_egresos`
UNION ALL SELECT 'vinculos coordinador-programa', COUNT(*) FROM `coordinador_programa`
UNION ALL SELECT 'CATALOGO cargo',               COUNT(*) FROM `cargo`
UNION ALL SELECT 'CATALOGO tipo_comprobante',    COUNT(*) FROM `tipo_comprobante`
UNION ALL SELECT 'CATALOGO categoria_rubro',     COUNT(*) FROM `categoria_rubro`
UNION ALL SELECT 'CATALOGO subcategoria_rubro',  COUNT(*) FROM `subcategoria_rubro`
UNION ALL SELECT 'CATALOGO tipo_rubro',          COUNT(*) FROM `tipo_rubro`
UNION ALL SELECT 'CATALOGO tipo_programa',       COUNT(*) FROM `tipo_programa`
UNION ALL SELECT 'CATALOGO oie_tipo',            COUNT(*) FROM `oie_tipo`
UNION ALL SELECT 'CATALOGO oie_tipo_comprobante', COUNT(*) FROM `oie_tipo_comprobante`
UNION ALL SELECT 'migraciones aplicadas',        COUNT(*) FROM `schema_migrations`;

-- Quiénes quedan con acceso:
SELECT u.`id`, u.`email`, c.`descripcion` AS cargo,
       CONCAT(p.`nombres`, ' ', p.`apellido_paterno`, ' ', p.`apellido_materno`) AS persona
  FROM `usuario` u
  JOIN `cargo`   c ON c.`id` = u.`cargo_id`
  JOIN `persona` p ON p.`id` = u.`persona_id`
 ORDER BY u.`id`;
