-- 022 — Códigos autogenerados: unicidad del árbol POA POR PADRE.
-- Los códigos del árbol (Resultado/Producto/Actividad/Rubro) pasan a autogenerarse
-- como numéricos jerárquicos (Resultado "1", Producto "1.1", Actividad "1.1.1",
-- Rubro "1.1.1.01") y por tanto SE REPITEN entre programas: "1" existe en cada
-- programa. Por eso la unicidad global de `codigo` se relaja a unicidad
-- (padre, codigo). La identidad real sigue siendo `id`.
--   resultado : único por (programa_id, codigo)
--   producto  : único por (resultado_id, codigo)
--   actividad : único por (producto_id, codigo)
--   rubro     : único por (actividad_id, codigo)
-- programa/fuente/categoría mantienen su UNIQUE global (correlativos con prefijo).

ALTER TABLE `resultado`
  DROP INDEX `codigo_UNIQUE`,
  ADD UNIQUE KEY `resultado_codigo_por_programa` (`programa_id`, `codigo`);

ALTER TABLE `producto`
  DROP INDEX `codigo_UNIQUE`,
  ADD UNIQUE KEY `producto_codigo_por_resultado` (`resultado_id`, `codigo`);

ALTER TABLE `actividad`
  DROP INDEX `codigo_UNIQUE`,
  ADD UNIQUE KEY `actividad_codigo_por_producto` (`producto_id`, `codigo`);

ALTER TABLE `rubro`
  DROP INDEX `codigo_UNIQUE`,
  ADD UNIQUE KEY `rubro_codigo_por_actividad` (`actividad_id`, `codigo`);

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('022');
