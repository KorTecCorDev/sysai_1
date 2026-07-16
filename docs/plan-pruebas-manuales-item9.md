# Plan de pruebas manuales en el navegador — Item 9 (2026-07-16)

> Verificación manual del **Programa Institucional + transferencias + reportes Excel corregidos**
> antes de la presentación a la contadora. Ejecutar en orden; cada paso indica su resultado esperado.
> Si algo no cuadra, anotar el número de paso.

## Preparación (una sola vez)

1. Servidor corriendo: `local3000` (http://localhost:3000).
2. Cargar el escenario demo (la suite QA deja el fixture de pruebas; el demo es más realista):
   ```
   "C:\xampp\mysql\bin\mysql.exe" -u root sysai -e "source C:/xampp/htdocs/sysai/database/seed_demo.sql"
   ```
3. Credenciales demo: **Contador** `contador@arcoiris.pe / Contador2026*` · **Coordinador** `coordinador.comunidad@arcoiris.pe / Comunidad2026*`.
4. ⚠️ No correr `qa_all.ps1` a mitad de las pruebas — resetea la BD.

---

## Bloque 1 — El Institucional existe y está protegido (Contador)

| # | Acción | Resultado esperado |
|---|---|---|
| 1.1 | Login como contador → **Programas** | Aparece **INSTITUCIONAL (PRG000)** con badge azul "Institucional" y **sin botón de eliminar** |
| 1.2 | **Fuentes ↔ Programas** (`/dfinanciamiento/crear`) → abrir el select de programa | INSTITUCIONAL **no aparece** como destino; el texto de ayuda menciona la transferencia |

## Bloque 2 — Transferencias

| # | Acción | Resultado esperado |
|---|---|---|
| 2.1 | Seleccionar **COMUNIDAD** → card ALIANZA SOLIDARIA (vinculada) → campo "Transferencia al Institucional" = **100000** → botón ⇄ | Mensaje "actualizado"; la card muestra "Transferido: S/. 100,000.00 (0 para quitarla)" |
| 2.2 | Cambiar a **150000** → guardar | Se actualiza; el "Comprometido (Σ sobres)" de la fuente sube |
| 2.3 | Teclear **999999999** → guardar | **Error visible** "excede la capacidad asignable"; el monto NO cambia |
| 2.4 | **Saldos** (`/saldos_contables/saldos`) | En la tabla de sobres aparece la fila **(INSTITUCIONAL, ALIANZA SOLIDARIA)** con monto 150 000 |

## Bloque 3 — El Contador opera el POA Institucional

| # | Acción | Resultado esperado |
|---|---|---|
| 3.1 | **POA Presupuestal** (`/poa/admin`) | Panel superior "**Programa Institucional** — a tu cargo" con presupuesto = S/. 150,000 y margen |
| 3.2 | "Gestionar jerarquía y rubros" → crear **Resultado → Producto → Actividad → Rubro** (ej. "SERVICIOS BÁSICOS", servicio, **S/ 80,000**) | Todo se crea normal (selector de programa = INSTITUCIONAL) |
| 3.3 | Volver a `/poa/admin` → **Iniciar POA Institucional** | Documento en **Borrador**, presupuesto = 80 000 (Σ rubros) |
| 3.4 | **Enviar a revisión** | Estado **Enviado** |
| 3.5 | "Revisar y decidir" en la tabla → **Aprobar** | Estado **Aprobado** (auto-aprobación aceptada por diseño) |

## Bloque 4 — Rendición institucional + guarda de reducción

| # | Acción | Resultado esperado |
|---|---|---|
| 4.1 | Jerarquía del Institucional → rubro → **Rendiciones** → crear una (ej. S/ 5,000, fecha de hoy, fuente ALIANZA) | Nace **Aprobada** (POA aprobado + eres Contador) |
| 4.2 | **Saldos** | Sobre del Institucional: 150 000 − 5 000 = **145 000** |
| 4.3 | Volver a `/dfinanciamiento` COMUNIDAD → intentar reducir la transferencia a **3000** (< 5 000 rendidos) | Banner: "**No se puede reducir o quitar la transferencia: el programa Institucional ya comprometió ese dinero…**"; el monto sigue en 150 000 |

## Bloque 5 — Reportes Excel (la corrección estrella)

| # | Acción | Resultado esperado |
|---|---|---|
| 5.1 | **Reportes → Rendición de cuentas** (`/reporte/poarendicion`) → descargar y abrir | Título de cada bloque: "**RENDICIÓN - 2026 - PROGRAMA …**" (ya no "PRESUPUESTO") |
| 5.2 | Bloque COMUNIDAD: mirar la actividad 1.1.1 (dos rubros) | La suma rendida de cada fuente está **en la fila del rubro que la generó** (no amontonada en la última fila) |
| 5.3 | Columnas después de la última fuente | Sección "**TOTAL RENDIDO**" con encabezados RENDIDO (S/) / (USD) / (EUR); **sin ceros** en filas sin rendiciones |
| 5.4 | Final del bloque COMUNIDAD | Fila "**TRANSFERENCIA A PROGRAMA INSTITUCIONAL**" (150 000) y debajo la fila "**TOTAL**" etiquetada, que la incluye |
| 5.5 | Bloque CASA HOGAR | Sus 2 rendiciones **pendientes** NO aparecen (solo aprobadas) |
| 5.6 | Bloque INSTITUCIONAL | Aparece como programa normal, con la rendición de 5 000 — **sin** fila de transferencia |
| 5.7 | **Reportes → POA** (`/reporte/poa`) y **POA General** (`/reporte/poarubros`) | Misma fila de transferencia + TOTAL etiquetado; nada revienta |

## Bloque 6 — El coordinador no se ve afectado

| # | Acción | Resultado esperado |
|---|---|---|
| 6.1 | Login como coordinador → `/poa/admin` | Su panel de siempre, **sin** panel institucional |
| 6.2 | Su reporte de rendición | Solo su programa, con la fila de transferencia si COMUNIDAD transfirió |

## Bloque 7 (opcional) — Regresión del sprint de deuda técnica

| # | Acción | Resultado esperado |
|---|---|---|
| 7.1 | Como admin: crear un usuario con nombre "María de los Ángeles" | Se guarda **tal cual** (sin MAYÚSCULAS forzadas) y el input ya no muestra mayúsculas al teclear |
| 7.2 | Cualquier pantalla con iconos (sidebar) | Iconos intactos (limpieza de `build/css/` no rompió nada) |

---

## Para la reunión con la contadora

- Llevar **`docs/confirmar-tc-contador.md`**: mapeo compra/venta del TC (gasto→venta, ingreso→compra,
  saldos→compra, planificación→venta) con las 5 preguntas concretas. **Resolver antes de registrar
  transacciones reales** — el TC congelado no se recalcula retroactivamente.
- Demostrar el flujo institucional (bloques 2-5) con el escenario demo.
