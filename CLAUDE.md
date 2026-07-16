# CLAUDE.md — SysAI · Organización Arco Iris

> **Memoria caliente del proyecto: contexto técnico y de negocio para el desarrollo del sistema.**
> Este documento es la fuente de verdad *operativa*. El detalle histórico y de referencia vive en `docs/`
> (ver índice abajo) y se lee solo cuando hace falta, para no cargar tokens innecesarios cada sesión.
>
> ✅ El **sprint de seguridad** ya está integrado y mergeado a `main` (SMTP en `.env`, migraciones 010-012
> en el runner). La **producción de Hostinger fue dada de baja** → el próximo despliegue es **greenfield**
> (proyecto + BD nuevos desde cero). Detalle en `docs/historial-seguridad.md`.

### Índice de referencia (`docs/`, leer bajo demanda)
- **`docs/plan-comprometido-y-tope-poa.md`** — ✅ **IMPLEMENTADO (2026-07-15, Fases A y B, QA 30/30)**: higiene
  del término "comprometido" + item 4 (tope del POA por Σ sobres y puerta de sobres). Se conserva como registro
  de decisiones. (El techo `decimal(8,2)` que heredaba el tope quedó resuelto por la migr. 026.)
- **`docs/plan-montos-y-tipo-cambio.md`** — ✅ **IMPLEMENTADO (2026-07-15, Fases 0-6, migr. 026-031, suite QA
  101/101)**: montos a escala real, `montoNumerico()`, capacidad asignable, tabla `tipo_cambio` unificada
  (compra/venta por `fecha_vigencia`), TC congelado en transacciones, reportes que nunca revientan y consulta
  SBS informativa. Se conserva como registro de decisiones.
- `docs/historial-implementacion-items-2-6.md` — construcción + QA de los items **completados** (2 al 6).
- `docs/historial-migraciones.md` — tabla completa de migraciones 001-019 + estado histórico de la BD.
- `docs/modelo-datos-detalle.md` — lista completa de vistas SQL + discrepancias/deuda de esquema.
- `docs/qa-automatizado.md` — detalle de los arneses de QA HTTP.
- `docs/historial-seguridad.md` — sprint de hardening (hecho/mergeado).
- `docs/build-assets.md` — pipeline Gulp.
- `docs/follow-ups-tecnicos.md` — deuda técnica pendiente (detalle).

---

## [SECCION: ENTORNO]

- **Proyecto:** SysAI — Sistema de gestión presupuestal y rendición de cuentas para ONG.
- **Organización:** Arco Iris (ONG sin fines de lucro, **Huaraz, Perú**).
- **Repositorio:** `KorTecCorDev/sysai_1` (privado, GitHub).
- **Autor original:** Karlos Colonia Arellano.
- **Stack:** PHP MVC (sin framework) + Active Record propio · MySQL/MariaDB · Bootstrap 5 · SCSS/Gulp · PHPSpreadsheet · PHPMailer.
- **Entorno local:** XAMPP (Windows) — `C:/xampp/htdocs/sysai`. BD local: `sysai`.
- **Producción:** **Hostinger** (corre **PHP 8.2.12**, igual que el dev local — ver *Setup*).
  ⚠️ La instancia anterior fue **dada de baja el 2026-06-03** (BD `u612374195_sysai`, decomisionada). El próximo
  despliegue va **sobre Hostinger otra vez, pero greenfield**: proyecto y BD nuevos, sin datos que preservar ni
  reconciliar.
- **Separación de entornos:** `.env` por entorno (ignorado en git). `.env.example` versionado como plantilla.
- **Credenciales de BD:** Solo en `.env`, nunca hardcodeadas. `includes/config/database.php` ignorado en git (lee `.env` y conecta MySQL).
- **Moneda base:** Sol peruano (PEN / S/). Conversiones a USD/EUR solo para reportes.

---

## [SECCION: STACK Y DEPENDENCIAS]

- **Backend:** PHP puro, arquitectura MVC casera. Patrón ActiveRecord propio (estilo cursos de Juan de la Torre / DevWebCamp).
- **Base de datos:** MySQL/MariaDB vía `mysqli` (conexión única global). Uso intensivo de **VISTAS SQL** (los modelos con sufijo `*Vista` mapean vistas, no tablas). Mecanismo de auditoría con `SET @usuario_actual` (triggers que registrarían quién modifica — ver *[SECCION: MODELO DE DATOS]*).
- **Frontend:** Bootstrap 5 + Bootstrap Icons; SASS compilado con **Gulp** (`gulpfile.js`). Assets compilados en `build/` (CSS/JS/img); fuente en `src/`. Pipeline detallado en `docs/build-assets.md`.
- **Dependencias Composer (`composer.json`):**
  - `phpoffice/phpspreadsheet` ^4.1 — generación de reportes Excel.
  - `phpmailer/phpmailer` ^6.9 — envío de correos (recuperación de contraseña).
  - `intervention/image` 2.7 — manejo de imágenes.
  - `twbs/bootstrap-icons` ^1.11.
- **Namespaces PHP (PSR-4):** `Model\` → `models/`, `Controllers\` → `controllers/`, `MVC\` → raíz.

---

## [SECCION: SETUP / DEVSTACK]

**Entorno real de la PC de desarrollo (verificado):**
- **PHP 8.2.12 — versión oficial del proyecto (decisión 2026-07-15).** Es la de **XAMPP** (`C:\xampp\php\php.exe`,
  la del `PATH`) y **la misma que corre Hostinger**, el servidor de producción → dev y prod alineados. `php.ini`
  en `C:\xampp\php\php.ini`. Es la que usan `composer`, `php` y el servidor de desarrollo.
  - ✅ Verificado bajo 8.2.12: `zip`, `openssl`, `curl`, `mysqli`, `mbstring`, `gd` activas, y PhpSpreadsheet,
    PHPMailer e Intervention cargan y escriben un `.xlsx` real. `vendor/composer/platform_check.php` exige
    `PHP_VERSION_ID >= 80200`; `composer.json` no fija versión de PHP. `intl` está **inactiva** (hoy nadie la usa).
  - ⚠️ **Histórico:** hasta el 2026-07-15 este documento declaraba "PHP CLI 8.3.x en `C:\php` (standalone, NO el
    de XAMPP)". **`C:\php` ya no existe.** Si algún script o permiso invoca `C:\php\php.exe`, está roto.
- **MariaDB de XAMPP** en `127.0.0.1:3306` (binario `C:\xampp\mysql\bin\mysql.exe`, root sin contraseña).
- **Composer 2.9**, **Node 24 / npm 11**.
- **Servidor de desarrollo:** alias `local3000` = `php -S localhost:3000` ejecutado **desde la raíz del proyecto**. App en **http://localhost:3000**.
  - ✅ El servidor embebido sirve los assets de `build/` directos; las rutas inexistentes caen a `index.php` (front controller) que lee `REQUEST_URI`. No requiere vhost ni Apache.
  - ⚠️ **`php -S` NO procesa `.htaccess`** → en dev NO aplican los bloqueos de `controllers/`, `models/`, `*.sql`, `.env`, etc. Los `.htaccess` solo protegen en producción (Apache/Hostinger).
  - 🔴 **Requisito TLS (Windows) para SMTP — HOY SIN CUMPLIR.** Verificado el 2026-07-15: en
    `C:\xampp\php\php.ini` tanto `openssl.cafile` como `curl.cainfo` están **vacíos**. La receta estaba escrita
    para el desaparecido `C:\php\php.ini`, así que se perdió al migrar a la PHP de XAMPP. **No molesta mientras
    `MAIL_USERNAME`/`MAIL_PASSWORD` sigan vacíos** (modo DEV: el token va a `includes/logs/mail.log` y no se envía
    correo), pero **el día que se configure SMTP real, la verificación TLS fallará**. Arreglo: en
    `C:\xampp\php\php.ini` apuntar ambas a `C:\xampp\apache\bin\curl-ca-bundle.crt` y reiniciar el servidor.

**Pasos de arranque:**
```bash
composer install                      # vendor/  (PhpSpreadsheet, PHPMailer, intervention/image…)
npm install                           # node_modules/ (Gulp)
npm run dev                           # = gulp; recompila build/ (opcional: build/ ya viene compilado)

# Base de datos (enfoque ACTUAL — runner de migraciones):
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE sysai CHARACTER SET utf8 COLLATE utf8_general_ci;"
"C:\xampp\mysql\bin\mysql.exe" -u root sysai < database/schema_baseline.sql   # baseline versionado (sin datos)
php database/migrate.php                                                       # aplica migrations/001-032
"C:\xampp\mysql\bin\mysql.exe" -u root sysai < database/seed.sql              # catálogos + admin inicial

# Arrancar (desde la raíz del proyecto):
local3000                             # php -S localhost:3000  → http://localhost:3000
```

> **Para vaciar/recrear la BD local antes de reimportar** (XAMPP): phpMyAdmin trae `DROP DATABASE`
> deshabilitado (`$cfg['AllowUserDropDatabase']=false`). Usar la consola MySQL —no tiene esa restricción—:
> `DROP DATABASE IF EXISTS sysai; CREATE DATABASE sysai CHARACTER SET utf8 COLLATE utf8_general_ci;`

**Configuración por entorno (secretos, NO versionados):**
- `includes/config/database.php` — conexión (lee `.env`). Gitignored. Plantilla: `.env.example`.
  `conectarDB()` hace `mysqli_report(MYSQLI_REPORT_OFF)` porque el código comprueba valores de retorno (no usa try/catch).
- **SMTP / recuperación de contraseña:** el flujo `/chgpsswd → /token_verify → /updtepsswd` usa PHPMailer.
  ✅ Credenciales ya externalizadas: `includes/config/mail.php` lee las claves `MAIL_*` del `.env` (helper
  `enviarTokenRecuperacion()`). En **modo DEV** (`MAIL_USERNAME`/`MAIL_PASSWORD` vacíos) el token solo se
  escribe en `includes/logs/mail.log`, no se envía correo real.
- **Credenciales de prueba / QA local:** coordinador `coordinador@sysai.test` / `Test1234*` (programa 1);
  contador `contador@sysai.test` / `admin1234` (= admin local `robertokar97@gmail.com`).
  ⚠️ **No re-sembrar ni resetear `usuario.password`** en la BD local — el usuario gestiona sus contraseñas
  vía el flujo de cambio; `seed.sql`/`UPDATE` los pisarían. Seed idempotente `INSERT IGNORE` para alta inicial únicamente.

---

## [SECCION: ARQUITECTURA]

```
index.php          → Punto de entrada (front controller). Arranca sesión, fija CSP, carga includes/app.php,
                     registra rutas públicas y, según cargo_id de sesión, incluye el archivo de rutas del rol.
iadmin.php         → Rutas del Administrador (cargo_id=1, layout_admin.php)
iconta.php         → Rutas del Contador (cargo_id=2, layout_contador.php)
icoordi.php        → Rutas del Coordinador (cargo_id=3, layout_coordinador.php)
Router.php         → Enrutador MVC personalizado (MVC\Router)
includes/app.php   → Bootstrap: funciones, config DB, autoload, conexión
includes/config/database.php → Lee .env, conecta MySQL (gitignored)
controllers/       → ~18-19 controladores (lógica de negocio)
models/            → ~56-80 modelos (Active Record + vistas SQL)
views/             → ~82 vistas por módulo (admin, crear, actualizar, formulario)
database/          → migrate.php, migrations/, schema_baseline.sql, seed.sql, README.md
build/             → CSS/JS/IMG compilados (output de Gulp)
src/               → SCSS y JS fuente
```
> Los conteos de controladores/modelos/vistas son orden de magnitud, no cifra exacta.

### Front controller y enrutamiento
1. **`index.php`** arranca sesión, fija cabecera CSP, carga `includes/app.php`, registra rutas públicas
   (login, logout, recuperación) y según **`$_SESSION['cargo_id']`** incluye `iadmin.php` / `iconta.php` /
   `icoordi.php`. Finalmente llama a `$router->comprobarRutas()`.
2. **`Router.php`** (`MVC\Router`) — router minimalista: arrays `rutasGET`/`rutasPOST`, métodos `get()`/`post()`,
   `comprobarRutas()` (despacha por `REQUEST_URI` + método), `render()` y `renderssdbr()` (vistas sin sidebar, p. ej. login).
   - **Autorización por rol = carga condicional de rutas.** Las rutas de admin solo se registran si `cargo_id==1`;
     un coordinador ni siquiera las tiene registradas (caen en 404). Es el principal mecanismo de control de acceso.
   - **CSRF:** `Router::requiereCsrf()` protege los sufijos `/crear|/actualizar|/eliminar|/enviar|/observar|/aprobar|/guardar`
     (POST sin token → 419). Los formularios emiten `csrf_input()`.

### Capa de datos — `models/ActiveRecord.php` (clase base)
- `setDB()`, `guardar()` (decide crear/actualizar por `$this->id`), `crear()`/`actualizar()`/`eliminar()` y
  variantes `*sinRedireccion()` (las normales hacen `header(Location...)` + `exit` tras éxito → la redirección
  está acoplada al modelo; usar las `*sinRedireccion()` para encadenar operaciones).
- Lectura: `all()`, `find($id)`, `findxatributo()`, `findwithparameters()`, `consultarSql()`, etc.
  ⚠️ `consultarPreparado()`/`crearObjeto()` descartan columnas fuera de `$columnasDB` (p. ej. alias de agregación);
  para leer un escalar calculado, consultar con mysqli directo.
- **`sanitizarAtributos()`** escapa en escrituras; **`convertirAMayusculas()`** fuerza TODO string a MAYÚSCULAS
  antes de insertar (decisión de negocio). Excepciones declaradas en `$columnasSinMayuscula = ['password','reset_token','email']`.
  ⚠️ `null` se normaliza a `''` en todo `UPDATE` (intencional): "limpiar" un campo = cadena vacía, no `NULL`.
- Helpers de reportes Excel embebidos: `insertarCeldasReportePOA()`, `insertarRendicionesFuente()`,
  `insertarDatosDesdeArray()`, `combinarCeldasRepetidas()`, `insertarDatosDesdeArrayEgresosRendiciones()`.
- `setUsuarioActual()` ejecuta `SET @usuario_actual = '<descripción>'` para auditoría en BD.

### Modelos, controladores y vistas
- **Modelos de tabla:** `Usuario`, `Persona`, `Cargo`, `Poa`, `Programa`, `Producto`, `Actividad`, `Resultado`,
  `Rubro`, `CategoriaRubro`, `SubCategoriaRubro`, `TipoRubro`, `FuenteFinanciamiento`, `DetalleFinanciamiento`,
  `Rendicion`, `RendicionFf`, `OtrosIngresosEgresos`, `OieComprobante`, `TipoComprobante`, `CoordinadorPrograma`,
  `PoaIndicadores`, `DetalleActividad`, `TipoCambioDolar`, `TipoCambioEuro`, `Login`, etc.
- **Modelos de VISTA SQL** (sufijo `*Vista`): mapean vistas precompuestas. Lista completa en `docs/modelo-datos-detalle.md`.
- **Controladores** (`controllers/`): estáticos, reciben `Router $router`. Patrón CRUD `index/crear/actualizar/eliminar`
  (+ flujo `enviar/observar/aprobar/revisar` en los documentos POA). Renderizan con `$router->render('carpeta/vista', [datos])`.
- **Vistas** (`views/`): una subcarpeta por entidad, cada una con `admin.php`/`crear.php`/`actualizar.php`/`formulario.php`.
  Layouts `layout_admin/contador/coordinador.php` + `layout_login.php`. Helper de escape **`s()`** en
  `includes/funciones.php` (`htmlspecialchars`, ENT_QUOTES, UTF-8, null-safe). Única salida cruda intencional: `echo $contenido` en los layouts.

---

## [SECCION: ROLES]

| cargo_id | Nombre | Capacidades |
|---|---|---|
| 1 | Administrador | Root. Acceso total. |
| 2 | Contador | Coordinador en jefe + aprobador de POAs y rendiciones. Registra OIE (aprobación automática). |
| 3 | Coordinador | Gestiona su programa asignado. Elabora POAs y rendiciones. |

---

## [SECCION: MODULOS FUNCIONALES]

| Módulo | Rutas base | Descripción |
|---|---|---|
| **Login / Auth** | `/login`, `/logout`, `/chgpsswd`, `/token_verify`, `/updtepsswd` | Autenticación, bloqueo por intentos, recuperación de contraseña por email con `reset_token`. |
| **Usuarios** | `/usuario/*` | CRUD de usuarios + `persona` asociada. Coordinador (cargo 3) se vincula a programa. |
| **Programas** | `/programa/*` | Programas de la ONG. |
| **POA** | `/poa/*`, `/reporte/guardarpoa`, `/reporte/modificarpoa` | Plan Operativo Anual; vincula coordinador↔programa. |
| **Resultados / Productos / Actividades** | `/resultado/*`, `/producto/*`, `/actividad/*` | Jerarquía: Resultado → Producto → Actividad. |
| **Rubros / Categorías** | `/rubro/*`, `/categoria_rubro/*` | Partidas presupuestarias (tipo rubro Bien/Servicio). |
| **Fuentes de financiamiento** | `/fuente_financiamiento/*`, `/dfinanciamiento/*` | Fuentes (donantes) y su detalle. |
| **Rendiciones** | `/rendicion/*`, `/rendicionff/*` | Rendición de cuentas con comprobantes (RUC, serie, número, monto) por fuente. |
| **Otros Ingresos/Egresos (OIE)** | `/ingreso_egreso/*` | Movimientos no ligados a rendición, con comprobantes. |
| **Tipos de cambio** | `/tcambio/*?moneda=USD\|EUR` | TC unificado (migr. 028): compra/venta por `fecha_vigencia`; el vigente a una fecha se resuelve por fecha, no por orden de registro. `/tcambio/sbs` = consulta informativa que pre-llena el formulario. |
| **Reportes** | `/reporte/poa`, `/reporte/rendiciones`, `/reporte/ingresos`, `/descargar`, … | Exportación Excel con PhpSpreadsheet, con conversión de moneda. |
| **Saldos contables** | `/saldos_contables/saldos` | Saldos por fuente de financiamiento. |

---

## [SECCION: REGLAS DE NEGOCIO — CONFIRMADAS]

> Decisiones de negocio confirmadas (Grupos 8-13, sesiones 2026-06-02/05). **No volver a preguntar.**

### Fuentes de Financiamiento
- Una fuente es una organización de caridad con presupuesto anual.
- El monto es referencial hasta que se elaboren los POAs presupuestales.
- Una fuente puede estar vinculada a múltiples programas (`detalle_financiamiento`). Solo las fuentes vinculadas al programa pueden usarse en rendiciones. No hay límite de fuentes por programa.
- **Dos tipos de presupuesto por fuente:**
  - `presupuesto_comprometido` = **Σ de los sobres asignados** (`detalle_financiamiento.monto_asignado`) de esa fuente (enmienda 2026-07-09; antes era "Σ POAs aprobados que usan la fuente", que nunca se calculó). Lo expone `SaldoFuenteFinanciamientoVista::desglosePorFuente()`.
  - `presupuesto_contable`: monto inicial + ingresos − rendiciones aprobadas − otros egresos.
- ✅ **TRES CIFRAS DE PRESUPUESTO (migr. 027, plan de montos §2.4):** `presupuesto` (inicial) **no se muta** con
  los ingresos; se calculan en vivo: **vigente** = inicial + TODOS los ingresos OIE; **capacidad asignable** =
  inicial + ingresos **sin programa** (`programa_id NULL`) − Σ sobres. Un ingreso dirigido a un sobre **no**
  amplía la capacidad asignable (ya es asignación — evita contar el dinero dos veces).
  `DetalleFinanciamiento::validarLimiteAsignacion()` compara contra la **capacidad asignable**.
- ✅ **Saneamiento de montos (migr. 026):** `presupuesto` es `decimal(14,2)` (antes `decimal(8,2)` ≈ S/ 1M de
  techo con truncamiento SILENCIOSO). Todo campo de dinero pasa por **`montoNumerico()`** (helper único en
  `includes/funciones.php`: tolera "S/", comas de miles y coma decimal; lo no numérico ⇒ error visible) y por el
  tope de cordura **`MONTO_MAXIMO` = S/ 50M**. Inputs `type="number" step="0.01"`.
- ⚠️ **SUB-PRESUPUESTOS ("sobres") — enmienda 2026-07-09 (migr. 020, REVIERTE la regla previa):** el presupuesto de la fuente **SÍ se reparte por programa** en "sobres" exclusivos (`detalle_financiamiento.monto_asignado`). Invariante: **Σ sobres por fuente ≤ presupuesto** (se permite remanente sin asignar). El sobre `(programa, fuente)` es la unidad contra la que se gasta. Se captura al vincular fuente↔programa (`/dfinanciamiento/crear`), validado por `DetalleFinanciamiento::validarLimiteAsignacion()`.
- El saldo contable sobrante al cierre del año se traslada al siguiente periodo (`fuente_presupuesto_anual`).

### Programa
- Cada programa tiene **uno y solo un coordinador activo**.
- Vínculo coordinador-programa mediante tabla intermedia `coordinador_programa` (usuario_id, programa_id, activo).
- Al cambiar coordinador: desactivar vínculo anterior (`activo=0`), crear nuevo vínculo (`activo=1`). Un coordinador solo puede tener un programa activo a la vez.
- Jerarquía: Programa → Resultado → Producto → Actividad → **Rubro** (POA Presupuestal) / **Indicadores** (POA Indicadores).

### POA Indicadores
- Se crea **antes** del POA Presupuestal.
- Documento con **estados**: Borrador(0) → Enviado(1) → Observado(2) → Aprobado(3). Mismo flujo que el Presupuestal.
- La jerarquía Resultado → Producto → Actividad es **compartida** con el POA Presupuestal (el Presupuestal solo agrega Rubros a la misma estructura, sin duplicar).
- Indicadores solo a nivel de Actividad (`indicador_producto`/`indicador_resultado` son de uso futuro).
- **Al Observar, el Contador escribe un comentario obligatorio** (`poa_indicadores.observacion`, migr. 015) que se muestra al Coordinador para subsanar. (Enmienda 2026-06-04 a la regla original "retorno sin comentario".)

### POA Presupuestal
- Se elabora a partir de la estructura del POA Indicadores. **Estados:** Borrador(0) → Enviado(1) → Observado(2) → Aprobado(3).
- El Coordinador edita hasta que el Contador aprueba. Al Observar, el Contador escribe comentario obligatorio (`poa.observacion`, migr. 016); `enviar`/`aprobar` lo limpian.
- Presupuesto del documento = Σ `rubro.monto` del programa; se calcula al iniciar y **se congela al enviar**.
- Luego de aprobado: solo visible para el Coordinador; solo el Contador puede modificarlo (como **adenda**).
- Máximo un POA Presupuestal activo por programa por año.
- ⚠️ El `presupuesto_comprometido` **NO** se calcula al aprobar el POA; es **Σ de los sobres** (`monto_asignado`) de la fuente (ver *Fuentes*). El POA Presupuestal es solo planificación.
- ⚠️ **TOPE DEL POA POR SOBRES — confirmado 2026-07-15 (✅ IMPLEMENTADO 2026-07-15, QA 30/30):**
  el POA **no puede exceder la suma de los sobres del programa**: `Σ rubro.monto ≤ Σ detalle_financiamiento.monto_asignado`
  del programa. Es un **tope agregado**, no por fuente: `rubro` **no tiene `ff_id`** (verificado en esquema), así
  que un rubro no sabe de qué sobre sale y el control sobre-por-sobre no es expresable sin migración. Un programa
  con sobres de 600k (fuente A) y 400k (fuente B) tiene tope de POA = 1M, repartible como sea entre ambos.
  Se valida en `enviar()` **y** en `aprobar()` — revalidar al aprobar no es redundante: los sobres pueden bajar
  entre el envío y la aprobación. Implementación: `Poa::topeSobres()` + `Poa::validarTopeSobres()`; al fallar
  redirige con `resultado=20` (tope excedido) o `19` (Σ sobres = 0). La UI muestra `Σ rubros` vs `Σ sobres` con
  el margen en `/poa/admin` y `/poa/revisar`. Descartadas: la variante por fuente (exigiría `rubro.ff_id`) y la
  de no poner tope.
  > ✅ El techo `decimal(8,2)` que heredaba el tope quedó resuelto: la **migr. 026** ensanchó
  > `fuente_financiamiento.presupuesto` a `decimal(14,2)` — el tope ya opera con montos reales (S/ 1M-10M).
- ⚠️ **SIN SOBRES NO HAY PRESUPUESTO — confirmado 2026-07-15 (✅ IMPLEMENTADO 2026-07-15, QA 30/30):**
  con **Σ sobres = 0** (programa sin fuentes vinculadas), el Coordinador **no puede registrar nada presupuestal**:
  ni rubros, ni POA Presupuestal, ni rendiciones. El Contador debe asignar al menos un sobre primero
  (`/dfinanciamiento/crear`); con **≥ 1 sobre** el Coordinador ya puede editar y registrar.
  **El bloqueo NO alcanza al POA Indicadores** ni a la jerarquía Resultado→Producto→Actividad: no manejan dinero
  y el POA Indicadores se elabora **antes** que el Presupuestal (ver *POA Indicadores*). El coordinador puede
  planificar indicadores sin financiamiento; lo que no puede es presupuestar.
  Implementación: helper `exigirSobreAsignado()` (`includes/funciones.php`) en las 8 rutas presupuestales del
  Coordinador (`/rubro/crear|actualizar|eliminar`, `/poa/crear|enviar`, `/rendicion/crear|actualizar|eliminar`);
  redirige a `/poa/admin?resultado=19` con banner **persistente** que distingue "aún no tienes sobres" (esperar
  al Contador) de "excediste el tope". El sidebar del coordinador oculta "POA Presupuestal" con Σ sobres = 0.
  Contador/Admin **no** pasan por la puerta (adenda).

### Rubros
- Un rubro es un Bien o Servicio (`tipo_rubro`: TRB001=Bien, TRB002=Servicio).
- Tiene un monto de **planificación**. ⚠️ **Enmienda 2026-07-09 (migr. 020, decisión "solo el sobre"):** el rubro **ya NO limita el gasto** — el tope de una rendición es el **saldo del sobre** `(programa, fuente)`, no `rubro.monto`. El rubro queda como clasificación/imputación. Pueden registrarse múltiples rendiciones por rubro.
- ✅ **Saldo con signo (migr. 027, §2.3):** `vista_saldo_rubro` (`saldo = monto − Σ rendiciones`; negativo =
  **sobregasto**). Al registrar una rendición que cruza el monto del rubro se **avisa sin impedir**
  (`resultado=21`); el saldo con signo se muestra en `rubro/admin`, `rendicion/admin` y el formulario de
  rendición. No revierte la enmienda: el tope duro sigue siendo el sobre.

### Rendiciones
- Las elaboran Coordinador y Contador. Siempre vinculadas a un **rubro** (la actividad se deriva por rubro→actividad).
- Una rendición usa **una sola fuente** vinculada al programa.
- Documentos de sustento (`tipo_comprobante`): factura, **boleta de venta**, **boleta de viaje**, **recibo de caja**, **recibo de servicio básico**, recibo de viaje, declaración jurada, recibo de pago de servicios, recibo general. (La "boleta" genérica se desdobló en venta/viaje — decisión 2026-06-03.)
- Datos obligatorios: **RUC** + **razón social / nombre** (identifica a cualquier proveedor, empresa o persona natural). **No se usa DNI.**
- El monto no puede exceder el **saldo disponible del sobre** `(programa, fuente)` (`Rendicion::validarLimiteSobre()` → `DetalleFinanciamiento::saldoSobre()`). El "disponible para comprometer" cuenta rendiciones de **todo estado** (evita sobre-comprometer); el saldo **contable** solo las aprobadas.
- Nace **Pendiente (estado=0)** y NO afecta el saldo contable hasta que se aprueba.
- ✅ **TC congelado (migr. 029):** al registrar, copia el TC de **venta** vigente a su `fecha_original`
  (`Rendicion::congelarTipoCambio()`); sin cobertura queda "pendiente de TC" (no bloquea el registro, pero sí
  la aprobación del POA — ver *Tipo de Cambio*).

### POA Rendición (= el mismo POA Presupuestal)
- ⚠️ **Reencuadre 2026-06-05:** el "POA Rendición" **NO es un documento aparte**: es el **mismo POA Presupuestal** (`poa`). Lo que el usuario llama "POA Rendición" es el **reporte Excel** (`/reporte/poarendicion`). La tabla `poa_rendicion` (migr. 005) y `rendicion.poa_rendicion_id` (migr. 006) quedan **vestigiales**; solo se usa `rendicion.estado`.
- Una vez el POA Presupuestal está **Enviado(1)/Aprobado(3)**, el Coordinador queda bloqueado (no agrega/edita/elimina rendiciones; `resultado=18`). Contador/Admin pasan (adenda).
- **Al aprobar el POA Presupuestal**, todas las rendiciones del programa pasan a **Aprobada(1)**, se congelan y descuentan el `presupuesto_contable` de cada fuente.
- Adenda del Contador sobre POA ya Aprobado: la rendición nace Aprobada (descuenta al instante).
- Re-apertura por el Contador → *diferido v1.1*.

### Otros Ingresos / Egresos (OIE)
- **SOLO los registra el Contador** (y Admin) — aprobación automática (descuento/suma inmediata sobre `presupuesto_contable`; las vistas de saldo calculan en vivo). ✅ El coordinador ya no tiene rutas OIE ni enlace en su sidebar (item 7, 2026-07-14); defensa en profundidad con `exigirRol([1,2])` en el controlador.
- Ingresos (nuevas donaciones) suman; Egresos (gastos fuera del POA) restan. Monto en `oie_comprobante.monto`. Incluyen recibo con datos del benefactor/proveedor.
- **Ingreso híbrido (enmienda 2026-07-09):** puede ir al **total de la fuente** (remanente sin asignar; `otros_ingresos_egresos.programa_id` **NULL**, migr. 020) o a un **programa concreto** (su sobre). El **egreso** siempre lleva programa y descuenta del sobre `(programa, fuente)`.
- ✅ **Item 7 COMPLETADO (2026-07-14, QA 22/22):** CRUD en un solo paso (se eliminó el flujo en dos pasos `/ingreso_egreso/ff`). Validaciones en `OtrosIngresosEgresos`: egreso exige programa, el par (programa, fuente) debe tener sobre (`DetalleFinanciamiento::existeVinculo()`), y el egreso no puede exceder el disponible del sobre (`validarTopeSobre()` → `saldoSobre()`, que ahora acepta excluir un OIE en edición). Eliminar borra el OIE **y su comprobante**. `programa_id` NULL real vía `ActiveRecord::$columnasNull` (opt-in). Vista de listado recreada con LEFT JOIN (migr. 025).

### Tipo de Cambio (✅ reescrito 2026-07-15, plan de montos — migr. 028-030)
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
  ⚠️ El mapeo compra/venta está derivado por lógica, no por norma: **confirmar con el contador** (§5.3 del plan).
- **La tasa SBS es informativa**: botón "Consultar SBS" pre-llena el formulario (endpoint configurable
  `SBS_API_URL` en `.env`, timeout 5 s, degradación limpia) y `database/importar_tc_sbs.php` hace el backfill en
  lote (origen='SBS', `INSERT IGNORE`). Nunca corre en la ruta de un reporte; nunca se guarda sin el Contador.

### Saldos
- Saldo **fuente** = presupuesto + ingresos − rendiciones_aprobadas − otros_egresos (`vista_saldo_fuente_financiamiento`,
  que desde la migr. 027 expone además `presupuesto_inicial` / `presupuesto_vigente` / `capacidad_asignable`).
- ✅ La pantalla convierte el saldo total al **cierre** (TC **compra** vigente a hoy) mostrando siempre tasa,
  fecha de vigencia y origen; sin TC muestra "sin tipo de cambio registrado" — nunca revienta ni inventa.
- Saldo **sobre** `(programa, fuente)` = monto_asignado + ingresos_al_sobre − egresos − rendiciones_aprobadas (`vista_saldo_sobre`, migr. 021).
- Pantalla `/saldos_contables/saldos` (Admin/Contador): 5 niveles → KPIs globales · gráfico SVG por fuente · tarjetas por fuente (con comprometido = Σ sobres y capacidad asignable) · tabla por sobre · **histórico anual** (item 8).
- ✅ **Saldo de programa visible solo con POA aprobado (item 8):** la fila del sobre siempre aparece, pero las
  cifras solo se muestran si el POA Presupuestal del programa (año vigente) está **Aprobado**; si no, badge
  "POA no aprobado" (las rendiciones pendientes no descuentan → el saldo aún no es firme).
- Al registrar rendiciones aprobadas u OIE, los saldos se actualizan (las vistas calculan en vivo).

### Cierre Anual (✅ IMPLEMENTADO 2026-07-16, item 8 — decisiones confirmadas ese día)
- El saldo sobrante de cada fuente al cierre del año se registra en `fuente_presupuesto_anual` (fuente_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable). Permite el histórico año a año.
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

---

## [SECCION: MODELO DE DATOS]

> Charset baseline real: **`InnoDB`, `CHARSET=utf8 COLLATE=utf8_general_ci`**, `datetime` para `fecha`.
> Las vistas del dump original traían `DEFINER=root@localhost` → ⚠️ al importar en otro host ajustar/quitar definer.
> **Lista completa de vistas SQL + discrepancias/deuda de esquema: `docs/modelo-datos-detalle.md`.**

### Jerarquía de planificación (POA)
```
programa (tipo_programa)
  └─ resultado            (+ resultado_detalle, indicador_resultado, avance_resultado)
       └─ producto        (+ producto_detalle, indicador_producto, avance_producto)
            └─ actividad   (+ detalle_actividad, indicador_actividad, avance_actividad)
                 └─ rubro  (categoria_rubro → subcategoria_rubro; tipo_rubro: TRB001=Bien, TRB002=Servicio)
poa (programa, anio, presupuesto, estado, usuario_id→coordinador)
detalle_financiamiento  (N:M programa ↔ fuente_financiamiento; `monto_asignado` = sobre, migr. 020)
```
> `detalle_actividad` es la tabla base de los indicadores del POA Indicadores (`indicador_medido`,
> `medio_verificacion`, `supuesto`, `responsable`). Las tablas `*_detalle`, `indicador_*` y `avance_*`
> existen pero están **vacías / sin controladores** (uso futuro — ver *[SECCION: DIFERIDO A v1.1]*).

### Tablas de movimientos contables
- **`rendicion`** — gasto rendido imputado a un **rubro**: `rubro_id` (FK, migr. 017 — reemplaza al viejo
  `actividad_id`), `tipo_comprobante_id`, `ff_id` (fuente), comprobante (serie, numero, **ruc**, **razon_social**,
  monto, fecha_original). Sin DNI. Tiene `estado` (0=Pendiente, 1=Aprobada) y `poa_rendicion_id` (vestigial).
- **`otros_ingresos_egresos` (OIE)** — vinculado a `programa_id` (migr. 007; antes `poa_id`), `oie_comprobante_id`,
  `oie_tipo_id` (1=Ingreso, 2=Egreso), `ff_id`. El **monto vive en `oie_comprobante.monto`**.
- **`tipo_cambio`** — unificada (migr. 028; reemplaza `tipo_cambio_dolar`/`tipo_cambio_euro`, eliminadas):
  moneda, `fecha_vigencia`, compra, venta `decimal(12,6)`, origen. `rendicion` y `oie_comprobante` llevan el
  TC **congelado** por fila (`tc_usd`/`tc_eur` + `tipo_cambio_*_id` como rastro, migr. 029; NULL = pendiente).

### Catálogos
`cargo` (1 Administrador, 2 Contador, 3 Coordinador), `tipo_programa`, `tipo_rubro`, `tipo_comprobante`,
`oie_tipo`, `oie_tipo_comprobante`, `categoria_rubro`, `subcategoria_rubro`.

### Identidad
- **`persona`** (datos personales: `nro_documento` UNIQUE, apellidos, nombres, telefono).
- **`usuario`** (`persona_id` UNIQUE, `cargo_id`, `email` **UNIQUE** desde la migr. 032 —el código corto
  `descripcion` se retiró en la migr. 024—, `password` char(60) bcrypt, `reset_token`).

---

## [SECCION: ESTADO DE LA BD — MIGRACIONES]

- **Runner** `database/migrate.php` + baseline `database/schema_baseline.sql` + tabla `schema_migrations`.
  Migraciones vigentes: **001-032** (`database/migrations/`). **020** = `monto_asignado` (sobres) + `oie.programa_id` nullable; **021** = `vista_saldo_sobre`; **022-024** = códigos autogenerados (jerárquicos + correlativos) y retiro del código de usuario; **025** = `otros_ingresos_egresos_admin_vista` con LEFT JOIN a programa/fuente (ingreso híbrido, item 7); **026-031** = plan de montos y TC (2026-07-15): **026** `fuente_financiamiento.presupuesto` → `decimal(14,2)`; **027** `vista_saldo_rubro` + `vista_saldo_fuente_financiamiento` con inicial/vigente/capacidad_asignable; **028** tabla `tipo_cambio` unificada (elimina `tipo_cambio_dolar`/`euro` y sus vistas); **029** columnas de TC congelado en `rendicion` y `oie_comprobante`; **030** vistas de reporte con conversión congelada (`ROUND(monto/NULLIF(tc,0),2)` + contador de pendientes); **031** `reporte_fuentes` alineada con su modelo (alias `fuente_*`); **032** `usuario.email` UNIQUE (item 10, cierra esa parte de B3).
- **BD local `sysai`:** migraciones aplicadas hasta 032. Despliegue **greenfield** (Hostinger dado de baja):
  importar baseline + 001-032 + `seed.sql` en BD nueva; ya no hay que reconciliar contra un estado previo.
  Para poblar un escenario de demo completo (usuarios, programas, fuentes con sobres, POA, rendiciones): `database/seed_demo.sql` (re-ejecutable).
- **Tabla de migraciones 001-019, hallazgos y brechas: `docs/historial-migraciones.md`** (020-021 documentadas aquí, en *Fuentes* y *Saldos*).

---

## [SECCION: ORDEN / ESTADO DE IMPLEMENTACION]

| # | Módulo | Estado |
|---|---|---|
| 1 | Migración de BD | ✅ COMPLETADO (001-025) |
| 2 | Vínculo Coordinador-Programa | ✅ COMPLETADO (Fase 1) |
| 3 | POA Indicadores | ✅ COMPLETADO (QA 18/18) |
| 4 | POA Presupuestal | ✅ COMPLETADO (2026-07-15) — flujo QA 18/18 + **tope por Σ sobres y puerta de sobres** (QA 30/30) |
| 5 | Rendiciones (↔ rubro; tope por **sobre**) | ✅ COMPLETADO (QA 10/10) |
| 6 | POA Rendición (= mismo POA Presupuestal) | ✅ COMPLETADO (QA 21/21) |
| 7 | Otros Ingresos/Egresos (OIE, solo Contador; tope por **sobre**) | ✅ COMPLETADO (QA 22/22) |
| 8 | Saldos (fuente + **sobre** + comprometido + **cierre anual**) | ✅ COMPLETADO (2026-07-16) — cierre anual manual + histórico + visibilidad por POA (QA 16/16, suite 117/117) |
| 9 | Reportes Excel | ⏸ diferido v1.1 |
| 10 | Usuarios | ✅ COMPLETADO (2026-07-16) — revisión + fixes del CRUD, email UNIQUE (migr. 032), QA 14/14 (suite 131/131) |

> Detalle de construcción + QA de los items **completados (2-6)**: `docs/historial-implementacion-items-2-6.md`.
> **Sub-presupuestos ("sobres") — Fases 1-5 (2026-07-09):** migr. 020-021, validaciones (`validarLimiteSobre`, `validarLimiteAsignacion`), saldos por sobre + comprometido, y captura de `monto_asignado` en `/dfinanciamiento/crear`. Ver *Fuentes*, *Rubros*, *Saldos*.

---

## [SECCION: BACKLOG PENDIENTE]

> ✅ **El backlog de negocio del MVP está COMPLETO** (items 1-8 y 10; el 9 —Reportes Excel nuevos— quedó en
> v1.1 por decisión). Lo abierto vive en *[SECCION: PENDIENTES / FOLLOW-UPS TECNICOS]* (deuda técnica y
> confirmaciones de negocio) y en *[SECCION: DIFERIDO A v1.1]*.

### Item 10 — Usuarios (✅ completado 2026-07-16, QA `qa_usuarios.ps1` 14/14)
La revisión final del CRUD corrigió: `eliminar()` no limpiaba `poa_indicadores`/`poa_rendicion` (FK) y el
DELETE fallaba EN SILENCIO reportando éxito (ahora `eliminarsinRedireccion()` devuelve `bool`, se verifica, y
falla con `resultado=25`); `crear()` podía dejar personas huérfanas (ahora verifica y deshace); mass assignment
en `actualizar()` (password/persona_id/reset_token/ids protegidos, patrón A2); email con formato validado
(`FILTER_VALIDATE_EMAIL`) y **UNIQUE en BD** (migr. 032, cierra esa parte de B3). El password del alta es un
provisional aleatorio hasheado: el usuario define el suyo vía `/chgpsswd` (por diseño no hay campo de contraseña).

---

## [SECCION: CONVENCIONES]

- Comentarios y nombres en **español**; identificadores de dominio en español (`fuente_financiamiento`, `rendicion`, …).
- Datos de texto se almacenan en **MAYÚSCULAS** (forzado en `ActiveRecord::convertirAMayusculas`, excepto `password`/`reset_token`/`email`).
- Controladores con métodos **estáticos**; patrón CRUD `index/crear/actualizar/eliminar`.
- La redirección post-guardado vive en el modelo (`crear()/actualizar()` hacen `header()+exit`); usar las variantes `*sinRedireccion()` para encadenar operaciones.
- Tras una operación se redirige a `/<entidad>/admin?resultado=N` y `mostrarNotificacion(N)` traduce el código a mensaje (1=creado, 2=actualizado, 3=eliminado…).
- Banners que deben persistir (p. ej. observaciones) llevan clase `.alert-persistente` (los flash normales se auto-ocultan a los 3 s vía `src/js/app.js`; recompilar bundle con `npx gulp js` si se toca el JS).
- Esquema: `InnoDB`, `CHARSET=utf8 COLLATE=utf8_general_ci`, `datetime` para `fecha`.

---

## [SECCION: DIFERIDO A v1.1]

- **Re-apertura del POA Rendición** por el Contador (MVP = ciclo enviar→aprobar una vez).
- **Rollover de cierre anual** — traspaso de saldo entre años (`fuente_presupuesto_anual` ya existe).
- **Reportes Excel nuevos/ampliados** — se conserva lo existente; no se agregan nuevos en MVP.
- **Avances** (`avance_actividad/producto/resultado`) — seguimiento, uso futuro.
- **Indicadores a nivel de Producto/Resultado** (`indicador_producto`, `indicador_resultado`) — uso futuro.

---

## [SECCION: PENDIENTES / FOLLOW-UPS TECNICOS]

> Detalle completo (hechos + pendientes) en `docs/follow-ups-tecnicos.md`. Abiertos, en resumen:
- [x] ~~Bloqueo por intentos en `Login.php`~~ — **resuelto en `main`** (migr. 011 `login_intentos`; `models/Login.php` la usa). Cerrado 2026-07-15.
- [x] ~~Retirar modelo `RendicionFuentesCantidadVista`~~ — **HECHO 2026-07-15** (modelo y llamadas eliminados; `$ffnro` no se usaba en ninguna vista).
- [ ] B2 — reportes POA/Excel inflados por fan-out de fuentes. Ya existe la cifra correcta libre de fan-out (`comprometido` = Σ sobres por fuente; `vista_saldo_sobre` por sobre); falta que los **reportes Excel** (item 9, v1.1) la consuman en vez de repetir `fuente.presupuesto` por actividad.
- [ ] B3 — esquema desalineado. ✅ Resueltos: overflow de montos (migr. 026), `fecha_original` (ya era `date`) y `email` UNIQUE (migr. 032, item 10). **Quedan:** `avance decimal(2,2)` y auditoría sin triggers.
- [ ] B4 — MAYÚSCULAS forzadas indiscriminadas (degrada calidad de datos).
- [ ] B5 — código muerto de otro proyecto en `includes/templates/` (bienes raíces); `setImagen/borrarImagen` sin validar archivo.
- [ ] Confirmar con el contador de la organización el **mapeo compra/venta** del TC (ingreso→compra, gasto→venta, saldo→compra): está derivado por lógica NIC 21, no por norma interna (plan de montos §5.3).
- [ ] `sql_mode` sin `STRICT_TRANS_TABLES` — evaluar activarlo en el greenfield (convertiría todo truncamiento futuro en error ruidoso); requiere probar la app entera antes.

---

## [SECCION: PRIORIDADES ACORDADAS]

1. Implementar el backlog de negocio pendiente (items 7 OIE, 8 Saldos, 10 Usuarios) sobre la BD ya migrada.
2. **Sprint de seguridad** ya integrado en `main` (`docs/historial-seguridad.md`); urgencia baja (producción dada de baja).
3. Documentar/entender la lógica de negocio (reportes POA y rendiciones, conversión de moneda).
4. Refactorizar la capa de datos hacia consultas preparadas y validaciones consistentes (parcialmente hecho).
