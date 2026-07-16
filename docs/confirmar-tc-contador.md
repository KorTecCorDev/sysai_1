# Consulta al contador — Mapeo compra/venta del tipo de cambio

> **Estado: PENDIENTE de respuesta del contador de Arco Iris** (creado 2026-07-16).
> El mapeo vigente fue derivado por lógica NIC 21 durante el plan de montos y TC
> (`docs/plan-montos-y-tipo-cambio.md` §5.3), **no por norma interna de la organización**.
> Este documento resume qué está implementado, por qué, y qué preguntar. Cuando el contador
> responda: si confirma, cerrar el follow-up en `CLAUDE.md`; si contradice, tocar los puntos
> del código listados abajo (son copias por valor — corregir ANTES de registrar transacciones
> reales, porque el TC congelado no se recalcula retroactivamente).

## Mapeo implementado hoy

| Operación | Tasa aplicada | Racional (NIC 21 / práctica cambiaria) |
|---|---|---|
| **Rendición (gasto rendido)** | **VENTA** vigente a la fecha de operación, congelada al registrar | Para cubrir un gasto habría que *comprar* divisa al tipo de **venta** del banco. |
| **OIE Ingreso (donación)** | **COMPRA** vigente a la fecha de operación, congelada | Una divisa recibida se *vende* al banco al tipo de **compra**. |
| **OIE Egreso** | **VENTA** vigente a la fecha de operación, congelada | Mismo racional que el gasto. |
| **Saldos contables (partida monetaria, conversión al cierre)** | **COMPRA** vigente a hoy | NIC 21: las partidas monetarias se convierten a la tasa de cierre; se usa compra como tasa de realización. |
| **Planificación (POA / rubros, reportes Excel)** | **VENTA** vigente al generar el reporte | Presupuesto = gasto futuro → tasa de venta. Sin fecha de operación, no se congela. |

Reglas ya confirmadas que NO están en duda (no preguntar de nuevo):
- El TC vigente a una fecha = registro con `fecha_vigencia` máxima ≤ esa fecha (cubre feriados/fines de semana).
- El TC de una transacción se congela al registrarla (se copia el valor, no un FK).
- La tasa SBS es solo informativa (pre-llena el formulario; el Contador decide).

## Preguntas concretas para el contador

1. Cuando la organización **rinde un gasto** y se reporta su equivalente en USD/EUR,
   ¿usan la tasa de **venta** del día de la operación? ¿O tienen otra convención (p. ej. tasa SBS
   contable única, promedio compra/venta, tasa del donante)?
2. Cuando **ingresa una donación** en soles que se reporta en divisa, ¿aplican la tasa de **compra**?
3. Para los **saldos al cierre** (reporte a donantes), ¿convierten con la tasa de **compra** vigente
   al cierre (NIC 21) o con otra tasa (venta, promedio, tasa pactada con el donante)?
4. Para el **presupuesto/POA** (planificación, sin fecha de operación), ¿venta vigente al generar el
   reporte es aceptable, o el donante fija una tasa contractual?
5. ¿Algún donante exige una **fuente específica de tasa** (SBS contable, BCRP, banco propio)?
   Hoy la SBS es solo informativa y las tasas las registra el Contador a mano.

## Dónde vive el mapeo en el código (tocar SOLO si el contador contradice)

| Punto | Archivo:línea | Qué elige |
|---|---|---|
| Rendición al registrar | `models/Rendicion.php:80,82` (`->venta`) | venta |
| Rendición, backfill de pendientes | `models/Rendicion.php:129,131` | venta |
| OIE ingreso/egreso | `models/OieComprobante.php:60` (`$campo = ($oieTipoId === 1) ? 'compra' : 'venta';`) | compra / venta |
| Saldos al cierre | `views/saldos_contables/saldos.php:90-91` (+ `controllers/SaldoContableController.php:45-46`) | compra |
| Planificación / reporte POA rubros | `models/ActiveRecord.php:726-727` (+ `controllers/ReportePoaRubrosController.php:47`) | venta |

> Nota: las vistas SQL de reportes (migr. 030) convierten con el TC **ya congelado** por fila
> (`tc_usd`/`tc_eur`), así que no eligen compra/venta: heredan lo que se congeló. Si el mapeo cambia,
> además de los puntos de la tabla habría que decidir qué hacer con transacciones ya congeladas
> (en greenfield no habrá ninguna → otra razón para resolver esto antes del despliegue).
