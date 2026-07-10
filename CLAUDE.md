# CLAUDE.md — SysAI · Organización Arco Iris

> **Memoria caliente del proyecto: contexto técnico y de negocio para el desarrollo del sistema.**
> Este documento es la fuente de verdad *operativa*. El detalle histórico y de referencia vive en `docs/`
> (ver índice abajo) y se lee solo cuando hace falta, para no cargar tokens innecesarios cada sesión.
>
> ✅ El **sprint de seguridad** ya está integrado y mergeado a `main` (SMTP en `.env`, migraciones 010-012
> en el runner). La **producción de Hostinger fue dada de baja** → el próximo despliegue es **greenfield**
> (proyecto + BD nuevos desde cero). Detalle en `docs/historial-seguridad.md`.

### Índice de referencia (`docs/`, leer bajo demanda)
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
- **Entorno local:** XAMPP (Windows) — `C:/xampp/htdocs/sysai_1`. BD local: `sysai`.
- **Producción:** ⚠️ **DADA DE BAJA (2026-06-03).** Estuvo en Hostinger (BD `u612374195_sysai`), decomisionada.
  El próximo despliegue es **greenfield**: proyecto y BD nuevos, sin datos que preservar ni reconciliar.
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
- **PHP CLI 8.3.x en `C:\php`** (standalone, **NO** el de XAMPP). Es el que usan `composer`, `php` y el servidor de desarrollo. `php.ini` en `C:\php\php.ini`. Extensión `zip` activada (la requiere PhpSpreadsheet para Excel).
- **MariaDB de XAMPP** en `127.0.0.1:3306` (binario `C:\xampp\mysql\bin\mysql.exe`, root sin contraseña).
- **Composer 2.9**, **Node 24 / npm 11**.
- **Servidor de desarrollo:** alias `local3000` = `php -S localhost:3000` ejecutado **desde la raíz del proyecto**. App en **http://localhost:3000**.
  - ✅ El servidor embebido sirve los assets de `build/` directos; las rutas inexistentes caen a `index.php` (front controller) que lee `REQUEST_URI`. No requiere vhost ni Apache.
  - ⚠️ **`php -S` NO procesa `.htaccess`** → en dev NO aplican los bloqueos de `controllers/`, `models/`, `*.sql`, `.env`, etc. Los `.htaccess` solo protegen en producción (Apache/Hostinger).
  - ⚠️ **Requisito TLS (Windows)** para SMTP: `C:\php\php.ini` con `openssl.cafile` y `curl.cainfo` apuntando a `C:\xampp\apache\bin\curl-ca-bundle.crt`. Reiniciar el servidor tras cambiar `php.ini`.

**Pasos de arranque:**
```bash
composer install                      # vendor/  (PhpSpreadsheet, PHPMailer, intervention/image…)
npm install                           # node_modules/ (Gulp)
npm run dev                           # = gulp; recompila build/ (opcional: build/ ya viene compilado)

# Base de datos (enfoque ACTUAL — runner de migraciones):
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE sysai CHARACTER SET utf8 COLLATE utf8_general_ci;"
"C:\xampp\mysql\bin\mysql.exe" -u root sysai < database/schema_baseline.sql   # baseline versionado (sin datos)
php database/migrate.php                                                       # aplica migrations/001-021
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
| **Tipos de cambio** | `/tcambio/dolar/*`, `/tcambio/euro/*` | TC para convertir S/ → USD/EUR en reportes. |
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

### Rubros
- Un rubro es un Bien o Servicio (`tipo_rubro`: TRB001=Bien, TRB002=Servicio).
- Tiene un monto de **planificación**. ⚠️ **Enmienda 2026-07-09 (migr. 020, decisión "solo el sobre"):** el rubro **ya NO limita el gasto** — el tope de una rendición es el **saldo del sobre** `(programa, fuente)`, no `rubro.monto`. El rubro queda como clasificación/imputación. Pueden registrarse múltiples rendiciones por rubro.

### Rendiciones
- Las elaboran Coordinador y Contador. Siempre vinculadas a un **rubro** (la actividad se deriva por rubro→actividad).
- Una rendición usa **una sola fuente** vinculada al programa.
- Documentos de sustento (`tipo_comprobante`): factura, **boleta de venta**, **boleta de viaje**, **recibo de caja**, **recibo de servicio básico**, recibo de viaje, declaración jurada, recibo de pago de servicios, recibo general. (La "boleta" genérica se desdobló en venta/viaje — decisión 2026-06-03.)
- Datos obligatorios: **RUC** + **razón social / nombre** (identifica a cualquier proveedor, empresa o persona natural). **No se usa DNI.**
- El monto no puede exceder el **saldo disponible del sobre** `(programa, fuente)` (`Rendicion::validarLimiteSobre()` → `DetalleFinanciamiento::saldoSobre()`). El "disponible para comprometer" cuenta rendiciones de **todo estado** (evita sobre-comprometer); el saldo **contable** solo las aprobadas.
- Nace **Pendiente (estado=0)** y NO afecta el saldo contable hasta que se aprueba.

### POA Rendición (= el mismo POA Presupuestal)
- ⚠️ **Reencuadre 2026-06-05:** el "POA Rendición" **NO es un documento aparte**: es el **mismo POA Presupuestal** (`poa`). Lo que el usuario llama "POA Rendición" es el **reporte Excel** (`/reporte/poarendicion`). La tabla `poa_rendicion` (migr. 005) y `rendicion.poa_rendicion_id` (migr. 006) quedan **vestigiales**; solo se usa `rendicion.estado`.
- Una vez el POA Presupuestal está **Enviado(1)/Aprobado(3)**, el Coordinador queda bloqueado (no agrega/edita/elimina rendiciones; `resultado=18`). Contador/Admin pasan (adenda).
- **Al aprobar el POA Presupuestal**, todas las rendiciones del programa pasan a **Aprobada(1)**, se congelan y descuentan el `presupuesto_contable` de cada fuente.
- Adenda del Contador sobre POA ya Aprobado: la rendición nace Aprobada (descuenta al instante).
- Re-apertura por el Contador → *diferido v1.1*.

### Otros Ingresos / Egresos (OIE)
- **SOLO los registra el Contador** — aprobación automática (descuento/suma inmediata sobre `presupuesto_contable`).
- Ingresos (nuevas donaciones) suman; Egresos (gastos fuera del POA) restan. Monto en `oie_comprobante.monto`. Incluyen recibo con datos del benefactor/proveedor.
- **Ingreso híbrido (enmienda 2026-07-09):** puede ir al **total de la fuente** (remanente sin asignar; `otros_ingresos_egresos.programa_id` **NULL**, migr. 020) o a un **programa concreto** (su sobre). El **egreso** descuenta del sobre `(programa, fuente)`.
- ⚠️ El **tope por sobre en OIE** y el CRUD final de OIE son parte del **item 7 (pendiente)**; la plomería ya está (`programa_id` nullable + `DetalleFinanciamiento::saldoSobre()` reutilizable).

### Tipo de Cambio
- Registro de USD y EUR. Se usa el **tipo de cambio vigente** (último registrado) para toda conversión. No hay cálculo con TC histórico.

### Saldos
- Saldo **fuente** = presupuesto + ingresos − rendiciones_aprobadas − otros_egresos (`vista_saldo_fuente_financiamiento`).
- Saldo **sobre** `(programa, fuente)` = monto_asignado + ingresos_al_sobre − egresos − rendiciones_aprobadas (`vista_saldo_sobre`, migr. 021).
- Pantalla `/saldos_contables/saldos` (Admin/Contador): 4 niveles → KPIs globales · gráfico SVG por fuente · tarjetas por fuente (con comprometido = Σ sobres, y remanente sin asignar) · tabla por sobre.
- Al registrar rendiciones aprobadas u OIE, los saldos se actualizan (las vistas calculan en vivo).

### Cierre Anual
- El saldo sobrante de cada fuente al cierre del año se registra en `fuente_presupuesto_anual` (fuente_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable). Permite el histórico año a año.

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
- **`tipo_cambio_dolar` / `tipo_cambio_euro`** — TC por usuario y fecha (se usa el último registro).

### Catálogos
`cargo` (1 Administrador, 2 Contador, 3 Coordinador), `tipo_programa`, `tipo_rubro`, `tipo_comprobante`,
`oie_tipo`, `oie_tipo_comprobante`, `categoria_rubro`, `subcategoria_rubro`.

### Identidad
- **`persona`** (datos personales: `nro_documento` UNIQUE, apellidos, nombres, telefono).
- **`usuario`** (`persona_id` UNIQUE, `cargo_id`, `descripcion` = código corto UNIQUE, `email`,
  `password` char(60) bcrypt, `reset_token`). ⚠️ `email` **NO es UNIQUE** (riesgo en login `LIMIT 1`).

---

## [SECCION: ESTADO DE LA BD — MIGRACIONES]

- **Runner** `database/migrate.php` + baseline `database/schema_baseline.sql` + tabla `schema_migrations`.
  Migraciones vigentes: **001-021** (`database/migrations/`). **020** = `monto_asignado` (sobres) + `oie.programa_id` nullable; **021** = `vista_saldo_sobre`.
- **BD local `sysai`:** migraciones aplicadas hasta 021. Despliegue **greenfield** (Hostinger dado de baja):
  importar baseline + 001-021 + `seed.sql` en BD nueva; ya no hay que reconciliar contra un estado previo.
  Para poblar un escenario de demo completo (usuarios, programas, fuentes con sobres, POA, rendiciones): `database/seed_demo.sql` (re-ejecutable).
- **Tabla de migraciones 001-019, hallazgos y brechas: `docs/historial-migraciones.md`** (020-021 documentadas aquí, en *Fuentes* y *Saldos*).

---

## [SECCION: ORDEN / ESTADO DE IMPLEMENTACION]

| # | Módulo | Estado |
|---|---|---|
| 1 | Migración de BD | ✅ COMPLETADO (001-021) |
| 2 | Vínculo Coordinador-Programa | ✅ COMPLETADO (Fase 1) |
| 3 | POA Indicadores | ✅ COMPLETADO (QA 18/18) |
| 4 | POA Presupuestal | ✅ COMPLETADO (QA 18/18) |
| 5 | Rendiciones (↔ rubro; tope por **sobre**) | ✅ COMPLETADO (QA 10/10) |
| 6 | POA Rendición (= mismo POA Presupuestal) | ✅ COMPLETADO (QA 21/21) |
| 7 | Otros Ingresos/Egresos (OIE) | ⬜ PENDIENTE (plomería de sobres lista) |
| 8 | Saldos (fuente + **sobre** + comprometido) | 🟡 EN CURSO — pantalla y vistas hechas; falta cierre anual/`fuente_presupuesto_anual` |
| 9 | Reportes Excel | ⏸ diferido v1.1 |
| 10 | Usuarios | ⬜ PENDIENTE |

> Detalle de construcción + QA de los items **completados (2-6)**: `docs/historial-implementacion-items-2-6.md`.
> **Sub-presupuestos ("sobres") — Fases 1-5 (2026-07-09):** migr. 020-021, validaciones (`validarLimiteSobre`, `validarLimiteAsignacion`), saldos por sobre + comprometido, y captura de `monto_asignado` en `/dfinanciamiento/crear`. Ver *Fuentes*, *Rubros*, *Saldos*.

---

## [SECCION: BACKLOG PENDIENTE]

> Cada ítem es código (modelo/controlador/vista/rutas). La BD ya está migrada.
> Rutas por rol: registrar cada acción nueva en `iadmin.php`, `iconta.php`, `icoordi.php` según corresponda.

### 7 — Otros Ingresos/Egresos
- [ ] Actualizar modelo `OtrosIngresosEgresos`: `poa_id` → `programa_id` (columna ya migrada). Quitar validación de `poa_id`.
- [ ] Solo Contador, aprobación automática (descuento/suma inmediata sobre `presupuesto_contable`).
- [ ] Monto desde `oie_comprobante.monto`.

### 8 — Saldos
- [ ] Calcular sobre `fuente_presupuesto_anual` (año vigente): contable = monto_inicial + ingresos − rendiciones_aprobadas − otros_egresos.
- [ ] Mostrar `presupuesto_comprometido` vs `presupuesto_contable`.
- [ ] Saldo de programa visible solo con POA Presupuestal aprobado.

### 10 — Usuarios
- [ ] Revisión/ajustes finales del CRUD de usuarios (el vínculo coordinador-programa ya está en item 2).

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
- [ ] Confirmar si el código de bloqueo por intentos (`Login.php` referencia `intentos`/`estado` inexistentes) es muerto o falta migración (la rama de seguridad lo resolvió con `login_intentos`; verificar en la actual).
- [ ] Retirar modelo `RendicionFuentesCantidadVista` (vista `cantidad_fuentes_rendicion` eliminada en migr. 009).
- [ ] B2 — reportes POA/Excel inflados por fan-out de fuentes. Ya existe la cifra correcta libre de fan-out (`comprometido` = Σ sobres por fuente; `vista_saldo_sobre` por sobre); falta que los **reportes Excel** (item 9, v1.1) la consuman en vez de repetir `fuente.presupuesto` por actividad.
- [ ] B3 — esquema desalineado (overflow de montos, `avance decimal(2,2)`, `fecha_original varchar`, `email` no UNIQUE, auditoría sin triggers).
- [ ] B4 — MAYÚSCULAS forzadas indiscriminadas (degrada calidad de datos).
- [ ] B5 — código muerto de otro proyecto en `includes/templates/` (bienes raíces); `setImagen/borrarImagen` sin validar archivo.

---

## [SECCION: PRIORIDADES ACORDADAS]

1. Implementar el backlog de negocio pendiente (items 7 OIE, 8 Saldos, 10 Usuarios) sobre la BD ya migrada.
2. **Sprint de seguridad** ya integrado en `main` (`docs/historial-seguridad.md`); urgencia baja (producción dada de baja).
3. Documentar/entender la lógica de negocio (reportes POA y rendiciones, conversión de moneda).
4. Refactorizar la capa de datos hacia consultas preparadas y validaciones consistentes (parcialmente hecho).
