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
- **`docs/plan-reportes-ingresos-y-rendiciones.md`** — 📋 **PLANIFICADO (2026-08-14)**: corrección de
  `/reporte/ingresos` y `/reporte/rendiciones`. Auditoría con hallazgos verificados (movimientos que
  desaparecen por un JOIN mal planteado, presupuesto de fuentes sumado como ingreso, rendiciones
  pendientes sin marcar, columnas USD/EUR nunca escritas) + **2 hallazgos de seguridad críticos** que
  alcanzan a los cinco reportes (traversal en `/descargar`, xlsx servibles sin sesión). 12 decisiones
  tomadas; migr. 035 + builder + QA. **Nada ejecutado todavía.**
- `docs/historial-implementacion-items-2-6.md` — construcción + QA de los items **completados** (2 al 6).
- `docs/historial-migraciones.md` — tabla completa de migraciones 001-019 + estado histórico de la BD.
- `docs/modelo-datos-detalle.md` — lista completa de vistas SQL + discrepancias/deuda de esquema.
- `docs/qa-automatizado.md` — detalle de los arneses de QA HTTP. ⚠️ Ejecutar con **`pwsh`** (en
  PowerShell 5.1 el login falla) y contra **`php -S localhost:3000`** (bajo Apache los asserts de
  CSRF fallaban porque el 419 salía como 500; desde el 2026-09-14 el CSRF responde 403). Suite actual: **193/193**.
- `docs/historial-seguridad.md` — sprint de hardening (hecho/mergeado).
- **`docs/plan-secretos-y-hardening.md`** — ✅ **P0 y P1 IMPLEMENTADOS (2026-08-14)**: gestión de
  secretos y exposición. Desarrollo **sin ningún secreto** (Mailpit como SMTP local), `.env` fuera del
  document root en producción, `.htaccess` corregido (era sintaxis Apache 2.2 dentro de un `<IfModule>`),
  guardas CLI en `database/`, y el correo que ya no finge envíos exitosos. **P2 pendiente**: dominio
  propio, SPF/DKIM y verificación del `.htaccess` contra el Apache real.
- `docs/build-assets.md` — pipeline Gulp.
- `docs/follow-ups-tecnicos.md` — deuda técnica pendiente (detalle).

---

## [SECCION: ENTORNO]

- **Proyecto:** SysAI — Sistema de gestión presupuestal y rendición de cuentas para ONG.
- **Nombre visible = "Arca"** (rebrand 2026-07-17, barrido aplicado el 2026-08-12): así se llama el sistema en
  `<title>`, login, correos y ante el usuario. El **identificador técnico `sysai` NO cambia** (repo, BD,
  namespace, rutas, carpeta). Marca: `docs/marca/` (original) → `build/img/arca_isotipo.png` (login),
  `arca_favicon.png` (pestaña), `arca_logo.png` (lockup con la palabra, por si hace falta).
  El logo de la **ONG** (`build/img/logo_last.png`, la casita) sigue en el sidebar y en el Excel de reportes:
  ahí la marca que corresponde es la de Arco Iris, no la del software.
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
- **Credenciales de BD:** Solo en `.env`, nunca hardcodeadas. `includes/config/database.php` está **versionado**
  (no contiene secretos: lee `.env` y conecta MySQL) → el repo es portable de equipo en equipo copiando solo `.env`.
- **Moneda base:** Sol peruano (PEN / S/). Conversiones a USD/EUR solo para reportes.

---

## [SECCION: STACK Y DEPENDENCIAS]

- **Backend:** PHP puro, arquitectura MVC casera. Patrón ActiveRecord propio (estilo cursos de Juan de la Torre / DevWebCamp).
- **Base de datos:** MySQL/MariaDB vía `mysqli` (conexión única global). Uso intensivo de **VISTAS SQL** (los modelos con sufijo `*Vista` mapean vistas, no tablas). Mecanismo de auditoría con `SET @usuario_actual` (triggers que registrarían quién modifica — ver *[SECCION: MODELO DE DATOS]*).
- **Frontend:** Bootstrap 5 + Bootstrap Icons; SASS compilado con **Gulp** (`gulpfile.js`). Assets compilados en `build/` (CSS/JS/img); fuente en `src/`. Pipeline detallado en `docs/build-assets.md`.
- **Dependencias Composer (`composer.json`):**
  - `phpoffice/phpspreadsheet` ^5.9 — generación de reportes Excel (4.1 → 5.9 el 2026-09-14: la 4.1 tenía 9
    avisos de seguridad). Todo libro nace con `nuevoLibroXlsx()`, que instala `Model\XlsxValorSeguroBinder`:
    un texto que empieza por "=" nunca se vuelve fórmula; las fórmulas legítimas van con `setCellValueExplicit`.
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
- **Servidor de desarrollo — `npm run dev` es el iniciador único (2026-08-13).** Un solo comando desde la raíz
  del proyecto compila CSS/JS/imágenes, levanta el servidor PHP, pone **BrowserSync** por delante y queda
  vigilando cambios:
  - ⚠️ **No hay `gulp-cli` global**: `gulp` pelado responde `command not found`. Usar **`npm run dev`** o
    **`npx gulp`** (ambos resuelven el binario de `node_modules/.bin/`). Si `npx gulp` falla con
    `Cannot find module 'browser-sync'`, el `node_modules` está desactualizado → `npm install`.
  - **http://localhost:3001** → la app, con recarga automática ← **usar esta**
  - **http://localhost:3002** → panel de BrowserSync
  - **http://localhost:8025** → bandeja de correo de desarrollo (Mailpit)
  - 🔒 Todo escucha **solo en `127.0.0.1`** (2026-08-14). BrowserSync lo hacía en todas las interfaces y
    su proxy no filtra rutas: `curl http://<ip-lan>:3001/.env` devolvía las credenciales de la BD.
  - `http://localhost:3000` → el `php -S` crudo que gulp levanta por debajo (sigue sirviendo; sin recarga).
    El alias `local3000` (= `php -S localhost:3000`) sigue siendo válido si solo se quiere el backend.
  - ✅ El servidor embebido sirve los assets de `build/` directos; las rutas inexistentes caen a `index.php` (front controller) que lee `REQUEST_URI`. No requiere vhost ni Apache.
  - ⚠️ **`php -S` NO procesa `.htaccess`** → en dev NO aplican los bloqueos de `controllers/`, `models/`, `*.sql`, `.env`, etc. Los `.htaccess` solo protegen en producción (Apache/Hostinger).
  - Para trabajar contra el **vhost de Apache** (multiproceso y con `.htaccess` activo, lo más parecido a
    producción): `$env:PHP_PORT=8080; npx gulp` — gulp detecta que el puerto ya está servido y proxea a
    Apache en vez de levantar su propio PHP.
  - Decisiones del pipeline de dev (por qué se retira la CSP en el proxy, `ghostMode: false`, CSS inyectado
    vs. PHP recargado): `docs/build-assets.md`.
  - ✅ **Requisito TLS (Windows) para SMTP — CUMPLIDO (2026-07-16).** En `C:\xampp\php\php.ini`,
    `openssl.cafile` y `curl.cainfo` apuntan a `C:\xampp\apache\bin\curl-ca-bundle.crt`. Verificado:
    HTTPS por streams y handshake TLS verificado contra `smtp.gmail.com:465` OK. El día que se configure
    SMTP real (`MAIL_USERNAME`/`MAIL_PASSWORD` en `.env`), PHPMailer ya puede verificar el certificado.
    (Si se reinstala/actualiza XAMPP, revalidar estas dos claves del `php.ini`.)

**Pasos de arranque:**
```bash
composer install                      # vendor/  (PhpSpreadsheet, PHPMailer, bootstrap-icons)
npm install                           # node_modules/ (Gulp + BrowserSync)

# Base de datos (enfoque ACTUAL — runner de migraciones):
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE sysai CHARACTER SET utf8 COLLATE utf8_general_ci;"
"C:\xampp\mysql\bin\mysql.exe" -u root sysai < database/schema_baseline.sql   # baseline versionado (sin datos)
php database/migrate.php                                                       # aplica migrations/001-035
"C:\xampp\mysql\bin\mysql.exe" -u root sysai < database/seed.sql              # catálogos + admin inicial

# Arrancar (desde la raíz del proyecto):
npm run dev                           # = gulp: compila + php -S + BrowserSync + watchers
                                      #   → http://localhost:3001  la app (con recarga automática)
                                      #   → http://localhost:3002  panel de BrowserSync
                                      #   Ctrl+C cierra también el php.exe que levantó.

# Alternativas:
npx gulp                              # idéntico a npm run dev (NO existe `gulp` global)
local3000                             # solo backend, sin recarga: php -S localhost:3000
npx gulp build                        # solo compilar assets, sin servidor ni watcher (CI/prod)
```

> **Para vaciar/recrear la BD local antes de reimportar** (XAMPP): phpMyAdmin trae `DROP DATABASE`
> deshabilitado (`$cfg['AllowUserDropDatabase']=false`). Usar la consola MySQL —no tiene esa restricción—:
> `DROP DATABASE IF EXISTS sysai; CREATE DATABASE sysai CHARACTER SET utf8 COLLATE utf8_general_ci;`

**Configuración por entorno (secretos solo en `.env`, NO versionado):**
- `includes/config/database.php` — conexión (lee `.env`; **versionado**, sin secretos). Plantilla: `.env.example`.
  `conectarDB()` hace `mysqli_report(MYSQLI_REPORT_OFF)` porque el código comprueba valores de retorno (no usa try/catch),
  y fija **`SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'`**
  (2026-07-16): todo truncamiento/overflow es error ruidoso, idéntico en dev y Hostinger (por sesión, sin tocar
  `my.cnf`). Cubre también `database/migrate.php` (reutiliza `conectarDB()`). Verificado: replay greenfield
  completo (baseline + migr. 001-032 + seeds) y suite QA 131/131 bajo modo estricto.
- **Correo en desarrollo = Mailpit, sin credenciales (2026-08-14).** `npm run dev` levanta un SMTP local
  en `127.0.0.1:1025` que **acepta todo y no reenvía nada a Internet**; los correos se leen en
  **http://localhost:8025**. Instalación por equipo: `winget install Axllent.Mailpit`. Gracias a esto el
  `.env` de desarrollo **no contiene ni un secreto** (BD local sin contraseña + correo local), que es
  justamente el objetivo: nada que proteger, rotar ni trasladar entre equipos.
  `MAIL_TRANSPORT` = `smtp` | `log` | `auto`. Detalle y razones: `docs/plan-secretos-y-hardening.md`.
  ⚠️ **En producción el `.env` va FUERA del document root** (`../secrets/.env`, permisos 600, o la ruta
  en `$SYSAI_ENV_FILE`): la app lo busca ahí primero.
- **SMTP / recuperación de contraseña:** el flujo `/chgpsswd → /token_verify → /updtepsswd` usa PHPMailer.
  ✅ Credenciales externalizadas: `includes/config/mail.php` lee las claves `MAIL_*` del `.env`; el envío lo
  arma `construirMailer()` y lo usa `enviarTokenRecuperacion()` (ambos en `includes/funciones.php`).
  ⚠️ **Histórico:** el 2026-08-13 se verificó SMTP real con Gmail `korteccor@gmail.com` + App Password
  (587/TLS). **Esa configuración ya no existe:** el `.env` se perdió al reinstalarse el equipo y Google no
  permite recuperar una App Password. **No se ha vuelto a configurar a propósito** — desarrollar contra
  Mailpit no necesita credenciales, y una App Password abre la cuenta de Gmail entera (decisión
  2026-08-14, `docs/plan-secretos-y-hardening.md`). Si hiciera falta probar entrega real: generarla,
  usarla y **revocarla** en el momento. Gmail exige que `MAIL_FROM_EMAIL` sea **la misma** de
  `MAIL_USERNAME` (si no, reescribe el remitente). ⏸ Destino final: `no-reply@<dominio>` en Hostinger
  (`smtp.hostinger.com`, 465/ssl) **con SPF y DKIM**, cuando haya dominio contratado.
  - **Diagnóstico:** `php database/smtp_test.php <destinatario>` — comprueba por separado credenciales,
    openssl/CA de Windows, handshake y envío real. No toca la BD ni genera tokens. `MAIL_DEBUG=1` vuelca el
    diálogo SMTP al `error_log`.
  - **Bitácora:** todo envío deja línea en `includes/logs/mail.log` (ruta configurable con
    `MAIL_LOG_PATH`, para sacarla del document root en producción). ⚠️ **El token NUNCA se escribe**
    (2026-08-14): es una credencial temporal y ese archivo llegó a ser descargable por HTTP. Para verlo,
    la bandeja de Mailpit; en su defecto, `usuario.reset_token`. `LoginController` ya no ignora el fallo
    de envío (queda en el log), pero la respuesta al usuario sigue siendo **neutra** (A5, anti-enumeración).
  - ⚠️ **Con `APP_ENV=production` y sin transporte, el envío FALLA (devuelve `false` + `[ERROR]`)** en vez
    de fingir éxito: sin correo nadie puede activar su cuenta, y antes el sistema lo ocultaba.
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
     (POST sin token → **403**; hasta el 2026-09-14 era 419, que no es estándar y bajo Apache salía como 500).
     Los formularios emiten `csrf_input()`. `/logout` también exige token y **solo acepta POST** (formulario
     oculto en los layouts que envía `app.js`).

### Capa de datos — `models/ActiveRecord.php` (clase base)
- `setDB()`, `guardar()` (decide crear/actualizar por `$this->id`), `crear()`/`actualizar()`/`eliminar()` y
  variantes `*sinRedireccion()` (las normales hacen `header(Location...)` + `exit` tras éxito → la redirección
  está acoplada al modelo; usar las `*sinRedireccion()` para encadenar operaciones).
- Lectura: `all()`, `find($id)`, `findxatributo()`, `findwithparameters()`, `consultarSql()`, etc.
  ⚠️ `consultarPreparado()`/`crearObjeto()` descartan columnas fuera de `$columnasDB` (p. ej. alias de agregación);
  para leer un escalar calculado, consultar con mysqli directo.
- ✅ **B4 cerrado (2026-07-16):** se retiró `convertirAMayusculas()` — los datos se guardan **tal como se
  ingresan** (antes TODO string se forzaba a MAYÚSCULAS y degradaba nombres/razones sociales). Los códigos
  autogenerados (`siguienteCodigo*()`) siguen saliendo en mayúsculas por sus prefijos constantes (PRG, FF, REN…).
  ⚠️ `null` se normaliza a `''` en todo `UPDATE` (intencional): "limpiar" un campo = cadena vacía, no `NULL`.
- Helpers de reportes Excel embebidos: `insertarCeldasReportePOA()`, `insertarRendicionesFuente()`,
  `insertarDatosDesdeArray()`, `combinarCeldasRepetidas()`, `insertarDatosDesdeArrayEgresosRendiciones()`.
- `setUsuarioActual()` ejecuta `SET @usuario_actual = '<descripción>'` para auditoría en BD.

### Modelos, controladores y vistas
- **Modelos de tabla:** `Usuario`, `Persona`, `Cargo`, `Poa`, `Programa`, `Producto`, `Actividad`, `Resultado`,
  `Rubro`, `CategoriaRubro`, `SubCategoriaRubro`, `TipoRubro`, `FuenteFinanciamiento`, `DetalleFinanciamiento`,
  `Rendicion`, `OtrosIngresosEgresos`, `OieComprobante`, `TipoComprobante`, `CoordinadorPrograma`,
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
| **Login / Auth** | `/login`, `/logout`, `/chgpsswd`, `/token_verify`, `/updtepsswd` | Autenticación, bloqueo por intentos, recuperación de contraseña por email con `reset_token`. **Reingeniería 2026-07-17 (verificada y mergeada el 2026-08-12)** — ver abajo. |
| **Usuarios** | `/usuario/*` | CRUD de usuarios + `persona` asociada. Coordinador (cargo 3) se vincula a programa. |
| **Programas** | `/programa/*` | Programas de la ONG. |
| **POA** | `/poa/*`, `/reporte/guardarpoa`, `/reporte/modificarpoa` | Plan Operativo Anual; vincula coordinador↔programa. |
| **Resultados / Productos / Actividades** | `/resultado/*`, `/producto/*`, `/actividad/*` | Jerarquía: Resultado → Producto → Actividad. |
| **Rubros / Categorías** | `/rubro/*`, `/categoria_rubro/*` | Partidas presupuestarias (tipo rubro Bien/Servicio). |
| **Fuentes de financiamiento** | `/fuente_financiamiento/*`, `/dfinanciamiento/*` | Fuentes (donantes) y sus sobres por programa; en `/dfinanciamiento/crear` se captura también la **transferencia al programa Institucional** (migr. 033). |
| **Rendiciones** | `/rendicion/*` | Rendición de cuentas con comprobantes (RUC, serie, número, monto) por fuente. (`/rendicionff/*` se retiró el 2026-09-14: código muerto sin guardas, su tabla `rendicion_ff` no existe.) |
| **Otros Ingresos/Egresos (OIE)** | `/ingreso_egreso/*` | Movimientos no ligados a rendición, con comprobantes. |
| **Tipos de cambio** | `/tcambio/*?moneda=USD\|EUR` | TC unificado (migr. 028): compra/venta por `fecha_vigencia`; el vigente a una fecha se resuelve por fecha, no por orden de registro. `/tcambio/sbs` = consulta informativa que pre-llena el formulario. |
| **Reportes** | `/reporte/poa`, `/reporte/rendiciones`, `/reporte/ingresos`, `/descargar`, … | Exportación Excel con PhpSpreadsheet, con conversión de moneda. |
| **Saldos contables** | `/saldos_contables/saldos` | Saldos por fuente de financiamiento. |

### Autenticación — modelo y rediseño (✅ 2026-07-17, verificado y mergeado 2026-08-12)
- **Onboarding = invitación por correo** (decisión 2026-07-17): el Admin da de alta al usuario con una
  contraseña provisional **aleatoria y oculta** (`Usuario::crear()` → `generarCodigoAleatorioSimple`); el
  usuario **nunca** entra con una clave que alguien más conoce: activa la suya en `/chgpsswd` → código al
  correo → `/token_verify` → `/updtepsswd`. **Descartado** el modelo "contraseña temporal conocida +
  bandera `must_change`". Por eso el alta de usuarios no tiene campo de contraseña.
- **La identidad del cambio de contraseña vive en la SESIÓN, no en la URL.** `token_verify` fija
  `$_SESSION['pwd_reset_uid']` + `pwd_reset_ts` con `session_regenerate_id(true)`, y `updatePassword` solo
  confía en eso (prueba de un solo uso, TTL `Login::RECUP_TOKEN_TTL`); sin ella redirige a `/chgpsswd`.
  ⚠️ **No reintroducir `?id=` en `/updtepsswd`**: así era antes y permitía tomar cualquier cuenta (IDOR).
- Política: mínimo 8 caracteres + confirmación. Bloqueo por intentos (`login_intentos`, migr. 011) intacto.
- Las 4 vistas (`login`, `chgpsswd`, `token_verify`, `updtepsswd`) usan los tokens `--sa-*` del tema y
  comparten el partial de avisos `views/partials/_auth_alertas.php`.
- **QA**: el flujo NO está en la suite `qa_*.ps1` (los arneses parten de una sesión ya autenticada). Se
  verificó a mano el 2026-08-12 con un usuario desechable creado y borrado en la BD local — 10 pruebas
  (IDOR, código inválido/válido, confirmación, longitud, one-shot, login posterior, token limpiado,
  bloqueo por intentos). Repetirlo así si se toca el módulo.

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

### Programa Institucional y transferencias (✅ IMPLEMENTADO 2026-07-16, item 9 — migr. 033)
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

### Reportes Excel de rendición (✅ REESCRITOS 2026-07-16, item 9 — migr. 034)
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
- QA: `qa_reportes.ps1` asserta el contenido **celda a celda** del xlsx generado (helper `qa_leer_xlsx.php`).

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
  ✅ **Mapeo CONFIRMADO por la contadora de Arco Iris el 2026-08-12** (*"usar la tasa vigente, seguimos con
  NIC 21"*): ya no es una derivación por lógica, es la convención de la organización. No cambiar sin una
  nueva consulta — el TC congelado no se recalcula hacia atrás (`docs/confirmar-tc-contador.md`).
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
transferencia_institucional  (migr. 033: monto que cada programa destina al Institucional por fuente;
                              UNIQUE (fuente, origen); su Σ se materializa como sobre del Institucional)
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
  Migraciones vigentes: **001-034** (`database/migrations/`). **020** = `monto_asignado` (sobres) + `oie.programa_id` nullable; **021** = `vista_saldo_sobre`; **022-024** = códigos autogenerados (jerárquicos + correlativos) y retiro del código de usuario; **025** = `otros_ingresos_egresos_admin_vista` con LEFT JOIN a programa/fuente (ingreso híbrido, item 7); **026-031** = plan de montos y TC (2026-07-15): **026** `fuente_financiamiento.presupuesto` → `decimal(14,2)`; **027** `vista_saldo_rubro` + `vista_saldo_fuente_financiamiento` con inicial/vigente/capacidad_asignable; **028** tabla `tipo_cambio` unificada (elimina `tipo_cambio_dolar`/`euro` y sus vistas); **029** columnas de TC congelado en `rendicion` y `oie_comprobante`; **030** vistas de reporte con conversión congelada (`ROUND(monto/NULLIF(tc,0),2)` + contador de pendientes); **031** `reporte_fuentes` alineada con su modelo (alias `fuente_*`); **032** `usuario.email` UNIQUE (item 10, cierra esa parte de B3); **033** Programa Institucional: flag `es_institucional`, tabla `transferencia_institucional` y siembra de `PRG000` (item 9); **034** `reporte_poa_rendicion` por rubro×fuente, solo aprobadas del ejercicio vigente (item 9); **035** vistas `reporte_ingresos`/`reporte_egresos`/`reporte_rendiciones` saneadas (se retira el JOIN a `detalle_financiamiento` que hacía DESAPARECER los movimientos de fuentes sin sobres y los duplicaba con ≥2 sobres; + programa, rubro, estado y tipo de comprobante — Fase 1 de `docs/plan-reportes-ingresos-y-rendiciones.md`).
- **BD local `sysai`:** migraciones aplicadas hasta 035. Despliegue **greenfield** (Hostinger dado de baja):
  importar baseline + 001-035 + `seed.sql` en BD nueva; ya no hay que reconciliar contra un estado previo.
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
| 9 | Reportes Excel + **Programa Institucional** | ✅ COMPLETADO (2026-07-16) — builder por rubro×fuente (migr. 034), transferencias al Institucional operado por el Contador (migr. 033), fila de transferencia en los Excel. QA 15/15 institucional + 8 asserts de celdas (suite **154/154**) |
| 10 | Usuarios | ✅ COMPLETADO (2026-07-16) — revisión + fixes del CRUD, email UNIQUE (migr. 032), QA 14/14 (suite 131/131) |

> Detalle de construcción + QA de los items **completados (2-6)**: `docs/historial-implementacion-items-2-6.md`.
> **Sub-presupuestos ("sobres") — Fases 1-5 (2026-07-09):** migr. 020-021, validaciones (`validarLimiteSobre`, `validarLimiteAsignacion`), saldos por sobre + comprometido, y captura de `monto_asignado` en `/dfinanciamiento/crear`. Ver *Fuentes*, *Rubros*, *Saldos*.

---

## [SECCION: BACKLOG PENDIENTE]

> ✅ **El backlog está COMPLETO: items 1-10** (el 9 —Reportes Excel + Programa Institucional— se completó el
> 2026-07-16 como primer entregable de v1.1). Lo abierto vive en *[SECCION: PENDIENTES / FOLLOW-UPS TECNICOS]*
> (confirmación del TC con el contador) y en *[SECCION: DIFERIDO A v1.1]*.

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
- Los datos se guardan **y se muestran tal como se ingresan** (B4: el 2026-07-16 se retiró el forzado a
  MAYÚSCULAS de `ActiveRecord`; el 2026-08-12 se retiró su gemelo en CSS —`input:not([type="password"])`
  y `textarea` con `text-transform: uppercase` en `src/scss/layout/_sidebar.scss`—, que hacía que la
  pantalla mintiera sobre lo guardado). Los datos históricos y catálogos sembrados quedan en mayúsculas;
  la collation `utf8_general_ci` hace las comparaciones case-insensitive.
- Controladores con métodos **estáticos**; patrón CRUD `index/crear/actualizar/eliminar`.
- La redirección post-guardado vive en el modelo (`crear()/actualizar()` hacen `header()+exit`); usar las variantes `*sinRedireccion()` para encadenar operaciones.
- Tras una operación se redirige a `/<entidad>/admin?resultado=N` y `mostrarNotificacion(N)` traduce el código a mensaje (1=creado, 2=actualizado, 3=eliminado…).
- Banners que deben persistir (p. ej. observaciones) llevan clase `.alert-persistente` (los flash normales se auto-ocultan a los 3 s vía `src/js/app.js`; recompilar bundle con `npx gulp js` si se toca el JS).
- Esquema: `InnoDB`, `CHARSET=utf8 COLLATE=utf8_general_ci`, `datetime` para `fecha`.

---

## [SECCION: DIFERIDO A v1.1]

- **Re-apertura del POA Rendición** por el Contador (MVP = ciclo enviar→aprobar una vez).
- **Rollover de cierre anual** — traspaso de saldo entre años (`fuente_presupuesto_anual` ya existe).
- ~~Reportes Excel nuevos/ampliados~~ — ✅ el item 9 se completó el 2026-07-16 (builder por rubro×fuente +
  Programa Institucional); nuevos formatos adicionales se evaluarán bajo demanda.
- **Auditoría con triggers** (decisión 2026-07-16) — la infraestructura existe (tabla `auditoria`,
  `setUsuarioActual()` en ~20 controladores) pero nadie consume `@usuario_actual`. Al implementarla:
  redimensionar `auditoria.usuario` (`varchar(8)` → no cabe el email) y revisar `auditoria.id` AUTO_INCREMENT.
- **Avances** (`avance_actividad/producto/resultado`) — seguimiento, uso futuro.
- **Indicadores a nivel de Producto/Resultado** (`indicador_producto`, `indicador_resultado`) — uso futuro.

---

## [SECCION: PENDIENTES / FOLLOW-UPS TECNICOS]

> Detalle completo (hechos + pendientes) en `docs/follow-ups-tecnicos.md`. Abiertos, en resumen:
- [x] ~~Bloqueo por intentos en `Login.php`~~ — **resuelto en `main`** (migr. 011 `login_intentos`; `models/Login.php` la usa). Cerrado 2026-07-15.
- [x] ~~Retirar modelo `RendicionFuentesCantidadVista`~~ — **HECHO 2026-07-15** (modelo y llamadas eliminados; `$ffnro` no se usaba en ninguna vista).
- [x] ~~B2 — reportes POA/Excel inflados por fan-out de fuentes~~ — **CERRADO 2026-07-16 (item 9):** al
  verificar, el `SUM(DISTINCT)` histórico ya no existía y `/reporte/poa` no imprime fuentes; el residuo real
  (sumas de rendición desalineadas por agrupar a nivel actividad) quedó resuelto por el re-anclaje a
  rubro×fuente (migr. 034) y el builder nuevo. Los reportes ya no repiten `fuente.presupuesto` por actividad.
- [x] ~~B3 — esquema desalineado~~ — **CERRADO 2026-07-16** (barrido contra BD viva): overflow de montos,
  `fecha_original`, `email` UNIQUE y `avance` (ya era `decimal(5,2)`/`(7,2)`, admite 100% — la nota
  "decimal(2,2)" era de un dump viejo) todos verificados OK. La **auditoría sin triggers** se difiere a
  **v1.1 por decisión** (hallazgos anotados en `docs/modelo-datos-detalle.md` §2: `auditoria.usuario
  varchar(8)` vs identidad email → redimensionar al implementarla).
- [x] ~~B4 — MAYÚSCULAS forzadas indiscriminadas~~ — **CERRADO 2026-08-12:** la mitad de servidor cayó el
  2026-07-16 (`convertirAMayusculas()`); hoy cayó la mitad de CSS, que había sobrevivido y seguía mostrando
  en MAYÚSCULAS —con la fuente vieja `Lato`— todo input y textarea de la app
  (`src/scss/layout/_sidebar.scss`). Detalle en `docs/follow-ups-tecnicos.md`.
- [x] ~~B5 — código muerto de otro proyecto~~ — **HECHO 2026-07-16:** eliminados `includes/templates/` (6
  archivos de bienes raíces), `incluirTemplate()`/`TEMPLATES_URL`/`FUNCIONES_URL`/`CARPETA_IMAGENES`,
  `setImagen()`/`borrarImagen()` (sin caller; accedían a una propiedad inexistente en cada delete) y la
  dependencia `intervention/image` (cero referencias). De paso B6: `validarPropiedadArray()` SÍ tenía un
  caller (`UsuarioController.php:48`) → corregida con `!empty()` (sin warnings), no eliminada. QA 131/131.
- [x] ~~Confirmar con el contador el **mapeo compra/venta** del TC~~ — ✅ **CONFIRMADO POR LA CONTADORA
  (2026-08-12):** *"se debe usar la tasa vigente, seguimos con la lógica NIC 21"*. El mapeo implementado
  queda **sin cambios** (gasto/egreso→venta, ingreso→compra, saldos→compra, planificación→venta) con la
  tasa vigente a la **fecha de operación**, que es lo que ya congela el sistema. **Cero código que tocar.**
  Sin pronunciamiento sobre fuente de la tasa y redondeo → se mantiene el comportamiento actual (Contador
  registra a mano, SBS informativa, `ROUND(…, 2)`). Detalle en `docs/confirmar-tc-contador.md`.
- [x] ~~B8 — modal "Guardar POA" en `/reporte/poa`~~ — **CERRADO 2026-08-13** (hallado en las pruebas
  manuales): fósil pre-flujo que escribía `poa.presupuesto` desde un monto posteado por el navegador —y
  que además solo sumaba la **columna D del xlsx (rubros tipo BIEN)**, dejando fuera los servicios—.
  Hoy esa cifra la calcula el servidor (`Poa::presupuestoCalculado()` al iniciar y al congelar en
  `/poa/enviar`). Retirados modal (duplicado en el DOM), form oculto, bloque JS (que rompía el clic de
  descarga en las 5 pantallas de reporte), 5 rutas, entrada CSRF y los métodos del controlador; de paso
  se eliminó la ruta GET `/reporte/guardarpoa` → `crearpoa`, **método inexistente** = error fatal latente.
  Detalle en `docs/follow-ups-tecnicos.md`.
- [x] ~~`sql_mode` sin `STRICT_TRANS_TABLES`~~ — **HECHO 2026-07-16:** activo por sesión en `conectarDB()`
  (portable a Hostinger). Replay greenfield + seeds + suite QA 131/131 verificados bajo modo estricto.

---

## [SECCION: PRIORIDADES ACORDADAS]

1. Implementar el backlog de negocio pendiente (items 7 OIE, 8 Saldos, 10 Usuarios) sobre la BD ya migrada.
2. **Sprint de seguridad** ya integrado en `main` (`docs/historial-seguridad.md`); urgencia baja (producción dada de baja).
3. Documentar/entender la lógica de negocio (reportes POA y rendiciones, conversión de moneda).
4. Refactorizar la capa de datos hacia consultas preparadas y validaciones consistentes (parcialmente hecho).
