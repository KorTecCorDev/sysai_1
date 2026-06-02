-- 004 · POA Presupuestal — ampliar capacidad del monto
-- decimal(7,2) topaba en 99,999.99. Se amplía a decimal(14,2).
-- `poa.estado` ya es int(11): admite 0=Borrador,1=Enviado,2=Observado,3=Aprobado
-- sin cambio de tipo (solo lógica de aplicación).

ALTER TABLE `poa`
  MODIFY `presupuesto` decimal(14,2) NOT NULL;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('004');
