---
name: cierre-anual
description: Reglas del cierre anual de Arca (fuente_presupuesto_anual, POST /cierre_anual/guardar, FuentePresupuestoAnual::cerrarAnio, histórico anual en saldos). Leer antes de tocar el cierre del ejercicio, el histórico por fuente o el rollover entre años.
---

# Cierre Anual (✅ IMPLEMENTADO 2026-07-16, item 8 — decisiones confirmadas ese día)

- El saldo sobrante de cada fuente al cierre del año se registra en `fuente_presupuesto_anual` (fuente_id, anio,
  monto_inicial, presupuesto_comprometido, presupuesto_contable). Permite el histórico año a año.
- **El cierre es un SNAPSHOT manual**: botón "Registrar cierre del año" en `/saldos_contables/saldos`
  (Contador/Admin, `POST /cierre_anual/guardar` — sufijo `/guardar` para pasar el CSRF del Router).
  Copia el desglose EN VIVO (`FuentePresupuestoAnual::cerrarAnio()` ← `desglosePorFuente()`): inicial =
  `fuente.presupuesto`, **comprometido = Σ sobres**, contable = inicial + ingresos − rendiciones aprobadas −
  otros egresos. **Nunca acumuladores por operación** (antipatrón descartado en el plan de montos §2.4).
- **Re-cerrable con aviso**: upsert por `UNIQUE (fuente, anio)`; el confirm avisa que reemplaza el snapshot.
- **Rendiciones pendientes advierten, no bloquean** (el confirm indica cuántas hay; no descuentan el contable).
- La pantalla muestra el **Histórico Anual por Fuente** (bloque 5) leído de la tabla.
- ⏸ El **rollover** (traspaso del sobrante al `monto_inicial` del año siguiente) sigue en v1.1, junto con las
  preguntas de periodos del plan de montos §5.1.
- QA: `database/qa_cierre_anual.ps1` (16).
