# Plan de pruebas manuales en el navegador

> Verificación manual antes de la entrega. Cubre el **item 9** (Programa Institucional +
> transferencias + reportes Excel, 2026-07-16) y el **sprint de login/marca/B4** (2026-08-12).
> Ejecutar en orden; cada paso indica su resultado esperado. Si algo no cuadra, anotar el número
> de paso (p. ej. "falla el 5.4") — con eso basta para retomarlo.

## Preparación (una sola vez)

1. Servidor corriendo: `local3000` (http://localhost:3000).
2. Cargar el escenario demo (la suite QA deja el fixture de pruebas; el demo es más realista):
   ```
   "C:\xampp\mysql\bin\mysql.exe" -u root sysai -e "source C:/xampp/htdocs/sysai/database/seed_demo.sql"
   ```
3. Credenciales demo:
   | Rol | Correo | Contraseña |
   |---|---|---|
   | Contador | `contador@arcoiris.pe` | `contador2026` ⚠️ *(cambiada el 2026-08-12 al ejecutar el paso 0.8 con esta cuenta; `seed_demo.sql` sigue trayendo `Contador2026*` — un reseed la revierte)* |
   | Coordinador (Comunidad) | `coordinador.comunidad@arcoiris.pe` | `Comunidad2026*` |
   | Coordinador (Casa Hogar) | `coordinador.casahogar@arcoiris.pe` | `CasaHogar2026*` |
4. ⚠️ No correr `qa_all.ps1` a mitad de las pruebas — resetea la BD al fixture de QA.

---

## Bloque 0 — Autenticación y marca (sprint 2026-08-12)

| # | Acción | Resultado esperado |
|---|---|---|
| 0.1 | Abrir `/login` | Tarjeta de dos paneles: izquierda azul con el **logo de Arca** y el título **Arca**; pestaña del navegador con el **isotipo** y el título **Arca · Arco Iris** |
| 0.2 | Teclear el correo en minúsculas | Se ve **en minúsculas** (antes el CSS lo mostraba en MAYÚSCULAS aunque se guardara tal cual) |
| 0.3 | Entrar con un correo **inventado** y cualquier clave, 5 veces seguidas | Del 1º al 4º: "Las credenciales ingresadas no son correctas"; al 5º: aviso de **máximo de intentos**; después: "Demasiados intentos fallidos" *(usar un correo inventado para no bloquear una cuenta demo)* |
| 0.4 | Pegar en la barra de direcciones `/updtepsswd?id=1` **sin haber pedido código** | Rebota a **`/chgpsswd`**. ⚠️ Este es el fallo de seguridad corregido: antes esa URL dejaba cambiarle la contraseña a **cualquier** usuario |
| 0.5 | `/chgpsswd` → correo de Casa Hogar → "Enviar código" ⚠️ *(la cuenta que uses aquí **pierde** su contraseña demo: apunta la nueva antes de seguir, o los bloques posteriores no podrán entrar con ella)* | Pasa al paso 2 (stepper 1-2-3). El código **no** llega por correo en modo DEV: se escribe en `includes/logs/mail.log` (última línea) |
| 0.6 | Pegar el código en el paso 2 | Pasa al paso 3, "Nueva contraseña" |
| 0.7 | Poner una contraseña de menos de 8 caracteres, y luego dos que no coincidan | Error visible en ambos casos; no avanza |
| 0.8 | Contraseña válida (mínimo 8) → guardar | Vuelve a `/login` con "Tu contraseña se actualizó". Entrar con la nueva ✔ *(ojo: esto cambia la clave demo de Casa Hogar; anótala)* |
| 0.9 | Volver a pegar `/updtepsswd` tras el cambio | Rebota a `/chgpsswd` (la prueba es de **un solo uso**) |
| 0.10 | Entrar con cada rol y mirar la pestaña del navegador; abrir también una URL inexistente (404) | Siempre **Arca · Arco Iris** y el isotipo de Arca (antes decía "SysAI") |

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
| 2.4 | **Saldos** (`/saldos_contables/saldos`) → tabla "Saldos por Sobre" | Aparece la fila **PRG000 · INSTITUCIONAL / ALIANZA SOLIDARIA**, pero **con badge amarillo "POA no aprobado" y sin cifras** — no es un fallo: es la regla del item 8 (el saldo solo se muestra con el POA Presupuestal del año Aprobado, y el del Institucional se crea en el Bloque 3). Casa Hogar sale igual, por lo mismo |
| 2.5 | *(se comprueba al terminar el Bloque 3)* Volver a **Saldos** | Ahora sí, la fila del Institucional muestra el monto: **Σ de las transferencias que recibió en esa fuente** |

## Bloque 3 — El Contador opera el POA Institucional

| # | Acción | Resultado esperado |
|---|---|---|
| 3.1 | **POA Presupuestal** (`/poa/admin`) | Panel superior "**Programa Institucional** — a tu cargo", con tope = **Σ de TODAS las transferencias que ha recibido** (no solo la de ALIANZA). Con las transferencias hechas el 2026-08-13 —150 000 ALIANZA + 500 000 COMPASSION de Comunidad + 90 000 LATIN LINK de Casa Hogar— el tope es **S/ 740,000** |
| 3.2 | "Gestionar jerarquía y rubros" → crear **Resultado → Producto → Actividad → Rubro** (ej. "Servicios básicos", servicio, **S/ 80,000**) | Todo se crea normal (selector de programa = INSTITUCIONAL). El texto se guarda **tal como lo escribes** |
| 3.3 | Volver a `/poa/admin` → **Iniciar POA Institucional** | Documento en **Borrador**, presupuesto = 80 000 (Σ rubros) |
| 3.4 | **Enviar a revisión** | Estado **Enviado** |
| 3.5 | "Revisar y decidir" en la tabla → **Aprobar** | Estado **Aprobado** (auto-aprobación aceptada por diseño) |

## Bloque 4 — Rendición institucional + guarda de reducción

| # | Acción | Resultado esperado |
|---|---|---|
| 4.1 | Jerarquía del Institucional → rubro → **Rendiciones** → crear una (ej. S/ 5,000, fecha de hoy, fuente ALIANZA) | Nace **Aprobada** (POA aprobado + eres Contador) |
| 4.2 | **Saldos** | Ya con el POA aprobado las cifras se ven. Sobre del Institucional en **la fuente que usaste** en 4.1: su transferencia − 5 000 (con ALIANZA: 150 000 − 5 000 = **145 000**) |
| 4.3 | Volver a `/dfinanciamiento` COMUNIDAD → intentar reducir la transferencia a **3000** (< 5 000 rendidos) | Banner: "**No se puede reducir o quitar la transferencia: el programa Institucional ya comprometió ese dinero…**"; el monto sigue en 150 000 |

## Bloque 5 — Reportes Excel (la corrección estrella)

| # | Acción | Resultado esperado |
|---|---|---|
| 5.1 | **Reportes → Rendición de cuentas** (`/reporte/poarendicion`) → descargar y abrir | Título de cada bloque: "**RENDICIÓN - 2026 - PROGRAMA …**" (ya no "PRESUPUESTO") |
| 5.2 | Bloque COMUNIDAD: mirar la actividad 1.1.1 (dos rubros) | La suma rendida de cada fuente está **en la fila del rubro que la generó** (no amontonada en la última fila) |
| 5.3 | Columnas después de la última fuente | Sección "**TOTAL RENDIDO**" con encabezados RENDIDO (S/) / (USD) / (EUR); **sin ceros** en filas sin rendiciones |
| 5.4 | Final del bloque COMUNIDAD | Fila "**TRANSFERENCIA A PROGRAMA INSTITUCIONAL**" (150 000) y debajo la fila "**TOTAL**" etiquetada, que la incluye |
| 5.5 | Bloque CASA HOGAR | Sus rendiciones **pendientes** NO aparecen (solo aprobadas) |
| 5.6 | Bloque INSTITUCIONAL | Aparece como programa normal, con la rendición de 5 000 — **sin** fila de transferencia |
| 5.7 | **Reportes → POA** (`/reporte/poa`) y **POA General** (`/reporte/poarubros`) | Misma fila de transferencia + TOTAL etiquetado; nada revienta |
| 5.8 | Descargar **dos veces seguidas** el reporte de rendición y comparar | Las columnas de fuente salen **en el mismo orden** las dos veces (arreglado el 2026-08-12: antes el orden era el que devolviera MySQL y podía cambiar entre descargas) |

## Bloque 6 — El coordinador no se ve afectado

| # | Acción | Resultado esperado |
|---|---|---|
| 6.1 | Login como coordinador → `/poa/admin` | Su panel de siempre, **sin** panel institucional |
| 6.2 | Su reporte de rendición | Solo su programa, con la fila de transferencia si COMUNIDAD transfirió |

## Bloque 7 — Regresión del sprint de deuda técnica

| # | Acción | Resultado esperado |
|---|---|---|
| 7.1 | Como admin: crear un usuario con nombre "María de los Ángeles" | Se guarda **tal cual** y el input **ya no muestra mayúsculas al teclear** (B4 quedó cerrado del todo el 2026-08-12: faltaba la mitad de CSS) |
| 7.2 | Cualquier pantalla con iconos (sidebar) | Iconos intactos (la limpieza de `build/css/` no rompió nada) |
| 7.3 | Un formulario con **textarea** (p. ej. descripción de un rubro) y otro con fechas/montos | Texto tal como se escribe; los montos siguen aceptando decimales |

---

## Estado de los pendientes de negocio

- ✅ **Mapeo compra/venta del TC — CONFIRMADO por la contadora el 2026-08-12**: *"usar la tasa
  vigente, seguimos con la lógica NIC 21"*. No hay código que cambiar; registro en
  `docs/confirmar-tc-contador.md`.
- ⏳ **Despliegue greenfield en Hostinger**: al final, después de estas pruebas.
