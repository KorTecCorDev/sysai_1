---
name: tipo-de-cambio
description: Reglas del tipo de cambio de Arca (tabla tipo_cambio, TipoCambio::vigente, TC congelado en rendiciones y OIE, compra vs venta, NIC 21, consulta SBS). Leer antes de tocar conversiones a USD/EUR, /tcambio, saldos al cierre o la aprobación del POA con rendiciones pendientes de TC.
---

# Tipo de Cambio (✅ reescrito 2026-07-15, plan de montos — migr. 028-030)

- Tabla única `tipo_cambio` (moneda USD/EUR, `fecha_vigencia`, **compra** y **venta**, origen MANUAL/SBS,
  `decimal(12,6)`). UNIQUE (moneda, fecha_vigencia).
- **El TC vigente a una fecha** = registro con `fecha_vigencia` máxima ≤ esa fecha (convención contable para
  feriados/fines de semana). **Nunca por id/orden de tecleo** (`TipoCambio::vigente()`).
- **El TC aplicado a una transacción es el vigente a su FECHA DE OPERACIÓN, congelado al registrar** (migr. 029):
  rendición (gasto) → **venta**; OIE ingreso → **compra**; OIE egreso → **venta**. Se copia el **valor** (no un
  FK): editar/borrar un TC después no reescribe la contabilidad. Sin cobertura ⇒ `tc_usd`/`tc_eur` quedan `NULL`
  ("pendiente de TC", el registro no se bloquea); **el Contador no puede aprobar el POA** con rendiciones
  pendientes de TC (`resultado=22`; al aprobar se reintenta el congelamiento por si ya cargó las tasas).
- Al editar una transacción, el TC congelado **no se recalcula** salvo que cambie la fecha de operación (o el
  tipo ingreso↔egreso en OIE), o que siga pendiente y ya haya cobertura.
- **Planificación** (rubros/POA, sin fecha de operación) → **venta al cierre** (vigente al generar el reporte);
  **saldos** (partida monetaria) → **compra al cierre** (NIC 21), siempre mostrando tasa/fecha/origen.
  ✅ **Mapeo CONFIRMADO por la contadora de Arco Iris el 2026-08-12** (*"usar la tasa vigente, seguimos con
  NIC 21"*): ya no es una derivación por lógica, es la convención de la organización. No cambiar sin una
  nueva consulta — el TC congelado no se recalcula hacia atrás (`docs/confirmar-tc-contador.md`).
- **La tasa SBS es informativa**: botón "Consultar SBS" pre-llena el formulario (endpoint configurable
  `SBS_API_URL` en `.env`, timeout 5 s, degradación limpia) y `database/importar_tc_sbs.php` hace el backfill en
  lote (origen='SBS', `INSERT IGNORE`). Nunca corre en la ruta de un reporte; nunca se guarda sin el Contador.
- Plan completo y decisiones: `docs/plan-montos-y-tipo-cambio.md`.
