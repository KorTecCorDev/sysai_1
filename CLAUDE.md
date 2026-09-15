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
- **`docs/plan-reportes-ingresos-y-rendiciones.md`** — ✅ **IMPLEMENTADO (2026-08-14, Fases 0-4, migr. 035)**:
  corrección de `/reporte/ingresos` y `/reporte/rendiciones` (movimientos que desaparecían por un JOIN mal
  planteado, presupuesto de fuentes sumado como ingreso, rendiciones pendientes sin marcar, columnas USD/EUR
  nunca escritas) + los 2 hallazgos críticos de los cinco reportes (traversal en `/descargar` y xlsx servibles
  sin sesión → descarga por streaming). `ReporteMovimientosXlsxBuilder` + QA celda a celda. Registro de decisiones.
- **`docs/auditoria-seguridad-2026-09.md`** — ✅ **AUDITORÍA PRE-DESPLIEGUE (2026-09-14)**: seguridad (acceso por
  URL, hardening, Excel, sesión y login), alertas de Dependabot, PHP 8.3 oficial y zona horaria. Hallazgos con su
  estado, cómo repetir la verificación y la **checklist del despliegue greenfield**.
- `docs/historial-implementacion-items-2-6.md` — construcción + QA de los items **completados** (2 al 6).
- `docs/historial-migraciones.md` — tabla completa de migraciones 001-019 + estado histórico de la BD.
- `docs/modelo-datos-detalle.md` — lista completa de vistas SQL + discrepancias/deuda de esquema.
- `docs/qa-automatizado.md` — detalle de los arneses de QA HTTP. ⚠️ Ejecutar con **`pwsh`** (en
  PowerShell 5.1 el login falla) y contra **`php -S localhost:3000`** (bajo Apache los asserts de
  CSRF fallaban porque el 419 salía como 500; desde el 2026-09-14 el CSRF responde 403). Suite actual: **216/216**
  (incluye `qa_recuperacion.ps1`, que necesita **Mailpit** arriba: lee el código real de su API).
- `docs/historial-seguridad.md` — sprint de hardening (hecho/mergeado).
- **`docs/plan-secretos-y-hardening.md`** — ✅ **P0 y P1 IMPLEMENTADOS (2026-08-14)**: gestión de
  secretos y exposición. Desarrollo **sin ningún secreto** (Mailpit como SMTP local), `.env` fuera del
  document root en producción, `.htaccess` corregido (era sintaxis Apache 2.2 dentro de un `<IfModule>`),
  guardas CLI en `database/`, y el correo que ya no finge envíos exitosos. **P2 pendiente**: es la
  checklist de despliegue, consolidada en `docs/auditoria-seguridad-2026-09.md`.
- **`docs/despliegue-hostinger.md`** — procedimiento paso a paso del despliegue greenfield con SSH (estructura,
  `secrets/.env`, BD, `crear_admin.php`, `database/` temporal, verificación con `curl` y navegador, respaldos).
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
- **Producción:** **Hostinger**, con **PHP 8.3** (versión oficial del proyecto desde el 2026-09-14 — ver *Setup*).
  ⚠️ La instancia anterior fue **dada de baja el 2026-06-03** (BD `u612374195_sysai`, decomisionada). El próximo
  despliegue va **sobre Hostinger otra vez, pero greenfield**: proyecto y BD nuevos, sin datos que preservar ni
  reconciliar.
- **Separación de entornos:** `.env` por entorno (ignorado en git). `.env.example` versionado como plantilla.
- **Credenciales de BD:** Solo en `.env`, nunca hardcodeadas. `includes/config/database.php` está **versionado**
  (no contiene secretos: lee `.env` y conecta MySQL) → el repo es portable de equipo en equipo copiando solo `.env`.
- **Moneda base:** Sol peruano (PEN / S/). Conversiones a USD/EUR solo para reportes.

---

## [SECCION: STACK Y DEPENDENCIAS]

- **Vistas SQL:** los modelos con sufijo `*Vista` mapean **vistas**, no tablas. `SET @usuario_actual` es
  infraestructura de auditoría que hoy nadie consume (ver *Diferido a v1.1*).
- **Excel seguro:** todo libro nace con `nuevoLibroXlsx()`, que instala `Model\XlsxValorSeguroBinder`: un texto que
  empieza por "=" nunca se vuelve fórmula; las fórmulas legítimas van con `setCellValueExplicit`.
- Pipeline de assets (Gulp, `src/` → `build/`): `docs/build-assets.md`.

---

## [SECCION: SETUP / DEVSTACK]

**Entorno real de la PC de desarrollo (verificado):**
- **PHP 8.3 — versión oficial del proyecto (decisión 2026-09-14, reemplaza la de 8.2.12 del 2026-07-15).**
  Motivo: producción es greenfield y en Hostinger la versión se elige por sitio; PHP 8.2 deja de recibir
  parches de seguridad el **31 dic 2026** y la 8.3 los recibe hasta el **31 dic 2027**.
  - **Consola = `C:\php\php.exe` (8.3.28, NTS)**, la ÚNICA entrada de PHP en el `PATH` de sistema. La usan
    `php`, **Composer** (`composer.bat` ejecuta el `php` del PATH), el `php -S` que levanta `npm run dev`,
    los arneses QA y los scripts de `database/`. `php.ini` en `C:\php\php.ini` (con `openssl.cafile` y
    `curl.cainfo` → `C:\xampp\apache\bin\curl-ca-bundle.crt`, requisito del SMTP). La suite QA completa
    (193/193) está verificada sobre esta versión.
  - **Apache de XAMPP = 8.2.12** (`C:\xampp\php`, NO está en el PATH): XAMPP no ofrece una versión más nueva.
    Solo afecta al vhost :8080 (capacitación en la LAN, pruebas del `.htaccess`). **Diferencia conocida y
    aceptada**: por eso `composer.json` NO exige `php ^8.3` en `require` —el `platform_check` bloquearía a
    Apache—; en su lugar fija `config.platform.php = 8.3.0`, para que Composer resuelva dependencias como si
    corriera en producción sin importar el PHP local. `platform_check.php` exige ≥ 8.2.0.
  - **Producción (Hostinger): elegir PHP 8.3 en hPanel** al crear el sitio.
  - `intl` inactiva en ambos (nadie la usa). Extensiones del proyecto (`mysqli`, `zip`, `gd`, `openssl`,
    `curl`, `mbstring`, `xml`…) activas en ambos.
  - **Zona horaria fijada en código** (`America/Lima` en `includes/config/database.php` + `SET time_zone
    '-05:00'` en `conectarDB()`): cada PHP traía la suya (UTC / Europe/Berlin) y la app no la fijaba.
  - ⚠️ `C:\Program Files\MySQL\MySQL Server 8.0\bin` también está en el PATH (sin servicio): `mysql` a secas es
    el cliente de MySQL 8.0, no el de MariaDB. Los scripts usan la ruta completa de XAMPP.
- **MariaDB de XAMPP** en `127.0.0.1:3306` (binario `C:\xampp\mysql\bin\mysql.exe`, root sin contraseña).
- **Composer 2.9**, **Node 24 / npm 11**.
- **Servidor de desarrollo — `npm run dev` es el iniciador único (2026-08-13).** Un solo comando desde la raíz
  del proyecto compila CSS/JS (las tareas de imágenes se retiraron el 2026-09-14), levanta el servidor PHP, pone **BrowserSync** por delante y queda
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
    Si solo se quiere el backend: `php -S localhost:3000` desde la raíz. ⚠️ Varios documentos lo llaman
    `local3000`, pero **ese alias no existe** en el perfil de PowerShell (verificado 2026-09-14): es solo el
    nombre corto del comando.
  - ✅ El servidor embebido sirve los assets de `build/` directos; las rutas inexistentes caen a `index.php` (front controller) que lee `REQUEST_URI`. No requiere vhost ni Apache.
  - ⚠️ **`php -S` NO procesa `.htaccess`** → en dev NO aplican los bloqueos de `controllers/`, `models/`, `*.sql`, `.env`, etc. Los `.htaccess` solo protegen en producción (Apache/Hostinger).
  - Para trabajar contra el **vhost de Apache** (multiproceso y con `.htaccess` activo, lo más parecido a
    producción): `$env:PHP_PORT=8080; npx gulp` — gulp detecta que el puerto ya está servido y proxea a
    Apache en vez de levantar su propio PHP.
  - Decisiones del pipeline de dev (por qué se retira la CSP en el proxy, `ghostMode: false`, CSS inyectado
    vs. PHP recargado): `docs/build-assets.md`.
  - ✅ **Requisito TLS (Windows) para SMTP — CUMPLIDO (2026-07-16).** En `C:\php\php.ini` (el PHP oficial de
    consola; verificado 2026-09-14) y en `C:\xampp\php\php.ini`,
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
"C:\xampp\mysql\bin\mysql.exe" -u root sysai < database/seed.sql              # catálogos (sin usuarios)
php database/crear_admin.php --email <correo> --nombres "..." --apellido-paterno "..." --dni <n> --probar-correo
                                      # admin con clave aleatoria oculta + código de activación por correo

# Arrancar (desde la raíz del proyecto):
npm run dev                           # = gulp: compila + php -S + BrowserSync + watchers
                                      #   → http://localhost:3001  la app (con recarga automática)
                                      #   → http://localhost:3002  panel de BrowserSync
                                      #   Ctrl+C cierra también el php.exe que levantó.

# Alternativas:
npx gulp                              # idéntico a npm run dev (NO existe `gulp` global)
php -S localhost:3000                 # solo backend, sin recarga (el "alias local3000" de otros docs no existe)
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
  `MAIL_USERNAME` (si no, reescribe el remitente).
  ✅ **Emisor de producción — decidido 2026-09-14:** una **cuenta Gmail NUEVA y DEDICADA a Arca** (no
  `korteccor@gmail.com` ni ninguna cuenta personal), con App Password propia del servidor, 587/tls (465/ssl
  si Hostinger bloquea el 587). Motivo: la App Password abre la cuenta entera; en una cuenta dedicada un
  `.env` filtrado no expone a nadie, y la activación no depende de una persona. Cambiar la contraseña de
  esa cuenta revoca sus App Passwords (el correo deja de salir). **Cuando haya dominio** (no hay a la
  vista): `no-reply@<dominio>` en `smtp.hostinger.com:465/ssl` con SPF/DKIM/DMARC — solo cambia el `.env`.
  Plantilla en `.env.example`; razones en `docs/plan-secretos-y-hardening.md` (enmienda 2026-09-14).
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
  vía el flujo de cambio; un `UPDATE` los pisaría. `seed.sql` ya **no trae usuarios** (2026-09-14); los
  fixtures `seed_demo.sql`/`seed_qa.sql` crean un admin id=1 de desarrollo (`admin@sysai.test`, sin clave
  utilizable) solo si falta, con `INSERT IGNORE`. ⚠️ Ambos fixtures **borran todo usuario con id ≠ 1**: nunca
  sobre una BD con datos reales (en greenfield el admin de `crear_admin.php` nace con id 5 por el
  `AUTO_INCREMENT` del baseline; ningún código depende del id 1).

---

## [SECCION: ARQUITECTURA]

### Enrutamiento
- **Autorización por rol = carga condicional de rutas.** `index.php` incluye `iadmin.php` / `iconta.php` /
  `icoordi.php` según `$_SESSION['cargo_id']`: un coordinador ni siquiera tiene registradas las rutas de admin
  (caen en 404). Es el principal mecanismo de control de acceso.
- **CSRF por sufijo:** `Router::requiereCsrf()` exige token en todo POST a `/crear|/actualizar|/eliminar|/enviar|
  /observar|/aprobar|/guardar` (más login, recuperación y `/logout`) → **403** sin token. Una ruta nueva que muta
  estado debe usar uno de esos sufijos. Los formularios emiten `csrf_input()`; `/logout` solo acepta POST.

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
- Helpers de reportes Excel que quedan en `ActiveRecord`: `insertarCeldasReportePOA()` (solo `/reporte/poa`) y
  `combinarCeldasRepetidas()`. Los demás reportes usan los builders `ReporteRendicionXlsxBuilder` y
  `ReporteMovimientosXlsxBuilder`; todo libro nace con `nuevoLibroXlsx()` y se entrega con `descargarXlsx()`.
- `setUsuarioActual()` ejecuta `SET @usuario_actual = '<descripción>'` para auditoría en BD.

### Vistas
- Toda salida se escapa con **`s()`** (`includes/funciones.php`). Única salida cruda intencional: `echo $contenido`
  en los layouts.

---

## [SECCION: ROLES]

| cargo_id | Nombre | Capacidades |
|---|---|---|
| 1 | Administrador | Root. Acceso total. |
| 2 | Contador | Coordinador en jefe + aprobador de POAs y rendiciones. Registra OIE (aprobación automática). |
| 3 | Coordinador | Gestiona su programa asignado. Elabora POAs y rendiciones. |

---

## [SECCION: MODULOS FUNCIONALES]

### Autenticación (detalle en la skill `auth-recuperacion`)
- **Onboarding = invitación por correo:** el alta genera una contraseña aleatoria que nadie conoce; cada usuario
  activa la suya en `/chgpsswd → /token_verify → /updtepsswd`. Por diseño, el alta no tiene campo de contraseña.
- ⚠️ **No reintroducir `?id=` en `/updtepsswd`**: la identidad vive en la sesión (`pwd_reset_uid`); por URL
  permitía tomar cualquier cuenta (IDOR).
- La sesión se revalida contra la BD en cada petición (`Login::sesionSigueValida()`): cambiar contraseña, cargo o
  programa la cierra.

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

### Programa Institucional (detalle en la skill `programa-institucional`)
- Programa reservado `PRG000` para gastos de oficina. ⚠️ Identificarlo **siempre por el flag
  `programa.es_institucional`** (`Programa::institucional()`), nunca por nombre o id. No se puede eliminar y no
  recibe sobres directos: su sobre se deriva de las transferencias y solo lo escribe
  `TransferenciaInstitucional::sincronizarSobreInstitucional()`. Lo opera el Contador.

### Reportes Excel de rendición (detalle en la skill `reportes-excel`)
- `ReporteRendicionXlsxBuilder` (grano rubro×fuente, migr. 034) cuenta **solo rendiciones Aprobadas del ejercicio
  vigente**. Columnas calculadas, nunca letras hardcodeadas; `combinarCeldasRepetidas` solo en las columnas de
  etiquetas A/B (en columnas de montos hace desaparecer importes).

### Tipo de Cambio (detalle en la skill `tipo-de-cambio`)
- TC vigente a una fecha = `fecha_vigencia` máxima ≤ esa fecha (`TipoCambio::vigente()`), **nunca por id u orden
  de registro**.
- Se **congela** al registrar la transacción (rendición y OIE egreso → venta; OIE ingreso → compra) y **no se
  recalcula hacia atrás**. Mapeo confirmado por la contadora (NIC 21): no cambiarlo sin nueva consulta. La tasa
  SBS es solo informativa.

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

### Cierre Anual (detalle en la skill `cierre-anual`)
- Snapshot manual en `fuente_presupuesto_anual` (`POST /cierre_anual/guardar`), re-cerrable por upsert. Copia el
  desglose en vivo: **nunca acumuladores por operación**. El rollover al año siguiente queda para v1.1.

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

---

## [SECCION: ESTADO DE LA BD — MIGRACIONES]

- **Runner** `database/migrate.php` sobre `database/schema_baseline.sql` (tabla `schema_migrations`). Qué hace cada
  migración está en su archivo de `database/migrations/` y en `docs/historial-migraciones.md`. Una migración
  aplicada no se edita: se corrige con otra.
- **BD local `sysai`:** ⚠️ **aplicadas hasta 034** (la base de capacitación restaurada es anterior a la 035 →
  `php database/migrate.php`; sin ella `qa_reportes.ps1` da 7 FAIL).
- **Despliegue greenfield:** baseline + migraciones + `seed.sql` + `crear_admin.php`. `seed_demo.sql` es un escenario
  de demo solo para desarrollo.

---

## [SECCION: ESTADO DE IMPLEMENTACION]

> ✅ **Backlog funcional completo: items 1-10.** Lo abierto está en *Pendientes* y *Diferido a v1.1*. El detalle de
> construcción de cada ítem vive en `docs/` (p. ej. `docs/historial-implementacion-items-2-6.md`) y en git.

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

> **Abiertos — revisados el 2026-09-14.** Detalle y checklist completa del despliegue en
> `docs/auditoria-seguridad-2026-09.md`; deuda técnica histórica en `docs/follow-ups-tecnicos.md`.

**Antes de desplegar**
- [ ] **Rotar credenciales** (solo el usuario): App Password de Gmail y credenciales de la BD vieja siguen en
  el historial de git (`594f8e5` y siguientes); las de Mailtrap pasaron por un chat.
- [x] ~~**Merge `dev → main`**~~ — **HECHO 2026-09-15** (fast-forward hasta `b5324c0`, con confirmación del
  usuario). Cierra las alertas de Dependabot, que solo analiza `main`. `main` es la rama que se despliega.
- [ ] **Verificar contra un Apache real** lo que `php -S` no ejecuta: redirección a HTTPS, bloqueos y CSP del
  `.htaccess`; y el botón "Cerrar sesión" (formulario POST vía `app.js`) en el navegador.
- [ ] **Crear la cuenta Gmail dedicada a Arca** (emisor decidido el 2026-09-14, ver *Setup*) con verificación
  en dos pasos y una App Password propia del servidor.
- [ ] **Checklist del despliegue greenfield**: PHP 8.3 en hPanel, SSL, usuario MySQL de mínimo privilegio,
  MySQL remoto apagado, `.env` en `../secrets/`, subida por lista blanca, **`crear_admin.php --probar-correo`
  desde el servidor** (prueba a la vez la salida al 587), `REMOTE_ADDR` real (sin CDN delante),
  `session.save_path` propio y `curl` a los archivos sensibles → 403/404.

**Entorno local**
- [ ] Aplicar la **migr. 035** a la BD local (`php database/migrate.php`).
- [ ] **`npm run env:pull`**: el `.env` es más viejo que `secrets/.env.enc` y la app responde 500 (pide la passphrase).
- [ ] MariaDB de XAMPP escucha en todas las interfaces con `root` sin contraseña: bloquear el 3306 en el firewall
  antes de otra sesión en la LAN.
- [ ] (Opcional) `memory_limit` de `C:\php\php.ini` es 128M (XAMPP: 512M): un reporte muy grande podría fallar en dev.

**Negocio y QA**
- [ ] **Capacitación**: sin evidencia en el repo de que se haya realizado (no hay `participantes.csv` ni respaldo
  de cierre). Confirmar con el usuario.
- [ ] Checklist visual `docs/qa-frontend-navegador.md`, sin marcar.
- [ ] **Presupuesto por periodo** (`docs/plan-montos-y-tipo-cambio.md` §5.1): consultar a la contadora; pasa a ser
  prerequisito si algún convenio no coincide con el año calendario.

**Riesgos aceptados o a vigilar**
- Alerta de `immutable` 3.x en BrowserSync **descartada** (forzar la 4 lo rompe): revisar cuando BrowserSync pida `^4`.
- Bajos de la auditoría sin tratar: enumeración por tiempo en el login, `CURLOPT_FOLLOWLOCATION` en la consulta
  SBS, `style-src 'unsafe-inline'` en la CSP.

**Cerrados:** el histórico (B2-B8, `sql_mode` estricto, bloqueo por intentos) está en `docs/follow-ups-tecnicos.md`;
la confirmación del mapeo del TC, en `docs/confirmar-tc-contador.md`.

---

## [SECCION: PRIORIDADES ACORDADAS]

> Revisadas el 2026-09-14: el backlog funcional (items 1-10), la auditoría de seguridad, Dependabot y la
> decisión de PHP 8.3 ya están hechos.

1. **Cerrar la lista "Antes de desplegar"** (sección anterior) y desplegar greenfield en Hostinger.
2. **Capacitación y validación con usuarios reales**, incluida la checklist visual del navegador.
3. **v1.1** (ver *Diferido*): presupuesto por periodo, rollover del cierre anual, reapertura del POA y auditoría con triggers.
4. Seguir llevando la capa de datos hacia consultas preparadas y validaciones consistentes (casi todo hecho).
