# Historial — Implementación items 2–6 (COMPLETADOS)

> **Archivo histórico.** Extraído de `CLAUDE.md` el 2026-07-09. Todos estos items del backlog están
> ✅ **COMPLETADOS y con QA verde**. Se conserva el detalle de construcción y los hallazgos corregidos
> como registro. El backlog **pendiente** (items 7/8/10) vive en `CLAUDE.md`.

> Cada ítem es código (modelo/controlador/vista/rutas). La BD ya está migrada.
> Rutas por rol: registrar cada acción nueva en `iadmin.php`, `iconta.php`, `icoordi.php` según corresponda.

## 2 — Vínculo Coordinador-Programa  *(linchpin: va primero)* ✅ COMPLETADO (Fase 1)
- [x] Modelo `CoordinadorPrograma` (tabla `coordinador_programa`) con helpers `asignarPrograma()`, `vinculoActivoPorUsuario()`, `desactivarPorUsuario()`, `desactivarPorPrograma()`, `eliminarPorUsuario()`.
- [x] CRUD de asignación en `UsuarioController` (crear/actualizar/eliminar): `asignarPrograma()` desactiva el vínculo previo del coordinador **y** el del programa (ambas invariantes) y crea el nuevo (`activo=1`). El form envía `coordinador_programa[programa_id]` (antes `poa[programa_id]`).
- [x] Alta de usuario coordinador crea su vínculo; se eliminó el viejo mecanismo de "poa-como-vínculo" en altas/ediciones (los `poa` reales se siguen usando para el documento POA).
- [x] Login consume `programa_id` desde `login_session_vista` (verificado: coordinador recupera `programa_id`/`poa_id`).
- [x] Migración **014**: backfill de `coordinador_programa` desde los `poa` de cargo 3 + reescritura de `programas_sin_coordinador_vista` y `usuario_id_disponible_programa_vista` para derivar de `coordinador_programa` (activo).
- Probado por HTTP (login admin, crear coordinador→vínculo, quitar programa→vínculo `activo=0`) y a nivel de datos.

## 3 — POA Indicadores (documento + flujo)  ✅ COMPLETADO (QA HTTP automatizado, 2026-06-04)
- [x] Modelo `PoaIndicadores` (tabla `poa_indicadores`) con estados y `observacion`. Modelo `DetalleActividad` (captura de indicadores por actividad, 1:1, upsert).
- [x] Flujo de estados 0→1→2→3 (Borrador/Enviado/Observado/Aprobado) en `PoaIndicadoresController` (index/crear/enviar/observar/aprobar/revisar).
- [x] Coordinador elabora (crea/captura indicadores/envía); Contador aprueba/observa **con comentario obligatorio** (migración 015) desde la **vista de revisión consolidada read-only** (`poa_indicadores/revisar`).
- [x] Reutiliza jerarquía compartida Resultado→Producto→Actividad (no duplica); captura indicadores en `detalle_actividad`.
- [x] **Bloqueo de jerarquía** al Enviar/Aprobar: helper `exigirPoaIndicadoresEditable` (redirect+flash, no 403 crudo) en Resultado/Producto/Actividad + DetalleActividad; **banner + botones deshabilitados** en las vistas admin de la jerarquía (prevención en UI).
- [x] **Visualización de la observación**: banner visible para coordinador (tarjeta) y contador (fila-banner en la tabla de `poa_indicadores/admin`) y en `revisar`. Decisión 2026-06-04: la observación **solo persiste mientras el documento está en estado Observado** (se limpia al reenviar/aprobar en `transicionar()`).
- [x] **Banner persistente**: `eliminarAlertas()` (`src/js/app.js`) auto-oculta los `.alert` flash a los 3 s, pero ahora respeta `.alert-persistente`; los banners de observación llevan esa clase para no desaparecer hasta cambiar de vista. ⚠️ recompilar bundle (`npx gulp js`) si se vuelve a tocar el JS.
- Decisión 2026-06-04: el **comentario de observación** reemplaza la regla "retorno sin comentario" del Grupo 11 (para POA Indicadores). El Presupuestal podría adoptarlo después (no cambiado aún).

> **✅ QA HTTP AUTOMATIZADO (2026-06-04) — 18/18 OK.** Arnés `database/qa_poa_indicadores.ps1`
> (PowerShell + `Invoke-WebRequest`, 3 sesiones reales por cookie + verificación en BD). Credenciales
> locales: coordinador `coordinador@sysai.test`/`Test1234*` (prog. 1); contador `contador@sysai.test`/`admin1234`
> (= admin). Cubre: login 3 vías (incl. password incorrecto), **CSRF 419** en `/crear` sin token,
> **autorización por rol** (coordinador sin `/aprobar`, contador sin `/crear` → `/error`), **cross-tenant**
> (coordinador no revisa doc ajeno → 403), **flujo de estados completo** Borrador→Enviado→Observado→Enviado→Aprobado
> con verificación en BD, **observar sin comentario** → `resultado=15` sin cambio, **transición inválida** →
> `resultado=13`, **bloqueo de jerarquía** con doc Enviado (`/resultado/crear` → `resultado=14`, no inserta),
> y **acciones rechazan GET**. Re-ejecutable; crea y limpia su propio doc de prueba (programa 1).
>
> **Hallazgo corregido (CSRF):** `Router::requiereCsrf()` solo protegía sufijos `/crear|/actualizar|/eliminar`
> → las acciones de flujo `/enviar`, `/observar`, `/aprobar` y `/detalle_actividad/guardar` **mutaban estado
> sin token CSRF**. Se añadieron esos sufijos (`/enviar|/observar|/aprobar|/guardar`) a la protección. Los
> formularios ya emitían `csrf_input()`, así que no rompió nada.
>
> **Nota (observación al reenviar/aprobar):** el controlador hace `observacion = null`, pero `ActiveRecord`
> normaliza `null → ''` en todo `UPDATE` (intencional). Por tanto "limpiar" = cadena vacía, no `NULL` literal;
> el banner usa `!empty()` así que `''` lo oculta igual.
>
> **QA visual en navegador — ✅ VERIFICADO:** (a) banner de observación con `.alert-persistente` **no**
> desaparece a los 3 s mientras los flash de CRUD sí; (b) consola sin violaciones de CSP y `data-confirm`
> pide confirmación; (c) entrada de menú presente en los 3 layouts; (d) árbol de `revisar` read-only
> (actividad sin indicador en rojo, panel de decisión solo contador/admin si Enviado); (e) upsert de
> indicadores en `detalle_actividad` (1 fila, precarga al reeditar). ⚠️ admin local = `robertokar97@gmail.com`.

## 4 — POA Presupuestal (estados + flujo)  ✅ COMPLETADO (QA HTTP automatizado 18/18, 2026-06-04)
- [x] Estados 0-3 en `poa` (Borrador/Enviado/Observado/Aprobado) — reemplaza la semántica vieja del modal (En espera/Completado/Verificado). Constantes + helpers en `models/Poa.php` (`porProgramaAnio`, `esEditable`, `etiquetaEstado`, `presupuestoCalculado`). Migración **016**: `poa.observacion` varchar(500).
- [x] Flujo Coordinador↔Contador en `PoaController` (index/crear/enviar/observar/aprobar/revisar) **igual al Item 3**: vista `poa/revisar` read-only (árbol Resultado→Producto→Actividad→**Rubros** + total) y **comentario obligatorio** al observar (decisión 2026-06-04, no "sin comentario"). `enviar`/`aprobar` limpian la observación.
- [x] **Presupuesto** del documento = Σ `rubro.monto` del programa (`presupuestoCalculado`, S/ base); se calcula al iniciar y **se congela al enviar**. El legado `/reporte/guardarpoa` ahora hace upsert en **Borrador** (sin forzar estado) respetando el bloqueo; `/reporte/modificarpoa` quedó **deprecado** (redirige).
- [x] **Decisión confirmada (2026-06-04):** el `presupuesto_comprometido` **NO** se calcula al aprobar; se acumula desde **rendiciones + otros egresos** por fuente (`ff_id`) — corresponde a los items 5/6/8. El POA Presupuestal es solo planificación.
- [x] **Bloqueo de rubros** al Enviar/Aprobar: helper `exigirPoaPresupuestalEditable` / `...PorActividad` (redirect+flash `resultado=16`) en `RubroController` (crear/actualizar/eliminar) + **banner y botones ocultos** en `rubro/admin` (prevención UI). Admin/Contador pasan (adenda).
- [x] Máximo un POA Presupuestal por programa/año (`crear` valida `porProgramaAnio` → `resultado=17`). Tras aprobado: el coordinador no edita (solo Contador como adenda, vía el helper que solo restringe a coordinadores).
- [x] Rutas `/poa/{admin,revisar,crear,enviar,observar,aprobar}` por rol (iadmin todas; iconta admin/revisar/observar/aprobar; icoordi admin/revisar/crear/enviar). Entrada de menú "POA Presupuestal" en los 3 layouts. Códigos notif. 16/17.
- [x] CSRF: las rutas de flujo (`/enviar|/observar|/aprobar`) ya quedaron protegidas en el fix del Item 3.

> **QA:** arnés `database/qa_poa_presupuestal.ps1` (18/18) — login, CSRF 419, autorización por rol, cross-tenant 403, flujo 0→1→2→1→3 en BD, presupuesto calculado (28000) y congelado, bloqueo de rubros en Enviado **y** Aprobado, observar sin/con comentario, transición inválida, rechazo de GET. **Hallazgo corregido:** `consultarPreparado()` pasa filas por `crearObjeto()` que descarta columnas fuera de `$columnasDB` (alias de agregación) → `presupuestoCalculado` ahora lee el escalar con mysqli directo.

## 5 — Rendiciones  ✅ COMPLETADO (QA HTTP automatizado 10/10, 2026-06-04)
- [x] **Decisión técnica (2026-06-04):** la rendición se imputa **directo a un rubro**. Migración **017**: `rendicion` += `rubro_id` (FK), **se elimina `actividad_id`**, y se **limpia** la tabla (datos de prueba). La actividad se deriva por `rubro → actividad`. Las **5 vistas SQL** que dependían de `rendicion.actividad_id` (`rendicion_admin`, `total_monto_rendiciones_por_actividad`, `reporte_rendiciones`, `reporte_poa_rendicion`, `reporte_fuentes_programa`) se reescribieron para derivar la actividad desde el rubro, **conservando sus columnas de salida** (incl. `actividad_id`) + agregando `rubro_id` → reportes intactos.
- [x] **Límite por rubro:** `Rendicion::totalImputadoAlRubro()` + `validarLimiteRubro($rubroMonto)` → Σ rendiciones (incluida la actual, excluyéndose a sí misma en edición) ≤ `rubro.monto`; error con saldo disponible. Validado en `crear` y `actualizar`.
- [x] Emisor solo **RUC + razón social** (ya en esquema; sin DNI). `Rendicion` model: `rubro_id` en `columnasDB`, `validar` exige rubro. `estado`/`poa_rendicion_id` se omiten del model a propósito (DEFAULT 0 / NULL; su gestión es del item 6).
- [x] **Navegación reorganizada a por-rubro:** `rubro/admin` tiene botón "Rendiciones" por rubro → `/rendicion/admin?rubro_id=`; el panel muestra monto del rubro / total rendido / disponible. Se quitó el enlace de comprobantes de `actividad/admin`. `RendicionController` (index/crear/actualizar/eliminar) reescrito a `rubro_id`; nuevos helpers `programaIdPorRubro` / `exigirProgramaPropioPorRubro`. `RendicionAdminVista` += `rubro_id`.
- [x] **Bloqueo de rendiciones por estado del POA Presupuestal** (fix 2026-06-05): mientras el POA Presupuestal del programa esté **Enviado(1) o Aprobado(3)**, el **Coordinador** NO puede crear/editar/eliminar rendiciones en sus rubros (mismo candado que congela los rubros, reutiliza `poaPresupuestalEditable`). Contador/Admin pasan (adenda). Guard en `RendicionController` (crear/actualizar/eliminar) → redirige a `/rendicion/admin?...&resultado=18`; en `rubro/admin` el botón "Rendiciones" sigue disponible (read-only), pero en `rendicion/admin` se ocultan "Agregar" y las acciones de fila y se muestra banner (`.alert-persistente`) + candado. Nuevo código de notificación **18**.

> **QA:** arnés `database/qa_rendicion.ps1` (10/10) — login, CSRF 419, cross-tenant 403 (rubro ajeno), creación imputada al rubro (verifica `rubro_id`/`estado=0`/`poa_rendicion_id=NULL`), límite por rubro (rechaza 4000>3500 con mensaje, acepta el tope exacto 3500), rechazo de GET en eliminar. Sin regresión en items 3/4 (18/18 c/u).
>
> ✅ **VERIFICADO (automatizado):** `database/qa_rendicion.ps1` cubre el **bloqueo de rendiciones por estado
> del POA Presupuestal** (fix 2026-06-05): pone el POA del programa 1 en **Enviado**, verifica que el
> coordinador NO puede crear/editar/eliminar rendiciones (redirect `resultado=18`, sin inserción en BD),
> confirma que **Contador/Admin sí pueden** (adenda), y **revierte** el estado del POA al terminar
> (auto-limpieza, como el resto de arneses). Pasó exitosamente.

## 6 — POA Rendición  ✅ COMPLETADO (QA HTTP 21/21, 2026-06-05)
> **Decisión 2026-06-05 (reencuadre del Grupo 9):** el **"POA Rendición" NO es un documento
> aparte**: es **el mismo POA Presupuestal** (`poa`). El "POA Rendición" que veía el usuario es el
> **reporte Excel** de ese documento (`/reporte/poarendicion`), que **se mantiene tal cual**. Por
> tanto **no** se construye modelo/flujo/vistas/rutas `PoaRendicion`; la tabla `poa_rendicion`
> (migr. 005) y `rendicion.poa_rendicion_id` (migr. 006) **quedan vestigiales** (solo se usa
> `rendicion.estado`). El ciclo de la rendición lo gobierna el flujo del POA Presupuestal (item 4).
- [x] **Bloqueo al enviar/aprobar**: ya cubierto por el candado del item 5 (`resultado=18`): cuando el
  POA Presupuestal está Enviado(1)/Aprobado(3) el Coordinador no agrega/edita/elimina rendiciones.
- [x] **Al aprobar el POA Presupuestal** (`PoaController::aprobar`) → `Rendicion::aprobarPorPrograma()`
  marca **todas** las rendiciones del programa como **Aprobada (estado=1)** y las congela. Constantes
  `Rendicion::PENDIENTE=0` / `APROBADA=1`. `estado` se mantiene **fuera de `$columnasDB`** (el CRUD no lo
  toca; INSERT toma DEFAULT 0); la aprobación es un `UPDATE` directo (join rubro→actividad→…→programa).
- [x] **Descuento del saldo contable**: migración **018** reescribe las 2 vistas de saldo contable
  (`vista_total_egresos`, `vista_saldo_fuente_financiamiento`) para restar **solo** rendiciones
  `estado=1`. Antes de aprobar, una rendición Pendiente(0) **no** afecta el saldo; al aprobar, sí.
  Migración **019**: backfill de rendiciones de programas con POA ya Aprobado (idempotente; no afecta greenfield).
- [x] **Adenda**: si el Contador registra una rendición sobre un POA ya Aprobado, nace Aprobada
  (auto-aprobación en `RendicionController::crear`) para que el saldo la refleje al instante.
- [ ] Re-apertura por Contador → *diferido v1.1* (MVP = aprobar una vez).

> **QA:** integrado en `database/qa_poa_presupuestal.ps1` (ahora **21/21**): una rendición Pendiente(0)
> no mueve `vista_total_egresos`; al aprobar el POA pasa a Aprobada(1) y el saldo la descuenta
> (Δ egresos = monto). Crea y limpia su propia rendición de prueba (programa 1). Suite completa OK.
