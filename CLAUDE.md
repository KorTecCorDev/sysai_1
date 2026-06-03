# CLAUDE.md — SysAI · Organización Arco Iris

> **Memoria única del proyecto: contexto técnico y de negocio para el desarrollo del sistema.**
> Este documento es la fuente de verdad. Consolida dos líneas de trabajo previas:
> - **Línea de negocio** (rama `feat/bd-migraciones-grupos-8-13`) → reglas de negocio confirmadas, migraciones `database/migrations/001-009`, backlog de implementación.
> - **Línea de análisis técnico** (auditoría 2026-06-02) → arquitectura interna, setup/devstack, sprint de seguridad, modelo de datos detallado.
>
> Diseñado para merge con otras instancias: cada sección es independiente y está etiquetada.
>
> ✅ **Nota de integración (actualizada):** el **sprint de seguridad** descrito en *[SECCION: SEGURIDAD]*
> —originalmente en la rama `seguridad/hardening-y-despliegue-local`— ya fue **integrado mediante merge
> curado** en la rama `integ/seguridad` (commit `4c4e6eb`), sobre las migraciones de negocio. En esa
> integración: el SMTP pasó a `.env` (ya **no** está hardcodeado en `LoginController`), las 3 migraciones de
> seguridad se portaron al runner (`database/migrations/010-012`), y se descartó el sistema de migraciones
> viejo (`db/`). **Pendiente de despliegue** (no de código): aplicar migraciones a la BD reimportada y a
> Hostinger, QA visual de CSP, y mergear `integ/seguridad` a la línea principal.

---

## [SECCION: ENTORNO]

- **Proyecto:** SysAI — Sistema de gestión presupuestal y rendición de cuentas para ONG.
- **Organización:** Arco Iris (ONG sin fines de lucro, **Huaraz, Perú**).
- **Repositorio:** `KorTecCorDev/sysai_1` (privado, GitHub).
- **Autor original:** Karlos Colonia Arellano.
- **Stack:** PHP MVC (sin framework) + Active Record propio · MySQL/MariaDB · Bootstrap 5 · SCSS/Gulp · PHPSpreadsheet · PHPMailer.
- **Entorno local:** XAMPP (Windows) — `C:/xampp/htdocs/sysai_1`.
- **Producción:** **PRODUCCIÓN real** desplegada en **Hostinger** (hosting compartido, Apache + `.htaccess`).
  - BD de producción: `u612374195_sysai`. BD local: `sysai`.
- **Separación de entornos:** `.env` por entorno (ignorado en git). `.env.example` versionado como plantilla.
- **Credenciales de BD:** Solo en `.env`, nunca hardcodeadas. `includes/config/database.php` ignorado en git (lee `.env` y conecta MySQL).
- **Moneda base:** Sol peruano (PEN / S/). Conversiones a USD/EUR solo para reportes.

---

## [SECCION: STACK Y DEPENDENCIAS]

- **Backend:** PHP puro, arquitectura MVC casera. Patrón ActiveRecord propio (estilo cursos de Juan de la Torre / DevWebCamp).
- **Base de datos:** MySQL/MariaDB vía `mysqli` (conexión única global). Uso intensivo de **VISTAS SQL** (los modelos con sufijo `*Vista` mapean vistas, no tablas). Mecanismo de auditoría con `SET @usuario_actual` (triggers que registrarían quién modifica — ver *[SECCION: MODELO DE DATOS]*).
- **Frontend:** Bootstrap 5 + Bootstrap Icons; SASS compilado con **Gulp** (`gulpfile.js`). Assets compilados en `build/` (CSS/JS/img); fuente en `src/`.
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
"C:\xampp\mysql\bin\mysql.exe" -u root sysai < database/schema_baseline.sql   # baseline versionado
php database/migrate.php                                                       # aplica migrations/001-009

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
  ⚠️ **Estado actual de la rama:** las credenciales SMTP están **hardcodeadas en `LoginController`**
  (`controllers/LoginController.php`, ~líneas 140-148). **Pendiente** moverlas a `.env`/config externa
  (ver *[SECCION: FOLLOW-UPS TECNICOS]* y *[SECCION: SEGURIDAD]* A-VULN1).
- **Credenciales de prueba / seed** (cuando se usa data ficticia): históricamente `admin@sysai.test`,
  `contador@sysai.test`, `coordinador@sysai.test` (pass `Test1234*`).
  ⚠️ La **BD local actual** suele tener el admin como `robertokar97@gmail.com` y **NO** entra con `Test1234*`
  (sí entran contador y coordinador). Para QA como admin: re-sembrar o resetear el hash del admin en local.
  > **Pendiente de catálogo/seed formal** para despliegue desde cero — ver *[SECCION: FOLLOW-UPS TECNICOS]*.

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
database/          → migrate.php, migrations/, schema_baseline.sql, README.md
build/             → CSS/JS/IMG compilados (output de Gulp)
src/               → SCSS y JS fuente
```
> Los conteos de controladores/modelos/vistas difieren entre las dos memorias originales (snapshots
> distintos); úsense como orden de magnitud, no como cifra exacta.

### Front controller y enrutamiento
1. **`index.php`** arranca sesión, fija cabecera CSP, carga `includes/app.php`, registra rutas públicas
   (login, logout, recuperación) y según **`$_SESSION['cargo_id']`** incluye `iadmin.php` / `iconta.php` /
   `icoordi.php`. Finalmente llama a `$router->comprobarRutas()`.
2. **`Router.php`** (`MVC\Router`) — router minimalista: arrays `rutasGET`/`rutasPOST`, métodos `get()`/`post()`,
   `comprobarRutas()` (despacha por `REQUEST_URI` + método), `render()` y `renderssdbr()` (vistas sin sidebar, p. ej. login).
   - **Autorización por rol = carga condicional de rutas.** Las rutas de admin solo se registran si `cargo_id==1`;
     un coordinador ni siquiera las tiene registradas (caen en 404). Es el principal mecanismo de control de acceso.

### Capa de datos — `models/ActiveRecord.php` (clase base)
- `setDB()`, `guardar()` (decide crear/actualizar por `$this->id`), `crear()`/`actualizar()`/`eliminar()` y
  variantes `*sinRedireccion()` (las normales hacen `header(Location...)` + `exit` tras éxito → la redirección
  está acoplada al modelo; usar las `*sinRedireccion()` para encadenar operaciones).
- Lectura: `all()`, `find($id)`, `findxatributo()`, `findwithparameters()`, `consultarSql()`, etc.
- **`sanitizarAtributos()`** escapa en escrituras; **`convertirAMayusculas()`** fuerza TODO string a MAYÚSCULAS
  antes de insertar (decisión de negocio). Excepciones declaradas en `$columnasSinMayuscula = ['password','reset_token','email']`.
- Helpers de reportes Excel embebidos: `insertarCeldasReportePOA()`, `insertarRendicionesFuente()`,
  `insertarDatosDesdeArray()`, `combinarCeldasRepetidas()`, `insertarDatosDesdeArrayEgresosRendiciones()`.
- `setUsuarioActual()` ejecuta `SET @usuario_actual = '<descripción>'` para auditoría en BD.

### Modelos (`models/`)
- **Modelos de tabla:** `Usuario`, `Persona`, `Cargo`, `Poa`, `Programa`, `Producto`, `Actividad`, `Resultado`,
  `Rubro`, `CategoriaRubro`, `SubCategoriaRubro`, `TipoRubro`, `FuenteFinanciamiento`, `DetalleFinanciamiento`,
  `Rendicion`, `RendicionFf`, `OtrosIngresosEgresos`, `OieComprobante`, `OieTipoComprobante`, `TipoComprobante`,
  `TipoCambioDolar`, `TipoCambioEuro`, `Login`, etc.
- **Modelos de VISTA SQL** (sufijo `*Vista`): `UsuarioVista`, `RendicionAdminVista`, `IngresoEgresoAdminVista`,
  `ReporteEgresosVista`, `SaldoFuenteFinanciamientoVista`, etc. — consultas precompuestas en la BD.

### Controladores (`controllers/`)
Estáticos, reciben `Router $router`. Patrón típico: `index` (listado/admin), `crear`, `actualizar`, `eliminar`.
Renderizan con `$router->render('carpeta/vista', [datos])`.

### Vistas (`views/`)
Una subcarpeta por entidad (`actividad/`, `usuario/`, `poa/`, `rendicion/`, `reporte/`, …), cada una con
`admin.php` (listado), `crear.php`, `actualizar.php`, `formulario.php` (parcial compartido). Layouts:
`layout.php`, `layout_admin/contador/coordinador.php`, `layout_login.php`. Helper de escape: **`s()`** en
`includes/funciones.php` (= `htmlspecialchars`, `ENT_QUOTES`, UTF-8, null-safe). La única salida cruda
intencional es `echo $contenido` en los layouts (HTML ya renderizado).

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

### Fuentes de Financiamiento
- Una fuente es una organización de caridad con presupuesto anual.
- El monto es referencial hasta que se elaboren los POAs presupuestales.
- Una fuente puede estar vinculada a múltiples programas (`detalle_financiamiento`).
- Solo las fuentes vinculadas al programa pueden usarse en rendiciones.
- No hay límite de fuentes por programa.
- **Dos tipos de presupuesto por fuente:**
  - `presupuesto_comprometido`: se define al aprobar el POA Presupuestal.
  - `presupuesto_contable`: monto inicial + ingresos − rendiciones aprobadas − otros egresos.
- El saldo contable sobrante al cierre del año se traslada al siguiente periodo.

### Programa
- Cada programa tiene **uno y solo un coordinador activo**.
- Vínculo coordinador-programa mediante tabla intermedia `coordinador_programa` (usuario_id, programa_id, activo).
- Al cambiar coordinador: desactivar vínculo anterior (`activo=0`), crear nuevo vínculo (`activo=1`).
- Un coordinador solo puede tener un programa activo a la vez.
- Jerarquía: Programa → Resultado → Producto → Actividad → Rubro (POA Presupuestal)
- Jerarquía: Programa → Resultado → Producto → Actividad → Indicadores (POA Indicadores)

### POA Indicadores
- Se crea **antes** del POA Presupuestal.
- Tiene su propio registro documento con **estados**: Borrador(0) → Enviado(1) → Observado(2) → Aprobado(3). Mismo flujo que el Presupuestal.
- La jerarquía Resultado → Producto → Actividad es **compartida** entre el POA Indicadores y el POA Presupuestal. El Presupuestal solo agrega Rubros a la misma estructura.
- Indicadores solo a nivel de Actividad (las tablas `indicador_producto` e `indicador_resultado` son para uso futuro).

### POA Presupuestal
- Se elabora a partir de la estructura del POA Indicadores.
- **Estados:** Borrador(0) → Enviado(1) → Observado(2) → Aprobado(3)
- El Coordinador puede editar hasta que el Contador lo apruebe.
- Si el Contador observa, retorna al Coordinador para modificación (sin comentario).
- Luego de aprobado: solo visible para el Coordinador. Solo el Contador puede modificarlo (como adenda).
- Al aprobarse: define el `presupuesto_comprometido` del programa y de las fuentes vinculadas.
- Máximo un POA Presupuestal activo por programa por año.

### Rubros
- Un rubro es un Bien o Servicio (campo `tipo_rubro`: TRB001=Bien, TRB002=Servicio).
- Tiene un monto máximo. La suma de todas las rendiciones contra ese rubro no puede exceder ese monto.
- Pueden registrarse múltiples rendiciones por rubro.

### Rendiciones
- Las elaboran: Coordinador y Contador.
- Siempre vinculadas a un rubro (bien o servicio).
- Una rendición usa **una sola fuente** vinculada al programa.
- Documentos de sustento válidos: factura, boleta, recibo de viaje, declaración jurada, recibo de pago de servicios, recibo general.
- Datos obligatorios: **RUC** y **razón social / nombre**. El RUC identifica a cualquier proveedor (empresa o persona natural). No se usa DNI.
- El monto de rendiciones comprometidas se muestra como "presupuesto comprometido" en la fuente.
- El descuento real del saldo contable ocurre al aprobar el POA_Rendición.

### POA Rendición
- **Uno solo por programa por año.** El Contador puede re-abrirlo después de aprobado para agregar más rendiciones (no se crea un documento nuevo).
- Mismo flujo de aprobación que el POA Presupuestal: Borrador → Enviado → Observado → Aprobado.
- Lo elabora el Coordinador (y el Contador como coordinador en jefe).
- Una vez **enviado**, el Coordinador queda bloqueado: no puede agregar rendiciones hasta que el Contador lo observe (devuelva) o apruebe.
- Al aprobarse por el Contador: las rendiciones se bloquean y se descuenta el `presupuesto_contable` de cada fuente.
- Luego de la aprobación: el Contador puede re-abrirlo para agregar rendiciones adicionales.

### Otros Ingresos / Egresos (OIE)
- **SOLO los registra el Contador** — aprobación automática (descuento/suma inmediata).
- Ingresos: nuevas donaciones. Suman al `presupuesto_contable` de la fuente.
- Egresos: gastos fuera del POA. Restan al `presupuesto_contable` de la fuente.
- Requieren selección de programa y fuente vinculada al programa.
- Incluyen recibo con datos del benefactor/proveedor.

### Tipo de Cambio
- Módulo de registro de USD y EUR.
- Se usa el **tipo de cambio vigente** (último registrado) para todos los cálculos de conversión.
- No hay cálculo con tipo de cambio histórico.

### Saldos
- Se muestran stats de fuentes y programas.
- Saldo fuente = monto_inicial + ingresos − rendiciones_aprobadas − otros_egresos
- El saldo de programas solo se muestra cuando el POA Presupuestal está aprobado.
- Al registrar rendiciones aprobadas u OIE, los saldos se actualizan.
- `presupuesto_comprometido` de una fuente = suma automática de los montos de todos los POAs Presupuestales aprobados que usan esa fuente.
- El presupuesto de la fuente **no se reparte formalmente por programa** (`detalle_financiamiento` sin campo monto_asignado).

### Cierre Anual
- El saldo sobrante de cada fuente al cierre del año se registra en la tabla `fuente_presupuesto_anual` (fuente_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable).
- Permite ver el histórico de saldos año a año por fuente.

---

## [SECCION: MODELO DE DATOS]

> Charset baseline real del proyecto: **`InnoDB`, `CHARSET=utf8 COLLATE=utf8_general_ci`**, `datetime` para `fecha`.
> (El análisis antiguo describía el dump original con `utf8mb3`/`utf8mb4`; la baseline versionada hoy
> estandariza en `utf8_general_ci`.) Las vistas del dump original traían `ALGORITHM=UNDEFINED` y
> `SQL SECURITY DEFINER` con `root@localhost` → ⚠️ al importar en Hostinger ajustar/quitar definer.

### Jerarquía de planificación (POA)
```
programa (tipo_programa)
  └─ resultado            (+ resultado_detalle, indicador_resultado, avance_resultado)
       └─ producto        (+ producto_detalle, indicador_producto, avance_producto)
            └─ actividad   (+ detalle_actividad, indicador_actividad, avance_actividad)
                 └─ rubro  (categoria_rubro → subcategoria_rubro; tipo_rubro: TRB001=Bien, TRB002=Servicio)
poa (programa, anio, presupuesto, estado, usuario_id→coordinador)
detalle_financiamiento  (N:M programa ↔ fuente_financiamiento)
```
> `detalle_actividad` es la tabla base de los indicadores del POA Indicadores (campos: `indicador_medido`,
> `medio_verificacion`, `supuesto`, `responsable`). Las tablas `*_detalle`, `indicador_*` y `avance_*`
> existen pero están **vacías / sin controladores** → seguimiento de indicadores/avances **no implementado**
> (solo esquema; uso futuro — ver *[SECCION: DIFERIDO A v1.1]*).

### Tablas de movimientos contables
- **`rendicion`** — gasto rendido por actividad: `actividad_id`, `tipo_comprobante_id`, `ff_id` (fuente),
  comprobante (serie, numero, **ruc**, **razon_social**, monto, fecha_original). Sin DNI. Tras migración:
  añade `estado` y `poa_rendicion_id`.
- **`otros_ingresos_egresos` (OIE)** — ingresos/egresos sueltos: tras migración vinculado a `programa_id`
  (antes `poa_id`), `oie_comprobante_id`, `oie_tipo_id` (1=Ingreso, 2=Egreso), `ff_id`. El **monto vive en
  `oie_comprobante.monto`** (no falta un campo monto). Comprobante con `oie_tipo_comprobante_id`.
- **`tipo_cambio_dolar` / `tipo_cambio_euro`** — TC por usuario y fecha (se usa el último registro).

### Catálogos
`cargo` (1 Administrador, 2 Contador, 3 Coordinador), `tipo_programa`, `tipo_rubro`, `tipo_comprobante`,
`oie_tipo`, `oie_tipo_comprobante`, `categoria_rubro`, `subcategoria_rubro`.

### Identidad
- **`persona`** (datos personales: `nro_documento` UNIQUE, apellidos, nombres, telefono).
- **`usuario`** (`persona_id` UNIQUE, `cargo_id`, `descripcion` = código corto UNIQUE, `email`,
  `password` char(60) bcrypt, `reset_token`). ⚠️ `email` **NO es UNIQUE** (riesgo de ambigüedad en login `LIMIT 1`).

### Vistas SQL principales (alimentan los modelos `*Vista`)
- **`login_session_vista`** → modelo `Login`. Reescrita (migración 002) usando `coordinador_programa` para
  dar `programa_id` a los coordinadores. Incluye `password` y `reset_token`.
- **`otros_ingresos_egresos_admin_vista`** → recreada apuntando a `programa` (antes dependía de `oie.poa_id`).
- Listados: `usuario_admin_vista`, `rendicion_admin`, `rubro_admin_vista`.
- Reportes: `reporte_poa_rubros`, `reporte_poa_rubros_sumas`, `reporte_poa_rendicion`, `reporte_rendiciones`,
  `reporte_ingresos` (oie_tipo=1), `reporte_egresos` (oie_tipo=2), `reporte_fuentes`,
  `reporte_fuentes_programa`, `reporte_fuentes_programa_rendicion`.
- Auxiliares: `fuente_por_actividad_vista`, `fuentes_por_poa_id`, `vista_fuentes_financiamiento_por_actividad`,
  `programa_poa_vista`, `programas_sin_coordinador_vista`, `usuarios_coordinador_vista`,
  `usuario_id_disponible_programa_vista`, `tipo_rubro_vista`, `vista_dolar`, `vista_euro`,
  `total_monto_rendiciones_por_actividad`.
- **Eliminada** (migración 009): `cantidad_fuentes_rendicion` (era VISTA, no tabla) — una rendición usa una sola fuente.

### ⚠️ Discrepancias esquema ↔ código / deuda de datos
1. `usuario` **no tiene** columnas `intentos`/`estado` pero código histórico de `Login.php` las referenciaba.
   > En la rama de seguridad esto se resolvió (control por sesión + tabla `login_intentos`). **En la rama
   > actual, verificar** si ese código muerto sigue presente antes de confiar en el bloqueo por intentos.
2. **Auditoría sin implementar:** existe tabla `auditoria` y `ActiveRecord::setUsuarioActual()`, pero el dump
   no trae triggers y `auditoria.id` no era AUTO_INCREMENT → la tabla nunca se llena automáticamente.
3. **Overflow de montos (dump original):** `monto`/`presupuesto` eran `decimal(7,2)` (máx 99 999.99) en `rubro`,
   `rendicion`, `oie_comprobante`, `poa`; `fuente_financiamiento.presupuesto` `decimal(8,2)`.
   > **`poa.presupuesto` ya se amplió a `decimal(14,2)`** (migración 004). (La rama de seguridad proponía
   > `decimal(12,2)` en `db/schema.sql`; el valor vigente es el de la migración.) Pendiente revisar overflow
   > en `rubro`/`rendicion`/`oie_comprobante` si no lo cubre otra migración.
4. `avance` era `decimal(2,2)` (rango máx 0.99 — no admite 100%) en `avance_actividad`/`avance_resultado`. Tablas de uso futuro.
5. `rendicion.fecha_original` era `varchar(500)` mientras `oie_comprobante.fecha_original` es `date` (inconsistencia).
6. `email` de `usuario` no es UNIQUE.
7. `reporte_poa_rubros_sumas` usaba `SUM(DISTINCT u.monto)` y joins con fan-out cartesiano por fuentes →
   posible **bug de reporte** (inflado). Corregido en la rama de seguridad; verificar en producción.

---

## [SECCION: ESTADO DE LA BD — MIGRACIONES]

> **Estado migraciones BD: APLICADAS.** Runner `database/migrate.php`, baseline `database/schema_baseline.sql`,
> tabla de control `schema_migrations`. **Este es el enfoque vigente** — sustituye al antiguo `db/schema.sql` +
> `db/seed.sql` + `db/migracion_*.sql` (la carpeta `db/` ya no existe en esta rama).

**Migraciones `database/migrations/`:**

| # | Archivo | Qué hace |
|---|---|---|
| 001 | `crear_coordinador_programa.sql` | Tabla intermedia `coordinador_programa` (usuario_id, programa_id, activo). |
| 002 | `reescribir_login_session_vista.sql` | `login_session_vista` ahora deriva `programa_id` de `coordinador_programa`. |
| 003 | `crear_poa_indicadores.sql` | Entidad documento `poa_indicadores` con estados. |
| 004 | `ampliar_poa_presupuesto.sql` | `poa.presupuesto` → `decimal(14,2)`. |
| 005 | `crear_poa_rendicion.sql` | Entidad documento `poa_rendicion`. |
| 006 | `rendicion_estado_y_poa_rendicion.sql` | `rendicion` += `estado`, `poa_rendicion_id`. |
| 007 | `oie_desvincular_poa.sql` | `otros_ingresos_egresos`: `poa_id` → `programa_id`. |
| 008 | `crear_fuente_presupuesto_anual.sql` | Tabla `fuente_presupuesto_anual` (fuente_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable). |
| 009 | `eliminar_cantidad_fuentes_rendicion.sql` | Elimina la vista `cantidad_fuentes_rendicion`. |
| 010 | `vistas_saldos_contables.sql` *(seguridad B1)* | Crea las 4 vistas de saldos (`vista_total_ingresos/egresos`, `vista_saldo_contable`, `vista_saldo_fuente_financiamiento`). |
| 011 | `login_intentos_rate_limit.sql` *(seguridad C2)* | Tabla `login_intentos` (rate-limit de login por IP/email). |
| 012 | `recuperacion_password_segura.sql` *(seguridad A3)* | `usuario.reset_token`→varchar(64) sha256 + `reset_token_expira`; tabla `recuperacion_intentos`. |

**Hallazgos del esquema real (confirmados al volcar la BD):**
- `cantidad_fuentes_rendicion` y `login_session_vista` eran **VISTAS**, no tablas.
- `poa.estado` ya es `int(11)` → admite 0-3 sin cambio de tipo (solo lógica de app).
- El monto del OIE no falta: vive en `oie_comprobante.monto` (igual que `rendicion.monto`).
- `otros_ingresos_egresos_admin_vista` dependía de `oie.poa_id` → recreada apuntando a `programa`.

**Resumen de brechas y su resolución:**

| Tabla / Campo | Situación previa | Resolución |
|---|---|---|
| `poa.estado` | Solo 0/1 (insuficiente) | Lógica de app 0=Borrador,1=Enviado,2=Observado,3=Aprobado (campo ya int) |
| `poa.presupuesto` | decimal(7,2) | Ampliado a decimal(14,2) (migr. 004) |
| `poa_rendicion` | No existía | Creada (migr. 005) |
| `poa_indicadores` | No existía | Creada con estados (migr. 003) |
| `detalle_financiamiento` | Sin monto | Sin cambio — el presupuesto es de la fuente |
| `fuente_presupuesto_anual` | No existía | Creada (migr. 008); `fuente_financiamiento.presupuesto` pasa a monto de referencia |
| `rendicion` | Sin `estado`/`poa_rendicion_id`; solo RUC | Añadidos (migr. 006), sin DNI |
| `coordinador_programa` | No existía | Creada (migr. 001) |
| `cantidad_fuentes_rendicion` | Vista obsoleta | Eliminada (migr. 009) |
| `otros_ingresos_egresos` | Vinculado a `poa_id` | Vinculado a `programa_id` (migr. 007) |

---

## [SECCION: DECISIONES CONFIRMADAS — GRUPOS 8-13]

> Estado: **RESUELTO** — implementar según estas decisiones.

### Grupo 8 — Vínculo Coordinador-Programa ✓
- **Tabla intermedia `coordinador_programa`** (usuario_id, programa_id, activo).
- Un coordinador tiene **un solo programa activo** a la vez.
- Al reemplazar: `activo=0` al vínculo anterior, nuevo registro con `activo=1`.
- `login_session_vista` se rediseña usando `coordinador_programa`.

### Grupo 9 — POA Rendición como documento ✓
- **Un solo POA Rendición por programa por año.**
- Una vez **enviado**, el Coordinador queda bloqueado (no puede agregar rendiciones).
- Después de **aprobado**, el Contador puede **re-abrir** el mismo POA para agregar rendiciones adicionales (no se crea documento nuevo).

### Grupo 10 — Presupuesto comprometido y contable ✓
- `detalle_financiamiento` **no** tiene campo `monto_asignado`. El presupuesto es de la fuente, no se reparte por programa.
- `presupuesto_comprometido` de una fuente = **suma automática** de los montos de todos los POAs Presupuestales aprobados que usan esa fuente.

### Grupo 11 — POA Indicadores como documento ✓
- Tiene **su propio registro documento** con estados Borrador → Enviado → Observado → Aprobado.
- La jerarquía (Resultado → Producto → Actividad) es **compartida** entre el POA Indicadores y el POA Presupuestal. El Presupuestal solo agrega Rubros a la misma estructura sin duplicar registros.

### Grupo 12 — Rendición: campos del emisor ✓
- Se usa **solo RUC** como documento de identificación (tanto empresa como persona natural).
- Campos en `rendicion`: `ruc` + `razon_social`. Sin campo `dni`.
- Se elimina la referencia a DNI de cualquier formulario o validación.

### Grupo 13 — Saldos contables y cierre anual ✓
- Crear tabla **`fuente_presupuesto_anual`** (fuente_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable).
- Permite ver el histórico de saldos año a año por fuente.
- `fuente_financiamiento.presupuesto` pasa a ser solo el monto de referencia inicial de la fuente.

---

## [SECCION: ORDEN DE IMPLEMENTACION]

1. **Migración de BD** — ✅ **COMPLETADO** (`database/migrations/001`-`009`)
2. Vínculo Coordinador-Programa
3. POA Indicadores
4. POA Presupuestal
5. Rendiciones
6. POA Rendición
7. Otros Ingresos/Egresos
8. Saldos
9. Reportes Excel *(diferido v1.1)*
10. Usuarios

---

## [SECCION: BACKLOG DE IMPLEMENTACION — PENDIENTE]

> Cada ítem es código (modelo/controlador/vista/rutas). La BD ya está migrada.
> Rutas por rol: registrar cada acción nueva en `iadmin.php`, `iconta.php`, `icoordi.php` según corresponda.

### 2 — Vínculo Coordinador-Programa  *(linchpin: va primero)*
- [ ] Modelo `CoordinadorPrograma` (tabla `coordinador_programa`).
- [ ] CRUD de asignación: al asignar, desactivar (`activo=0`) el vínculo previo del coordinador y crear el nuevo (`activo=1`). Validar regla "un coordinador = un programa activo".
- [ ] Ajustar alta de usuario coordinador para crear su vínculo de programa.
- [ ] Verificar que el login ya consuma `programa_id` desde la vista reescrita (`login_session_vista`) — la vista ya fue migrada (002).
- [ ] Revisar vistas que aún deducen programa desde POA: `programas_sin_coordinador_vista`, `usuario_id_disponible_programa_vista` (basadas en `poa.usuario_id` → migrar a `coordinador_programa`).

### 3 — POA Indicadores (documento + flujo)
- [ ] Modelo `PoaIndicadores` (tabla `poa_indicadores`).
- [ ] Flujo de estados 0→1→2→3 (Borrador/Enviado/Observado/Aprobado).
- [ ] Coordinador elabora; Contador aprueba/observa.
- [ ] Reutiliza jerarquía compartida Resultado→Producto→Actividad (no duplicar).

### 4 — POA Presupuestal (estados + flujo)
- [ ] Estados completos 0-3 en `poa` (campo ya soporta el rango).
- [ ] Flujo aprobación Coordinador↔Contador (observación sin comentario retorna a Borrador editable).
- [ ] Al aprobar: calcular y persistir `presupuesto_comprometido` por fuente = Σ POAs Presupuestales aprobados que usan la fuente.
- [ ] Tras aprobado: solo visible al Coordinador; solo Contador modifica (adenda).
- [ ] Máximo un POA Presupuestal activo por programa por año.

### 5 — Rendiciones
- [ ] Vincular rendición a `poa_rendicion_id` y manejar `estado` (columnas ya creadas).
- [ ] Límite por rubro: Σ rendiciones contra el rubro ≤ monto del rubro. **OJO:** `rendicion` se vincula a `actividad_id`, no a `rubro_id` — definir cómo se imputa una rendición al rubro (¿agregar `rubro_id` a `rendicion`?). *(decisión técnica pendiente)*
- [ ] Identificación de emisor solo con RUC + razón social (ya en esquema).

### 6 — POA Rendición (documento + flujo + saldos)
- [ ] Modelo `PoaRendicion` (tabla `poa_rendicion`).
- [ ] Uno por programa por año. Flujo 0-3.
- [ ] Al **enviar**: bloquear al Coordinador (no agrega rendiciones).
- [ ] Al **aprobar**: bloquear rendiciones y descontar `presupuesto_contable` de cada fuente.
- [ ] Re-apertura por Contador → *diferido v1.1*.

### 7 — Otros Ingresos/Egresos
- [ ] Actualizar modelo `OtrosIngresosEgresos`: `poa_id` → `programa_id` (columna ya migrada). Quitar validación de `poa_id`.
- [ ] Solo Contador, aprobación automática (descuento/suma inmediata sobre `presupuesto_contable`).
- [ ] Monto desde `oie_comprobante.monto`.

### 8 — Saldos
- [ ] Calcular sobre `fuente_presupuesto_anual` (año vigente): contable = monto_inicial + ingresos − rendiciones_aprobadas − otros_egresos.
- [ ] Mostrar `presupuesto_comprometido` vs `presupuesto_contable`.
- [ ] Saldo de programa visible solo con POA Presupuestal aprobado.

---

## [SECCION: SEGURIDAD — SPRINT DE HARDENING]

> ✅ **ESTADO:** este sprint (originalmente rama `seguridad/hardening-y-despliegue-local`) ya fue
> **integrado por merge curado** en `integ/seguridad` (commit `4c4e6eb`) sobre las migraciones de negocio.
> El código de hardening de abajo está ahora en esta línea; el SMTP se externalizó a `.env` y las 3
> migraciones de seguridad viven en `database/migrations/010-012`.
> **Pendiente de DESPLIEGUE** (no de código): aplicar las migraciones a la BD local reimportada y a
> Hostinger (`u612374195_sysai`), QA visual de CSP/confirmaciones, y mergear `integ/seguridad` a la
> línea principal. Es **PRIORITARIO** porque el sistema está en **producción real**.

### Resumen (en la rama de seguridad)
- **Críticas:** VULN-1 (credenciales externalizadas + app-password Gmail revocado), C1 (hash de password
  corrompido por MAYÚSCULAS — corregido con `$columnasSinMayuscula`), C2 (rate-limit de login en BD,
  tabla `login_intentos` por IP y email), B1 (vistas de saldos `/saldos_contables/saldos`).
- **Altas:** A1+A2 (IDOR / mass-assignment acotados por programa con helpers `exigirRol`, `exigirProgramaPropio*`),
  A3 (recuperación de contraseña segura: CSPRNG `random_bytes`, token sha256 con TTL 30 min, rate-limit),
  A4 (`.htaccess` bloquea `.git/`, `db/`, ocultos), A5 (anti-enumeración de usuarios en `/chgpsswd`, respuesta neutra).
- **Medias:** M1 (CSP `script-src` sin `unsafe-inline/eval`; `onclick` → `data-confirm` en `build/js/seguridad.js`;
  `style-src` conserva `unsafe-inline`), M2 (cookies `HttpOnly`+`SameSite=Lax`+`secure` bajo HTTPS, HSTS,
  timeout de inactividad 30 min), M3 (prepared statements en escrituras de `ActiveRecord`),
  M4 (XSS residual: `s()` en ~73 echoes), M5 (CSRF también en formularios de auth → 419 sin token),
  M6 (eliminadas `debuguear()`/`debuguearHTML()`).
- **Aspectos ya correctos:** bcrypt (`password_hash`/`verify`), `session_regenerate_id(true)` tras login,
  logout destruye sesión+cookie, validación de IDs con `FILTER_VALIDATE_INT`, cabeceras de seguridad en `.htaccess`.

### Migraciones de la rama de seguridad (pendientes de portar al runner / Hostinger)
> Estos `.sql` vivían en `db/` (carpeta inexistente en la rama actual). Si se mergea el hardening, **portarlos
> al runner `database/migrations/`** y aplicarlos en Hostinger (`u612374195_sysai`):
> 1. `migracion_saldos.sql` (B1 — 4 vistas de saldos; corrige también el fan-out B2)
> 2. `migracion_rate_limit_login.sql` (C2 — tabla `login_intentos`)
> 3. `migracion_recuperacion_segura.sql` (A3 — `reset_token` sha256/64 + `reset_token_expira` + tabla `recuperacion_intentos`)

### QA / acciones manuales de ese sprint
- **QA visual de M1** (no verificable por HTTP): botones de eliminar siguen pidiendo confirmación y la consola no muestra violaciones de CSP.
- **VULN-1 (residual):** el app-password de Gmail sigue en el **historial git** (commit `594f8e5`); opcional purgar con `git filter-repo`/BFG + `push --force` (destructivo). Verificar/rotar también las creds de BD de producción.
- **Dependabot:** ~57 vulnerabilidades de dependencias (`composer`/`npm`) reportadas — frente distinto (no es código propio), pendiente.

---

## [SECCION: BUILD DE ASSETS (GULP)]

- **Pipeline** (`gulpfile.js`): SCSS `src/scss/**` → Dart Sass + autoprefixer + cssnano + sourcemaps →
  `build/css/app.css`. JS `src/js/**` → concat `bundle.js` + terser → `build/js/bundle.min.js`.
  Imágenes `src/img/**` → imagemin → `build/img/` y versión `.webp`.
- **Tareas:** `gulp css` (compila y queda en watch de `src/scss`), `gulp js`, `gulp imagenes`, `gulp webp`,
  `gulp build` (todo, sin watcher; para CI/prod), `gulp` / `npm run dev` (todo + watcher). `npm run css` → `gulp css`.
- **Fixes ya aplicados:** eliminado `node-sass` muerto (el gulpfile usa Dart Sass `require('sass')`);
  `@use "sass:color";` añadido en `_variables.scss`/`_sidebar.scss`; `npm run css` exportada.
- **Pendiente menor:** los `@import` de Sass están *deprecated* (migrar a `@use/@forward`); `build/css/`
  contiene SVGs de bootstrap-icons commiteados (revisar/limpiar).

---

## [SECCION: CONVENCIONES]

- Comentarios y nombres en **español**; identificadores de dominio en español (`fuente_financiamiento`, `rendicion`, …).
- Datos de texto se almacenan en **MAYÚSCULAS** (forzado en `ActiveRecord::convertirAMayusculas`, excepto `password`/`reset_token`/`email`).
- Controladores con métodos **estáticos**; patrón CRUD `index/crear/actualizar/eliminar`.
- La redirección post-guardado vive en el modelo (`crear()/actualizar()` hacen `header()+exit`); usar las variantes `*sinRedireccion()` para encadenar operaciones.
- Tras una operación se redirige a `/<entidad>/admin?resultado=N` y `mostrarNotificacion(N)` traduce el código a mensaje (1=creado, 2=actualizado, 3=eliminado…).
- Esquema: `InnoDB`, `CHARSET=utf8 COLLATE=utf8_general_ci`, `datetime` para `fecha`.

---

## [SECCION: DIFERIDO A v1.1]

- **Re-apertura del POA Rendición** por el Contador (MVP = ciclo enviar→aprobar una vez).
- **Rollover de cierre anual** — lógica de traspaso de saldo entre años (tabla `fuente_presupuesto_anual` ya existe).
- **Reportes Excel nuevos/ampliados** — se conserva lo existente; no se agregan nuevos en MVP.
- **Avances** (`avance_actividad`, `avance_producto`, `avance_resultado`) — seguimiento, uso futuro.
- **Indicadores a nivel de Producto/Resultado** (`indicador_producto`, `indicador_resultado`) — uso futuro.

---

## [SECCION: FOLLOW-UPS TECNICOS]

- [x] ✅ **SMTP externalizado al `.env`** (integrado en `integ/seguridad`). `LoginController` ya no tiene credenciales; el envío usa el helper `enviarTokenRecuperacion()` y `includes/config/mail.php` lee las claves `MAIL_*` del `.env`. Sin credenciales hardcodeadas en código trackeado.
- [ ] `usuario` no tiene columnas `intentos`/`estado` pero `Login.php` histórico las referencia (bloqueo por intentos) → confirmar si es código muerto o falta migración antes de confiar en el bloqueo. *(Resuelto en la rama de seguridad con `login_intentos`; verificar en la actual.)*
- [ ] Retirar/limpiar modelo `RendicionFuentesCantidadVista` (su vista `cantidad_fuentes_rendicion` fue eliminada en migración 009).
- [ ] **Seed data** para despliegue desde cero: catálogos (`cargo`, `tipo_rubro`, `tipo_comprobante`, `oie_tipo`, `oie_tipo_comprobante`, `tipo_programa`) + usuario admin inicial. (No incluido en migraciones.)
- [ ] Validar relación rendición↔rubro (ver ítem 5 del backlog de implementación).
- [ ] **B2 — Reportes POA inflados en producción** (fan-out por fuentes) — corregido en la rama de seguridad, falta portar/migrar.
- [ ] **B3 — Esquema desalineado** (overflow de montos en `rubro`/`rendicion`/`oie_comprobante`; `avance decimal(2,2)`; `rendicion.fecha_original varchar`; `email` no UNIQUE; auditoría sin triggers/AUTO_INCREMENT) — revisar qué cubren las migraciones actuales vs. lo corregido en la rama de seguridad.
- [ ] **B4 — MAYÚSCULAS forzadas** indiscriminadas (degrada calidad de datos; origen del bug C1).
- [ ] **B5 — Código muerto / de otro proyecto:** `includes/templates/formulario_propiedades.php`, `formulario_vendedores.php`, `anuncios.php` (parecen de bienes raíces); `setImagen/borrarImagen` sin validar archivo.
- [ ] **B6 — `validarPropiedadArray()`** sin `isset` (warnings).
- [ ] **B7 — Deuda de build:** `@import` Sass deprecated (migrar a `@use/@forward`); SVGs commiteados en `build/css/`.
- [ ] Confirmar contra **producción** todas las discrepancias de *[SECCION: MODELO DE DATOS]* y planificar la migración de las correcciones pendientes.

---

## [SECCION: NOTAS TECNICAS]

- `cantidad_fuentes_rendicion`: vista eliminada (migr. 009). Una rendición solo tiene una fuente.
- `login_session_vista`: rediseñada usando `coordinador_programa` (tabla intermedia con `activo`) — migr. 002.
- `poa.presupuesto`: ampliado a `decimal(14,2)` (migr. 004).
- `fuente_financiamiento.presupuesto`: pasa a ser solo monto de referencia. Los saldos anuales van en `fuente_presupuesto_anual`.
- El correo de recuperación usa credenciales SMTP hardcodeadas en `LoginController` — mover al `.env`.
- `detalle_actividad`: tabla base de los indicadores del POA Indicadores (`indicador_medido`, `medio_verificacion`, `supuesto`, `responsable`).
- `avance_actividad`, `avance_producto`, `avance_resultado`: tablas de seguimiento de avances para uso futuro.
- `rendicion.dni`: no agregar — se usa solo RUC para cualquier proveedor (empresa o persona natural).
- Las vistas del dump original traían `DEFINER=root@localhost` → ajustar al importar en Hostinger.
- `php -S` no procesa `.htaccess` → en dev no aplican los bloqueos de archivos/carpetas; cuidar secretos.

---

## [SECCION: PRIORIDADES ACORDADAS]

1. Implementar el backlog de negocio (Grupos 8-13) sobre la BD ya migrada.
2. **Mergear/portar el sprint de seguridad** a la línea actual (empezando por externalizar el SMTP) — es producción real.
3. Documentar/entender la lógica de negocio (reportes POA y rendiciones, conversión de moneda).
4. Refactorizar la capa de datos hacia consultas preparadas y validaciones consistentes (parcialmente hecho en la rama de seguridad).
