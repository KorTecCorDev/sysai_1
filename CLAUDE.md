# CLAUDE.md — SysAI (Sistema de reportes contables ONG Arco Iris)

> Memoria de proyecto para futuras sesiones de Claude Code.
> Última actualización del análisis: 2026-06-01.

## 1. Qué es

Aplicación web **PHP** para la gestión contable y de rendición de cuentas de la **ONG Arco Iris**
(Huaraz, Perú). Permite planificar presupuestos (POA — Plan Operativo Anual), registrar ingresos/egresos
y rendiciones de cuentas por fuente de financiamiento, y exportar reportes en Excel.

- **Repositorio:** `KorTecCorDev/sysai_1` (privado).
- **Entorno:** **PRODUCCIÓN real** desplegada en **Hostinger** (hosting compartido). Desarrollo local en XAMPP.
- **Autor original:** Karlos Colonia Arellano.

## 2. Stack tecnológico

- **Backend:** PHP puro (sin framework), arquitectura MVC casera. Patrón ActiveRecord propio
  (estilo cursos de Juan de la Torre / DevWebCamp).
- **Base de datos:** MySQL vía `mysqli` (procedural-ish, conexión única global). Usa muchas **VISTAS SQL**
  (los modelos `*Vista` mapean vistas, no tablas) y al menos un mecanismo de auditoría con
  `SET @usuario_actual` (presumiblemente triggers que registran quién modifica).
- **Frontend:** Bootstrap 5 + Bootstrap Icons, SASS compilado con **Gulp** (`gulpfile.js`).
  Los assets compilados viven en `build/` (CSS/JS/img); el fuente en `src/`.
- **Dependencias Composer** (`composer.json`):
  - `phpoffice/phpspreadsheet` ^4.1 — generación de reportes Excel.
  - `phpmailer/phpmailer` ^6.9 — envío de correos (recuperación de contraseña).
  - `intervention/image` 2.7 — manejo de imágenes.
  - `twbs/bootstrap-icons` ^1.11.
- **PHP namespaces (PSR-4):** `Model\` → `models/`, `Controllers\` → `controllers/`, `MVC\` → raíz.

## 3. Cómo ejecutar (setup) — devstack del desarrollador

**Entorno real de la PC de desarrollo (verificado):**
- **PHP CLI 8.3.28 en `C:\php`** (standalone, NO el de XAMPP). Es el que usan `composer`, `php` y el
  servidor de desarrollo. `php.ini` en `C:\php\php.ini`. Extensión `zip` se activó (línea ~972,
  `extension=zip`) — la requiere PhpSpreadsheet para los reportes Excel.
- **MariaDB de XAMPP** en `127.0.0.1:3306` (binario `C:\xampp\mysql\bin\mysql.exe`, root sin contraseña).
- **Composer 2.9**, **Node 24 / npm 11**.
- **Servidor de desarrollo:** alias `local3000` = `php -S localhost:3000` (en `~/.bashrc`), ejecutado
  **desde la raíz del proyecto**. App en **http://localhost:3000**.
  - ✅ Verificado: el servidor embebido sirve los assets de `build/` directos y las rutas inexistentes
    (`/login`, `/poa/admin`) caen a `index.php` (front controller) que lee `REQUEST_URI`. No requiere
    vhost ni Apache.
  - ⚠️ **`php -S` NO procesa `.htaccess`** → en dev NO aplican los bloqueos de `controllers/`, `models/`,
    `*.sql`, `.env`, etc. Por eso los secretos van en archivos **`.php`** (se ejecutan, no se sirven como
    texto), nunca en `.env` plano. Los `.htaccess` solo protegen en producción (Apache/Hostinger).

**Pasos de arranque (ya ejecutados en esta máquina):**
```bash
composer install                      # vendor/  (PhpSpreadsheet, PHPMailer, intervention/image…)
npm install                           # node_modules/ (Gulp)
npm run dev                           # = gulp; recompila build/ (opcional: build/ ya viene compilado)
# Base de datos:
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE sysai CHARACTER SET utf8mb4;"
"C:\xampp\mysql\bin\mysql.exe" -u root sysai < db/schema.sql   # 33 tablas + 26 vistas
"C:\xampp\mysql\bin\mysql.exe" -u root sysai < db/seed.sql     # datos de prueba
# Arrancar (desde la raíz del proyecto):
local3000                             # php -S localhost:3000  → http://localhost:3000
```

**Configuración por entorno (secretos, NO versionados — ver §6 VULN-1):**
- `includes/config/database.php` — conexión local (root / sin pass / `sysai`). Gitignored.
  Plantilla versionada: `database.example.php`. `conectarDB()` hace `mysqli_report(MYSQLI_REPORT_OFF)`
  porque el código comprueba valores de retorno (no usa try/catch).
- `includes/config/mail.php` — credenciales SMTP. Gitignored. Plantilla: `mail.example.php`.
  - **Recuperación de contraseña:** el helper `enviarTokenRecuperacion()` (en `funciones.php`) usa
    PHPMailer vía SMTP. Flujo `/chgpsswd → /token_verify → /updtepsswd` (bug corregido: antes el fallo
    de SMTP devolvía a `/chgpsswd`).
    - **Dev actual:** `mail.php` apunta a **Mailtrap sandbox** (`sandbox.smtp.mailtrap.io:587`, TLS) →
      los correos se capturan en la bandeja de mailtrap.io (NO llegan a inboxes reales). Verificado OK.
    - Si `username`/`password` quedan vacíos → modo *fallback*: registra el token en
      `includes/logs/mail.log` y el flujo avanza igual (para testear sin SMTP).
    - **Requisito TLS (Windows):** `C:\php\php.ini` tiene `openssl.cafile` y `curl.cainfo` apuntando a
      `C:\xampp\apache\bin\curl-ca-bundle.crt` (sin esto, el TLS a Gmail/Mailtrap falla). Reiniciar el
      servidor `local3000` tras cambiar `php.ini`.
- **`db/schema.sql`** — estructura saneada y corregida (ver §7). **`db/seed.sql`** — datos ficticios.
  **Credenciales de prueba (todas con `Test1234*`):** `admin@sysai.test`, `contador@sysai.test`,
  `coordinador@sysai.test`.
- Punto de entrada único: **`index.php`** (front controller).
- **Producción:** Apache/Hostinger con `.htaccess` (sí enruta y protege). Allí `database.php`/`mail.php`
  tendrían las credenciales del proveedor (a rotar — ver §6).

## 4. Arquitectura y flujo de ejecución

### Front controller y enrutamiento
1. **`index.php`** — arranca sesión, fija cabecera CSP, carga `includes/app.php`, registra rutas públicas
   (login, logout, recuperación de contraseña) y, **según `$_SESSION['cargo_id']`**, incluye el archivo de
   rutas del rol correspondiente:
   - `cargo_id == 1` → **`iadmin.php`** (Administrador, layout `layout_admin.php`).
   - `cargo_id == 2` → **`iconta.php`** (Contador, layout `layout_contador.php`).
   - `cargo_id == 3` → **`icoordi.php`** (Coordinador, layout `layout_coordinador.php`).
   - Finalmente llama a `$router->comprobarRutas()`.
2. **`Router.php`** (`MVC\Router`) — router minimalista: arrays `rutasGET`/`rutasPOST`, métodos
   `get()`/`post()`, `comprobarRutas()` (despacha por `REQUEST_URI` + método), y `render()` /
   `renderssdbr()` (este último para vistas sin sidebar, p. ej. login).
   - **Autorización por rol = carga condicional de rutas.** Las rutas de admin solo se registran si
     `cargo_id==1`; un coordinador ni siquiera las tiene registradas (caen en 404). Es el principal
     mecanismo de control de acceso. La lista `$rutas_protegidas` en `comprobarRutas()` solo verifica
     `isset($_SESSION['login'])`, NO el rol. ⚠️ Ver §6.

### Capa de datos — `models/ActiveRecord.php` (clase base)
- `setDB()`, `guardar()` (decide crear/actualizar por `$this->id`), `crear()`/`actualizar()`/`eliminar()`
  y variantes `*sinRedireccion()` (las normales hacen `header(Location...)` + `exit` tras éxito → la
  redirección está acoplada al modelo).
- Lectura: `all()`, `find($id)`, `findxatributo()`, `findwithparameters()`, `consultarSql()`, etc.
- **`sanitizarAtributos()`** usa `escape_string` en escrituras; **`convertirAMayusculas()`** fuerza TODO
  string a MAYÚSCULAS antes de insertar (decisión de negocio: los datos se almacenan en mayúsculas).
- Helpers de reportes Excel embebidos en ActiveRecord: `insertarCeldasReportePOA()`,
  `insertarRendicionesFuente()`, `insertarDatosDesdeArray()`, `combinarCeldasRepetidas()`,
  `insertarDatosDesdeArrayEgresosRendiciones()`.
- `setUsuarioActual()` ejecuta `SET @usuario_actual = '<descripcion del usuario>'` para auditoría en BD.

### Modelos (`models/`)
~80 archivos. Dos tipos:
- **Modelos de tabla:** `Usuario`, `Persona`, `Cargo`, `Poa`, `Programa`, `Producto`, `Actividad`,
  `Resultado`, `Rubro`, `CategoriaRubro`, `SubCategoriaRubro`, `TipoRubro`, `FuenteFinanciamiento`,
  `DetalleFinanciamiento`, `Rendicion`, `RendicionFf`, `OtrosIngresosEgresos`, `OieComprobante`,
  `OieTipoComprobante`, `TipoComprobante`, `TipoCambioDolar`, `TipoCambioEuro`, `Login`, etc.
- **Modelos de VISTA SQL** (`*Vista`, sufijo): `UsuarioVista`, `RendicionAdminVista`,
  `IngresoEgresoAdminVista`, `ReporteEgresosVista`, `SaldoFuenteFinanciamientoVista`,
  `login_session_vista` (tabla del modelo `Login`), etc. — consultas precompuestas en la BD.

### Controladores (`controllers/`) — 19 archivos
Estáticos, reciben `Router $router`. Patrón típico: `index` (listado/admin), `crear`, `actualizar`,
`eliminar`. Renderizan vistas con `$router->render('carpeta/vista', [datos])`.

### Vistas (`views/`) — 82 archivos
Una subcarpeta por entidad (`actividad/`, `usuario/`, `poa/`, `rendicion/`, `reporte/`, etc.),
cada una con `admin.php` (listado), `crear.php`, `actualizar.php`, `formulario.php` (parcial compartido).
Layouts: `layout.php`, `layout_admin/contador/coordinador.php`, `layout_login.php`.
Helper de escape: **`s()`** en `includes/funciones.php` (= `htmlspecialchars`). Se usa de forma
**inconsistente** (≈84 usos de `s()` vs ≈198 `echo` crudos). Ver §6.

## 5. Módulos funcionales (dominio)

| Módulo | Rutas base | Descripción |
|---|---|---|
| **Login / Auth** | `/login`, `/logout`, `/chgpsswd`, `/token_verify`, `/updtepsswd` | Autenticación, bloqueo por 5 intentos (5 min), recuperación de contraseña por email con `reset_token`. |
| **Usuarios** | `/usuario/*` | CRUD de usuarios + `persona` asociada. Si es coordinador (cargo 3) se vincula a un POA/programa. |
| **Programas** | `/programa/*` | Programas de la ONG. |
| **POA** | `/poa/*`, `/reporte/guardarpoa`, `/reporte/modificarpoa` | Plan Operativo Anual; vincula coordinador↔programa. |
| **Resultados / Productos / Actividades** | `/resultado/*`, `/producto/*`, `/actividad/*` | Jerarquía de planificación: Resultado → Producto → Actividad. |
| **Rubros / Categorías** | `/rubro/*`, `/categoria_rubro/*` | Partidas presupuestarias (tipo rubro 1 vs 2 se usa en reportes). |
| **Fuentes de financiamiento** | `/fuente_financiamiento/*`, `/dfinanciamiento/*` | Fuentes (donantes) y su detalle. |
| **Rendiciones** | `/rendicion/*`, `/rendicionff/*` | Rendición de cuentas con comprobantes (RUC, serie, número, monto) por fuente. |
| **Otros Ingresos/Egresos (OIE)** | `/ingreso_egreso/*` | Movimientos no ligados a rendición, con comprobantes. |
| **Tipos de cambio** | `/tcambio/dolar/*`, `/tcambio/euro/*` | TC para convertir montos S/ → USD/EUR en reportes. |
| **Reportes** | `/reporte/poa`, `/reporte/rendiciones`, `/reporte/ingresos`, `/descargar`, ...desc | Exportación Excel con PhpSpreadsheet (POA, rendiciones, ingresos/egresos), con conversión de moneda. |
| **Saldos contables** | `/saldos_contables/saldos` | Saldos por fuente de financiamiento. |

**Roles (`cargo_id`):** 1 = Administrador, 2 = Contador, 3 = Coordinador.

## 6. ⚠️ Vulnerabilidades y deuda de seguridad (PRIORITARIO — es producción)

> Estado: VULN-1 ✅, VULN-2 ✅, VULN-3 ✅, VULN-4 ✅ (todas resueltas este sprint).
> Residuales menores (CSP, debug, enumeración en recuperación) documentados abajo.
> Todos los cambios verificados en local con el seed.

### CRÍTICAS
1. ✅ **[RESUELTO — VULN-1]** Credenciales hardcodeadas externalizadas:
   - `includes/config/database.php` (gitignored) apunta a la BD local. Plantilla: `database.example.php`.
     **Nota:** este archivo estaba *trackeado* en el repo original (con creds de prod); se ejecutó
     `git rm --cached includes/config/database.php` para dejar de versionarlo. En nuevos clones NO existe →
     copiar `database.example.php` a `database.php`. Igual para `mail.php` (← `mail.example.php`).
   - SMTP movido a `includes/config/mail.php` (gitignored, plantilla `mail.example.php`).
   - ✅ **[HECHO 2026-06-01]** Revocado el app-password de Gmail (`pruebaskorteccorsmtp@gmail.com`) que
     estuvo versionado en `LoginController.php`. ⚠️ **Aún pendiente:** el secreto sigue en el **historial
     git** (commit `594f8e5`) → opcional purgarlo con `git filter-repo`/BFG + `push --force` (operación
     destructiva, reescribe SHAs). Verificar también las creds de BD de producción.
2. ✅ **[RESUELTO — VULN-2]** SQLi. Capa de datos parametrizada con *prepared statements*:
   - Helpers nuevos en `ActiveRecord`: `consultarPreparado()` / `ejecutarPreparado()` (usar SIEMPRE
     que haya valores del usuario).
   - `Login.php`: TODOS los métodos (incluido `existeUsuario()` — SQLi preauth) con `bind_param`.
   - `ActiveRecord`: `find/findmany/findxatributo/findxatributouno/findporRango/findwithmoretables/`
     `findwithparameters/findwithtableforanea/devolverIdforaneo/devolverTodoforaneo` parametrizados
     (los nombres de tabla/columna vienen del código, no del usuario); `get()` castea `LIMIT` a int.
   - `Usuario::comprobarCoordinador()` parametrizado.
   - `crear()/actualizar()/existeDato()/existeDescripcion()` siguen usando `escape_string` (mitigación
     aceptable; columnas controladas por el código). Verificado: intento de SQLi en login falla.
3. ✅ **[RESUELTO — VULN-3]** XSS. `s()` endurecido (`ENT_QUOTES`, UTF-8, null-safe). Escapadas todas
   las salidas de **propiedades de objeto** (`$obj->prop`) en listados y formularios (los sinks de XSS
   almacenado) — verificado: un `<script>` guardado se renderiza escapado. **Residual menor:** quedan
   echoes de variables planas (`echo $error`, `echo $fecha`, IDs enteros) sin `s()` — bajo riesgo
   (texto de la app / numéricos), envolver con `s()` cuando se toquen esas vistas.

### ALTAS / MEDIAS
4. ✅ **[RESUELTO — VULN-4 (autz)]** `Router::comprobarRutas()` ahora exige sesión en **toda** ruta no
   pública (lista blanca: `/`, `/login`, `/logout`, `/chgpsswd`, `/token_verify`, `/updtepsswd`, `/error`).
   La separación por rol sigue dándose por la carga condicional de rutas según `cargo_id` en `index.php`.
   *Mejora futura:* verificar rol también dentro de los controladores (defensa en profundidad).
5. ✅ **[RESUELTO — VULN-4 (CSRF)]** Helpers `csrf_token()/csrf_input()/verificar_csrf()` en
   `funciones.php`. El Router (`requiereCsrf()`) exige token en **todas las acciones que mutan datos**:
   rutas `*/crear`, `*/actualizar`, `*/eliminar`, `/reporte/modificarpoa`, `/reporte/guardarpoa`
   → **419** si falta o no coincide. `csrf_input()` añadido a los ~50 formularios POST correspondientes.
   Verificado: crear sin token = 419, con token = pasa. (Los formularios-filtro de reporte conservan el
   token pero no se exige, por no mutar estado.)
6. ✅ **[RESUELTO — VULN-4 (token)]** `generarCodigoAleatorioSimple()` usa `random_bytes()`+`bin2hex`
   (token de reset criptográficamente seguro). `updatePsswrdUser()` invalida el token al usarlo.
7. ✅ **[RESUELTO]** `estaAutenticado()` ahora usa `session_status()`, `empty()` y `exit` (sin warnings).
8. **CSP permisiva:** `'unsafe-inline'` y `'unsafe-eval'` habilitados (index.php y .htaccess). *(pendiente, menor)*
9. ✅ **[parcial]** `debuguear()` del flujo de recuperación eliminado. Revisar otros usos de
   `debuguear/debuguearHTML` antes de producción. *(residual menor)*
10. ✅ **[parcial — login]** Login ahora muestra mensaje **neutro** ("Las credenciales ingresadas no son
    correctas") en vez de revelar si el usuario existe. *Residual:* el flujo de recuperación (`/chgpsswd`)
    aún revela existencia del correo → neutralizar a futuro.

### Aspectos correctos (ya bien hechos) ✅
- Contraseñas con `password_hash()`/`password_verify()` (bcrypt).
- `session_regenerate_id(true)` tras login; logout destruye sesión y cookie correctamente.
- Bloqueo por intentos fallidos (5 intentos / 5 min) en el login.
- `.htaccess` bloquea acceso directo a `controllers/`, `models/`, `includes/`, `vendor/`, `views/*.php`,
  y a archivos sensibles (`composer.*`, `*.sql`, `*.env`...). Cabeceras de seguridad presentes
  (X-Frame-Options, X-Content-Type-Options, cookies `httponly`/`secure`).
- Validación de IDs con `filter_var(..., FILTER_VALIDATE_INT)` en helpers de `funciones.php`.

## 7. Modelo de datos (BD `sysai` — MySQL 8)

> Documentado desde el dump del **2025-03-26** (entorno local, `DEFINER=root@localhost`).
> En producción la BD es `u612374195_sysai` (Hostinger). El dump completo con PII/hashes NO se versiona;
> ver `db/` (guardar ahí solo un dump `--no-data`). Motor InnoDB, charset mayormente `utf8mb3` (obsoleto).

### Jerarquía de planificación (POA)
```
programa (tipo_programa)
  └─ resultado            (+ resultado_detalle, indicador_resultado, avance_resultado)
       └─ producto        (+ producto_detalle, indicador_producto, avance_producto)
            └─ actividad   (+ detalle_actividad, indicador_actividad, avance_actividad)
                 └─ rubro  (categoria_rubro → subcategoria_rubro; tipo_rubro: 1=Bien, 2=Servicio)
poa (programa, anio, presupuesto, estado, usuario_id→coordinador)
detalle_financiamiento  (N:M programa ↔ fuente_financiamiento)
```
> Nota: las tablas `*_detalle`, `indicador_*` y `avance_*` existen pero están **vacías** y sin
> controladores/modelos → módulo de seguimiento/indicadores **no implementado** en la app (solo el esquema).

### Tablas de movimientos contables
- **`rendicion`** — gasto rendido por actividad: `actividad_id`, `tipo_comprobante_id`, `ff_id`
  (fuente financiamiento), comprobante (serie, numero, ruc, razon_social, monto, fecha_original).
- **`otros_ingresos_egresos` (OIE)** — ingresos/egresos sueltos: `poa_id`, `oie_comprobante_id`,
  `oie_tipo_id` (1=Ingreso, 2=Egreso), `ff_id`. Comprobante en **`oie_comprobante`** (con
  `oie_tipo_comprobante_id`: Factura/Boleta/DJ/Recibo).
- **`tipo_cambio_dolar` / `tipo_cambio_euro`** — TC por usuario y fecha (se usa el último registro).

### Catálogos
`cargo` (1 Administrador, 2 Contador, 3 Coordinador), `tipo_programa`, `tipo_rubro`,
`tipo_comprobante`, `oie_tipo`, `oie_tipo_comprobante`, `categoria_rubro`, `subcategoria_rubro`.

### Identidad
- **`persona`** (datos personales: `nro_documento` UNIQUE, apellidos, nombres, telefono).
- **`usuario`** (`persona_id` UNIQUE, `cargo_id`, `descripcion` = **código corto UNIQUE**, `email`,
  `password` char(60) bcrypt, `reset_token` varchar(20)). ⚠️ `email` **NO es UNIQUE**.

### Vistas SQL (≈22) — alimentan los modelos `*Vista`
- **`login_session_vista`** → modelo `Login`. Une usuario+persona+cargo y LEFT JOIN poa/programa
  (da `poa_id`/`programa_id` solo a coordinadores). Incluye `password` y `reset_token`.
- **`usuario_admin_vista`** → listado de usuarios (datos = apellidos+nombres).
- **`rendicion_admin`**, **`rubro_admin_vista`**, **`otros_ingresos_egresos_admin_vista`** → listados.
- Reportes: **`reporte_poa_rubros`**, **`reporte_poa_rubros_sumas`**, **`reporte_poa_rendicion`**,
  **`reporte_rendiciones`**, **`reporte_ingresos`** (oie_tipo=1), **`reporte_egresos`** (oie_tipo=2),
  **`reporte_fuentes`**, **`reporte_fuentes_programa`**, **`reporte_fuentes_programa_rendicion`**.
- Auxiliares: `fuente_por_actividad_vista`, `fuentes_por_poa_id`,
  `vista_fuentes_financiamiento_por_actividad`, `programa_poa_vista`, `programas_sin_coordinador_vista`,
  `usuarios_coordinador_vista`, `usuario_id_disponible_programa_vista`, `tipo_rubro_vista`,
  `vista_dolar`, `vista_euro`, `total_monto_rendiciones_por_actividad`, `cantidad_fuentes_rendicion`.
- Todas con `ALGORITHM=UNDEFINED` y `SQL SECURITY DEFINER` con definer `root@localhost`
  → ⚠️ al importar en Hostinger puede fallar/crear con definer equivocado (ajustar definer).

### ⚠️ Discrepancias esquema ↔ código y bugs de datos (verificar contra producción)
1. ✅ **[RESUELTO]** `usuario` NO tiene columnas `intentos` ni `estado`. El código muerto que las usaba
   (`aumentarIntentos/actualizarIntentos/bloquearUsuario/restablecerIntentos` + propiedades `intentos/estado`
   del modelo `Login`) fue **eliminado** al implementar C2. El control de intentos ahora es por **sesión**
   (`LoginController`) + **rate-limit en BD** (tabla `login_intentos`). No se requieren esas columnas.
2. **Auditoría sin implementar:** existe la tabla `auditoria` (y `ActiveRecord::setUsuarioActual()` que
   hace `SET @usuario_actual`), pero **el dump no trae triggers** y `auditoria.id` **no es AUTO_INCREMENT**.
   → La tabla nunca se llena automáticamente. Faltan los triggers que consumirían `@usuario_actual`.
3. **`avance` es `decimal(2,2)`** en `avance_actividad`/`avance_resultado` → rango máx **0.99**
   (no admite 1.00 / 100%). Probable error de tipo.
4. **Overflow de montos:** `monto`/`presupuesto` son `decimal(7,2)` (máx **99 999.99**) en `rubro`,
   `rendicion`, `oie_comprobante`, `poa`; `fuente_financiamiento.presupuesto` es `decimal(8,2)`
   (máx 999 999.99). Riesgo real para una ONG con montos mayores.
5. **`rendicion.fecha_original` es `varchar(500)`** mientras `oie_comprobante.fecha_original` es `date`
   → fechas de comprobante de rendición sin tipar (inconsistencia).
6. **`email` de `usuario` no es UNIQUE** y el login hace `... WHERE email=... LIMIT 1` → riesgo de
   ambigüedad si se duplica.
7. **`reset_token` en texto plano** (varchar 20) en la tabla; visible en dumps. Combinado con la
   generación débil (`str_shuffle`, ver §6.6) → recuperación de contraseña insegura.
8. Vistas con **joins implícitos** (coma + WHERE) en `rendicion_admin`, `rubro_admin_vista`,
   `usuario_admin_vista`, `vista_dolar/euro` (estilo antiguo, frágil pero funcional).
9. `reporte_poa_rubros_sumas` usa `SUM(DISTINCT u.monto)` → si dos rubros tienen el mismo monto, se
   suma una sola vez (posible **bug de reporte**).

### Datos sembrados relevantes
- Admin (`cargo_id=1`): usuario `CARK` / email `ROBERTOKAR97@GMAIL.COM`.
- Coordinadores (`cargo_id=3`): `BMRM` (programa San Marcos) y `BGJE` (programa "Ayudando a Niños").
- Fuentes: Latin Link, Alianza Solidaria/Finlandia, Antamina, Diócesis/Obispado de Huaraz.

### Estado del esquema versionado (`db/schema.sql`)
`db/schema.sql` es una versión **saneada y corregida** del dump (no idéntica a producción). Correcciones ya
aplicadas en el archivo (pendientes de migrar a producción con cuidado):
- Montos `decimal(7,2)` → **`decimal(12,2)`** en `rubro`, `rendicion`, `oie_comprobante`, `auditoria`.
- `avance` `decimal(2,2)` → **`decimal(5,2)`** en `avance_actividad`/`avance_resultado`.
- `rendicion.fecha_original` `varchar(500)` → **`date`**.
- Añadidas **FK `ff_id`** en `rendicion` y `otros_ingresos_egresos` → `fuente_financiamiento`.
- `auditoria.id` ahora **AUTO_INCREMENT**.
- Charset de `tipo_programa` unificado a `utf8mb3` (el dump usaba `utf8mb4_0900_ai_ci`, solo MySQL 8).
- Vistas sin `DEFINER root@localhost`.
- **BUG DE REPORTE corregido** en `reporte_poa_rubros` y `reporte_poa_rubros_sumas`: el dump unía
  `fuente_financiamiento`+`detalle_financiamiento`, duplicando cada rubro por cada fuente del programa
  (fan-out cartesiano). El `SUM(DISTINCT)`/`SELECT DISTINCT` originales eran parches. Se quitaron esos
  joins (el monto planificado no depende del nº de fuentes). Verificado con el seed: sumas correctas.
  ⚠️ **Producción sigue con las vistas con bug** hasta que se migre.

## 8. Pendiente / preguntas abiertas

- Confirmar contra **producción** las discrepancias de §7 (sobre todo `intentos`/`estado` y triggers de
  `auditoria`) y planificar la migración de las correcciones de `db/schema.sql`.

### Build de assets (Gulp) — RESUELTO/documentado
- **Pipeline** (`gulpfile.js`): SCSS `src/scss/**` → Dart Sass + autoprefixer + cssnano + sourcemaps →
  `build/css/app.css`. JS `src/js/**` → concat `bundle.js` + terser → `build/js/bundle.min.js`.
  Imágenes `src/img/**` → imagemin → `build/img/` y versión `.webp`.
- **Tareas exportadas:** `gulp css` (compila estilos y **queda escuchando** `src/scss`, auto-recompila),
  `gulp js`, `gulp imagenes`, `gulp webp`, `gulp build` (todo, **sin** watcher; para CI/prod),
  `gulp` / `npm run dev` (todo + watcher de scss/js/img). `npm run css` → `gulp css`.
- **Fixes aplicados este sprint:**
  - Eliminado `node-sass@^9` de `package.json` (dependencia **muerta**: el gulpfile usa Dart Sass
    `require('sass')`; node-sass rompía `npm install` en Node 24 por compilación nativa).
  - Añadido `@use "sass:color";` en `_variables.scss` y `_sidebar.scss` (usaban `color.adjust()` sin
    declarar el módulo → fallaba la compilación con Dart Sass).
  - `npm run css` ya funciona (antes el gulpfile no exportaba la tarea `css`).
  - Verificado: `gulp build` compila OK y `gulp css` recompila automáticamente al cambiar un `.scss`.
- **Pendiente menor:** los `@import` de Sass están *deprecated* (Sass 3.0 los quitará); migrar a
  `@use/@forward` a futuro. `build/css/` contiene SVGs de bootstrap-icons (no los genera Gulp; revisar).

## 9. Prioridades acordadas con el usuario (para próximas sesiones)

1. **Corregir vulnerabilidades** (ver §6; empezar por credenciales + SQLi del login + XSS).
2. **Documentar/entender** la lógica de negocio (reportes POA y rendiciones, conversión de moneda).
3. **Refactorizar** la capa de datos hacia consultas preparadas y validaciones consistentes.

## 10. Convenciones del proyecto

- Comentarios y nombres en **español**; identificadores de dominio en español (`fuente_financiamiento`,
  `rendicion`, etc.).
- Datos de texto se almacenan en **MAYÚSCULAS** (forzado en `ActiveRecord::convertirAMayusculas`).
- Controladores con métodos **estáticos**; el patrón CRUD es `index/crear/actualizar/eliminar`.
- La redirección post-guardado vive en el modelo (`crear()/actualizar()` hacen `header()+exit`); usar las
  variantes `*sinRedireccion()` cuando se necesite encadenar operaciones.
- Tras una operación, se redirige a `/<entidad>/admin?resultado=N` y `mostrarNotificacion(N)` traduce el
  código a mensaje (1=creado, 2=actualizado, 3=eliminado...).

## 11. Backlog de bugs y vulnerabilidades PENDIENTES (auditoría 2026-05-31)

> Lista crítica de lo que falta tras cerrar VULN-1..4. Marcar como resuelto al completar.

### 🔴 Críticas
- ✅ **[RESUELTO] C1 — Hash de contraseña corrompido al crear/editar por la UI.** Se añadió
  `ActiveRecord::$columnasSinMayuscula = ['password','reset_token','email']` y `convertirAMayusculas()`
  ahora los excluye. Verificado: usuario creado por la capa de modelos conserva el hash bcrypt y
  `password_verify` = OK; `email` se preserva; el resto de campos siguen en MAYÚSCULAS.
- ✅ **[RESUELTO] C2 — Lockout de intentos.** Dos capas: (1) contador en `$_SESSION` (primera barrera,
  ya existía); (2) **rate-limit persistente en BD** (tabla `login_intentos`, por **IP** y por **email**,
  5 intentos / 5 min cada uno) que cierra el bypass de la capa de sesión (un atacante sin cookies ya no
  la evade). Métodos en `Login`: `obtenerIp()`, `estaBloqueadoPorIntentos()`, `registrarIntentoFallido()`,
  `limpiarIntentos()` (al éxito), `purgarIntentosAntiguos()` (limpieza oportunista). La ventana se evalúa
  con `NOW()` de MySQL (no con la hora de PHP) para evitar desajustes de zona horaria. Tabla en
  `db/schema.sql` + `db/migracion_rate_limit_login.sql` (idempotente, para Hostinger). Se eliminaron las
  funciones muertas `aumentarIntentos/actualizarIntentos/bloquearUsuario/restablecerIntentos` y las
  propiedades `intentos/estado` del modelo (usaban columnas inexistentes). **Verificado end-to-end vía
  HTTP:** 6 POST sin cookies → el 6º bloqueado por BD; password-spraying (5 emails distintos, misma IP)
  bloqueado por el límite de IP; login correcto redirige (302) y limpia los intentos.
  ⚠️ **Pendiente migrar a producción** ejecutando `db/migracion_rate_limit_login.sql` en Hostinger.

### 🟠 Altas
- 🟡 **[PARCIAL] A1 — IDOR / control de acceso por registro.** Helpers en `funciones.php`:
  `cargoActual`, `esAdmin/esContador/esCoordinador`, `exigirRol`, `poaIdCoordinador`,
  `programaIdCoordinador`, **`exigirPoaPropio`**, **`programaIdPorActividad`** y
  **`exigirProgramaPropioPorActividad`** (resuelve actividad→producto→resultado→programa).
  Aplicado y **verificado**:
    - `IngresoEgresoController` (crear/indexff/actualizar/eliminar) → scope por `poa_id`. 403 a OIE ajeno.
    - `RendicionController` (index/crear/actualizar/eliminar) → scope por programa de la actividad.
      403 a rendiciones/actividades de otro programa; admin no acotado. Verificado con un programa ajeno.
  **PENDIENTE replicar** en: `ResultadoController`, `ProductoController`, `ActividadController`,
  `RubroController` (usar `exigirProgramaPropioPorActividad()` para rubro/actividad; para resultado/producto
  comparar `programa_id`/cadena con `programaIdCoordinador()`).
- 🟡 **[PARCIAL] A2 — Mass assignment / escalada.** `UsuarioController` ahora exige rol admin (`exigirRol([1])`)
  en index/crear/actualizar/eliminar (defensa en profundidad). En `IngresoEgresoController` el coordinador
  ya **no puede** forzar `oie_tipo_id` (se fuerza Egreso=2) ni `poa_id` (se fuerza el suyo) vía POST.
  **PENDIENTE:** whitelist de campos por modelo/rol y forzado equivalente en rendicion/resultado/… para
  impedir setear `programa_id`/ids ajenos vía `$_POST` (`new Modelo($_POST[...])` / `sincronizar()`).
- **A3 — `reset_token`: texto plano, sin expiración, sin rate-limit.** Hashear en BD, añadir TTL y limitar envíos.
- **A4 — `.git/` y `db/*.sql` servibles.** `.htaccess` no bloquea `.git`; bajo `php -S` no aplica `.htaccess`.
  En Apache, `/.git/` expone código+historial (incl. SMTP filtrado). Bloquear `.git` y denegar `db/`.
- **A5 — Enumeración de usuarios en `/chgpsswd`** (revela si el correo existe). Mensaje neutro.

### 🟡 Medias
- **M1 — CSP permisiva** (`'unsafe-inline'`, `'unsafe-eval'`).
- **M2 — Sesión/transporte:** cookies seguras solo vía `.htaccess`; sin HSTS ni timeout de sesión en código.
- **M3 — Escrituras sin prepared statements** (`crear/actualizar/existeDato/existeDescripcion` usan `escape_string`+join).
- **M4 — XSS residual:** `echo` de variables planas (`$error`, ids) y concatenaciones sueltas sin `s()`.
- **M5 — CSRF parcial:** falta en formularios-filtro de reportes (no mutan) y en formularios públicos de auth (login-CSRF).
- **M6 — Funciones de debug** (`debuguear`, `debuguearHTML`) accesibles.

### 🐛 Bugs funcionales / datos
- ✅ **[RESUELTO] B1 — `/saldos_contables/saldos` fatal.** Se crearon las 4 vistas faltantes
  (`vista_total_ingresos`, `vista_total_egresos`, `vista_saldo_contable`,
  `vista_saldo_fuente_financiamiento`) en `db/schema.sql` y en `db/migracion_saldos.sql` (idempotente,
  para Hostinger). Regla contable: **Saldo = Presupuesto + Ingresos(OIE tipo 1) − Egresos(OIE tipo 2 +
  rendiciones)**, imputado por `ff_id`; subconsultas agregadas independientes para evitar fan-out.
  Además `consultarSql()`/`consultarSqldvolveruno()` ahora manejan el `false` de un query fallido
  (registran en `error_log` y devuelven `[]`/`null` en vez de `fetch_assoc()` sobre bool). Verificado
  end-to-end con el seed (Ingresos 85000, Egresos 17650, Saldo 67350; fuentes 38550 / 28800).
  ⚠️ **Pendiente migrar a producción** ejecutando `db/migracion_saldos.sql` en Hostinger.
- **B2 — Reportes POA inflados en PRODUCCIÓN** (fan-out por fuentes; corregido en `db/schema.sql`, falta migrar).
- **B3 — Esquema desalineado** (corregido en `db/schema.sql`, falta migrar): `auditoria` sin triggers ni
  AUTO_INCREMENT (auditoría no funciona); `avance decimal(2,2)`; `monto decimal(7,2)` overflow;
  `rendicion.fecha_original varchar`; `email` no UNIQUE. *(El desajuste `intentos`/`estado` ya se resolvió
  eliminando el código muerto — ver §7.1.)*
- **B4 — MAYÚSCULAS forzadas** indiscriminadas (origen de C1; degrada calidad de datos).
- **B5 — Código muerto / de otro proyecto:** `includes/templates/formulario_propiedades.php`,
  `formulario_vendedores.php`, `anuncios.php` (parecen de bienes raíces); `setImagen/borrarImagen` sin validar archivo.
- **B6 — `validarPropiedadArray()`** sin `isset` (warnings).
- **B7 — Deuda de build:** `@import` Sass *deprecated* (migrar a `@use/@forward`); `build/css/` con SVGs commiteados (basura).
