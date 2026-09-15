-- 023 — Ampliar `codigo` del árbol POA para los códigos jerárquicos autogenerados.
-- Con "1.1.1.01" el varchar(8) queda justo, y se desborda apenas un ancestro llega
-- a dos dígitos (p. ej. "10.1.1.01" = 9). Ampliamos a varchar(20) para holgura.
ALTER TABLE `resultado` MODIFY `codigo` varchar(20) NOT NULL;
ALTER TABLE `producto`  MODIFY `codigo` varchar(20) NOT NULL;
ALTER TABLE `actividad` MODIFY `codigo` varchar(20) NOT NULL;
ALTER TABLE `rubro`     MODIFY `codigo` varchar(20) NOT NULL;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('023');
