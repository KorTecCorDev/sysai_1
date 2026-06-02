-- 008 · Grupo 13 — Presupuesto anual por fuente (histórico de saldos)
-- monto_inicial + ingresos - rendiciones_aprobadas - otros_egresos = contable.
-- presupuesto_comprometido = suma de POAs Presupuestales aprobados que usan la
-- fuente (se recalcula en la app). Una fila por fuente y año.
-- `fuente_financiamiento.presupuesto` queda como monto de referencia inicial.

CREATE TABLE `fuente_presupuesto_anual` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fuente_financiamiento_id` int(11) NOT NULL,
  `anio` char(4) NOT NULL,
  `monto_inicial` decimal(14,2) NOT NULL DEFAULT 0.00,
  `presupuesto_comprometido` decimal(14,2) NOT NULL DEFAULT 0.00,
  `presupuesto_contable` decimal(14,2) NOT NULL DEFAULT 0.00,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fuente_anio` (`fuente_financiamiento_id`, `anio`),
  CONSTRAINT `fk_fpa_fuente` FOREIGN KEY (`fuente_financiamiento_id`) REFERENCES `fuente_financiamiento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('008');
