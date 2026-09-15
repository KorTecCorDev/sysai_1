---
name: reportes-excel
description: Detalle de los reportes Excel de rendición de Arca (/reporte/poarendicion, /reporte/poarubros, /reporte/poa; ReporteRendicionXlsxBuilder, vista reporte_poa_rendicion) — grano, filtros, columnas calculadas y QA celda a celda. Leer antes de modificar un reporte Excel o sus vistas SQL.
---

# Reportes Excel de rendición (✅ REESCRITOS 2026-07-16, item 9 — migr. 034)

- `models/ReporteRendicionXlsxBuilder.php` genera `/reporte/poarendicion` y `/reporte/poarubros`
  (las vistas quedaron como orquestadores delgados), **conservando el diseño visual** (bloques por programa,
  BIENES/SERVICIOS, totales por actividad en G/H/I, tripletas S//USD/EUR por fuente desde K).
- **Grano por RUBRO** (migr. 034): la vista `reporte_poa_rendicion` agrupa por rubro×fuente — cada suma cae
  **en la fila de su rubro** (antes: fila del último rubro de la actividad, fósil pre-migr. 017). Cuenta
  **solo rendiciones Aprobadas** y **solo el ejercicio vigente** (`YEAR(fecha_original)`, decisiones 2026-07-16).
- Columnas **calculadas** (`Coordinate::stringFromColumnIndex`) — sin arrays K..Z hardcodeados: soporta N
  fuentes (antes con ≥6 las sumas desaparecían). Columnas "TOTAL RENDIDO" con encabezado; fila TOTAL
  **etiquetada**; `combinarCeldasRepetidas` solo en columnas de etiquetas A/B (fusionar montos iguales
  adyacentes hacía desaparecer importes). `/reporte/poa` conserva su layout + fila de transferencia + TOTAL.
- `/reporte/ingresos` y `/reporte/rendiciones` usan `ReporteMovimientosXlsxBuilder` (migr. 035,
  `docs/plan-reportes-ingresos-y-rendiciones.md`). Todo libro nace con `nuevoLibroXlsx()` y se entrega por
  streaming con `descargarXlsx()`.
- QA: `qa_reportes.ps1` asserta el contenido **celda a celda** del xlsx generado (helper `qa_leer_xlsx.php`).
