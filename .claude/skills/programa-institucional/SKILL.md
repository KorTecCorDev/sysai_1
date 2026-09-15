---
name: programa-institucional
description: Reglas del Programa Institucional (PRG000, es_institucional) y de las transferencias institucionales de Arca — sobre derivado, guardas de edición, POA operado por el Contador y fila de transferencia en los Excel. Leer antes de tocar programas, sobres (/dfinanciamiento), transferencia_institucional o el POA del Institucional.
---

# Programa Institucional y transferencias (✅ IMPLEMENTADO 2026-07-16, item 9 — migr. 033)

- El programa **INSTITUCIONAL** (código reservado `PRG000`, flag **`programa.es_institucional`**) concentra los
  gastos de oficina/administrativos. **Nace con el sistema** (lo crea la migr. 033 con `INSERT IGNORE`; los
  fixtures QA/demo lo re-siembran) y está **protegido contra eliminación** (`resultado=28`). Identificación
  SIEMPRE por el flag (`Programa::institucional()`, cacheado) — nunca por nombre/id.
- **Se comporta como un programa normal** (jerarquía, POA, rendiciones, saldos) con dos diferencias:
  1. **No recibe sobres directos** (`resultado=26` como destino en `/dfinanciamiento`): su sobre
     `(Institucional, fuente)` es **derivado** = Σ transferencias de esa fuente, materializado como fila normal
     de `detalle_financiamiento` gestionada solo por `TransferenciaInstitucional::sincronizarSobreInstitucional()`
     (recalcula, no incrementa; con Σ=0 el sobre se elimina). Así todas las vistas de saldo, la puerta de
     sobres y el tope del POA funcionan sin tocarse.
  2. **Lo opera el Contador directamente**: `iconta.php` tiene `POST /poa/crear|enviar` con guarda — el
     Contador solo ELABORA el POA del Institucional (`resultado=26` en programas normales; en ellos sigue
     siendo revisor/adenda). Panel de elaboración en la rama contador de `views/poa/admin.php`. La
     auto-aprobación de su propio POA es aceptada (decisión 2026-07-16). Sus rendiciones sobre POA aprobado
     nacen Aprobadas (adenda existente).
- **Transferencia** (`transferencia_institucional`, UNIQUE por (fuente, programa origen), **monto fijo**):
  se captura al asignar sobres en `/dfinanciamiento/crear` (campo opcional al crear; edición inline en
  fuentes vinculadas, monto 0 = quitarla). Es **partición en el origen**: el monto transferido NO vive en el
  sobre del programa origen — la invariante Σ sobres ≤ presupuesto se mantiene sin doble conteo, y
  `validarLimiteAsignacion` valida sobre + transferencia JUNTOS contra la capacidad asignable.
- **Guardas de edición**: aumento → el delta cabe en la capacidad asignable; reducción/eliminación → el sobre
  del Institucional nunca queda bajo lo ya comprometido por él en esa fuente (`resultado=27`). "Quitar" un
  vínculo arrastra su transferencia (con la misma guarda).
- **En los reportes Excel**, el bloque del programa origen muestra la fila
  **"TRANSFERENCIA A PROGRAMA INSTITUCIONAL"** (última antes del TOTAL): el total del bloque =
  Σ rubros + transferencia (el cargo completo al grant que ve el donante).
- QA: `database/qa_institucional.ps1` (15).
