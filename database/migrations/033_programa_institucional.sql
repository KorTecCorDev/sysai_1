-- 033 — Programa Institucional + transferencias (decisiones 2026-07-16).
-- El programa Institucional concentra los gastos de oficina/administrativos y se
-- comporta como un programa NORMAL (POA, rendiciones, saldos), con dos diferencias:
--   (a) lo opera el CONTADOR directamente (rutas /poa/crear|enviar en iconta);
--   (b) NO recibe sobres directos: su sobre (Institucional, fuente) se deriva como
--       Σ transferencias que cada programa le destina al asignarse sobres
--       (partición en el origen — el sol transferido vive en UN solo sobre, la
--       invariante Σ sobres ≤ presupuesto se mantiene sin doble conteo).
-- La transferencia se registra aquí como trazabilidad: alimenta la fila
-- "TRANSFERENCIA A PROGRAMA INSTITUCIONAL" del reporte Excel del programa origen.

-- 1) Flag de identificación (el seed garantiza existencia; el flag identifica —
--    identificar por nombre/id sería frágil). Comportamiento normal en todo lo demás.
ALTER TABLE `programa`
    ADD COLUMN `es_institucional` tinyint(1) NOT NULL DEFAULT 0 AFTER `tipo_programa_id`;

-- 2) Transferencias: un registro EDITABLE por par (fuente, programa origen) — espejo
--    del patrón uq_programa_fuente de detalle_financiamiento (migr. 020).
CREATE TABLE IF NOT EXISTS `transferencia_institucional` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fuente_financiamiento_id` int(11) NOT NULL,
  `programa_origen_id` int(11) NOT NULL,
  `monto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ff_origen` (`fuente_financiamiento_id`, `programa_origen_id`),
  KEY `fk_ti_fuente_idx` (`fuente_financiamiento_id`),
  KEY `fk_ti_programa_origen_idx` (`programa_origen_id`),
  CONSTRAINT `fk_ti_fuente` FOREIGN KEY (`fuente_financiamiento_id`)
    REFERENCES `fuente_financiamiento` (`id`),
  CONSTRAINT `fk_ti_programa_origen` FOREIGN KEY (`programa_origen_id`)
    REFERENCES `programa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- 3) El programa Institucional nace con el sistema (sin él "no funciona la
--    organización"). Código reservado PRG000 (no colisiona con el correlativo
--    PRG001+: siguienteCodigoCorrelativo usa MAX(sufijo)+1). Idempotente por el
--    UNIQUE de codigo.
INSERT IGNORE INTO `programa` (`codigo`, `nombre`, `descripcion`, `fecha`, `tipo_programa_id`, `es_institucional`)
VALUES ('PRG000', 'INSTITUCIONAL',
        'GASTOS DE OFICINA Y ADMINISTRATIVOS DE LA ORGANIZACIÓN. SU PRESUPUESTO = SUMA DE TRANSFERENCIAS DE LOS PROGRAMAS.',
        NOW(), 1, 1);

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('033');
