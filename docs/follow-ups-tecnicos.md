# Follow-ups técnicos (deuda pendiente)

> **Referencia.** Extraído de `CLAUDE.md` el 2026-07-09; sincronizado el 2026-07-16. Aquí se conserva el
> detalle completo (hechos y pendientes). El `CLAUDE.md` mantiene solo un resumen de los pendientes abiertos.

## Hechos ✅
- [x] ✅ **SMTP externalizado al `.env`** (integrado en `integ/seguridad`). `LoginController` ya no tiene credenciales; el envío usa el helper `enviarTokenRecuperacion()` y `includes/config/mail.php` lee las claves `MAIL_*` del `.env`. Sin credenciales hardcodeadas en código trackeado.
- [x] ✅ **Seed data** para despliegue desde cero: `database/seed.sql` (idempotente, `INSERT IGNORE`) con catálogos (`cargo`, `tipo_programa`, `tipo_rubro`, `oie_tipo`, `oie_tipo_comprobante`, `tipo_comprobante`, `categoria_rubro`, `subcategoria_rubro`) + usuario admin inicial (`admin@arcoiris.pe` / `Arcoiris2026*`, temporal). Validado en BD limpia. Documentado en `database/README.md`.
- [x] ✅ Relación rendición↔rubro resuelta (migr. 017): `rendicion.rubro_id` reemplaza a `actividad_id`; límite Σ rendiciones ≤ monto del rubro. Ver item 5 del backlog (`docs/historial-implementacion-items-2-6.md`).

## Pendientes abiertos
- [x] ~~`usuario` no tiene columnas `intentos`/`estado` pero `Login.php` histórico las referencia~~ — **CERRADO 2026-07-15:** resuelto en `main` (migr. 011 creó `login_intentos`; `models/Login.php` la usa).
- [x] ~~Retirar/limpiar modelo `RendicionFuentesCantidadVista`~~ — **HECHO 2026-07-15** (plan de montos §5.4): modelo eliminado junto con sus llamadas en `ReportePoaRubrosController`; `$ffnro` no se usaba en ninguna vista.
- [x] ~~**B2 — Reportes POA/Excel inflados** (fan-out por fuentes)~~ — **CERRADO 2026-07-16 (item 9):** al
  verificar, el `SUM(DISTINCT)` histórico ya no existía en la vista y `/reporte/poa` no imprime fuentes.
  El residuo real era otro: las sumas de rendición se agrupaban a nivel ACTIVIDAD y caían desalineadas en la
  fila del último rubro. Resuelto con la vista por rubro×fuente (migr. 034, solo aprobadas del ejercicio) y
  `ReporteRendicionXlsxBuilder` (columnas calculadas, TOTAL etiquetado, fila de transferencia al
  Institucional). QA celda a celda en `qa_reportes.ps1` + `qa_leer_xlsx.php`.
- [x] ~~**B3 — Esquema desalineado**~~ — **CERRADO 2026-07-16** (barrido de verificación contra la BD viva,
  detalle en `docs/modelo-datos-detalle.md`): overflow de montos (migr. 026 + `montoNumerico()`/`MONTO_MAXIMO`),
  `rendicion.fecha_original` (`date`), `usuario.email` UNIQUE (migr. 032) y `avance` (ya era
  `decimal(5,2)`/`(7,2)` — la nota "decimal(2,2)" venía de un dump viejo; admite 100%). La **auditoría sin
  triggers** queda **diferida a v1.1 por decisión (2026-07-16)** con hallazgos anotados: `auditoria.usuario
  varchar(8)` no cabe el email (identidad desde migr. 024) y revisar `auditoria.id` AUTO_INCREMENT.
- [x] ~~**B4 — MAYÚSCULAS forzadas**~~ — **HECHO 2026-07-16:** se retiró `convertirAMayusculas()` y
  `$columnasSinMayuscula` de `ActiveRecord` (los datos se guardan tal como se ingresan) y los
  `style="text-transform: uppercase"` de los formularios (8 vistas). Los códigos autogenerados
  (`siguienteCodigoCorrelativo`/`Jerarquico`) no dependían del forzado (prefijos constantes en mayúsculas).
  Comparaciones contra catálogos en MAYÚSCULAS: sin impacto (collation `utf8_general_ci`, case-insensitive).
  Suite QA 131/131.
  **Cerrado del todo el 2026-08-12:** el barrido del 16-jul quitó los `style=` de 8 vistas pero **dejó vivo el
  gemelo global en SCSS** (`src/scss/layout/_sidebar.scss`: `input:not([type="password"])` y `textarea` con
  `text-transform: uppercase` + `font-family: Lato`), así que TODOS los formularios seguían mostrando en
  MAYÚSCULAS lo que se guardaba tal cual, y arrastraban la fuente vieja fuera del tema. Se eliminaron ambas
  reglas y se recompiló `build/css/app.css`. El caso más dañino era el login: el código de verificación se
  veía en mayúsculas pero se compara en minúsculas.
- [x] ~~**B5 — Código muerto / de otro proyecto**~~ — **HECHO 2026-07-16:** eliminados `includes/templates/`
  completo (6 archivos; `anuncios.php` usaba la clase inexistente `App\Propiedad` y habría fatal-errorado),
  `incluirTemplate()`, `TEMPLATES_URL`, `FUNCIONES_URL`, `CARPETA_IMAGENES`, `setImagen()`/`borrarImagen()`
  (sin caller externo; `borrarImagen()` corría en cada `eliminar*()` sobre una propiedad inexistente →
  warning en PHP 8.2+) y `intervention/image` de composer (cero referencias; se fueron también guzzlehttp/psr7
  y ralouphie/getallheaders). QA 131/131.
- [x] ~~**B6 — `validarPropiedadArray()`** sin `isset`~~ — **HECHO 2026-07-16:** NO era función muerta
  (caller real: `UsuarioController.php:48`, detecta si se eligió programa al crear coordinador). Reescrita
  como `!empty($array[$propiedad][$subpropiedad])`: misma semántica, sin warnings cuando falta la clave.
- [x] ~~**B7 — Deuda de build**~~ — **HECHO 2026-07-16:** los 5 `@import` Sass de `app.scss` migrados a
  `@use` (`_login.scss` ahora consume variables con prefijo `v.`; los `@import url()` de Google Fonts son
  CSS y quedan). CSS compilado **byte-idéntico** tras la migración. Limpieza de `build/css/`: eliminados
  2.081 archivos no referenciados (2.051 SVGs sueltos de bootstrap-icons, 15 css + 15 map de variantes
  bootstrap sin uso, y el dir `font/` duplicado). Quedan solo los 3 CSS que cargan los layouts
  (`app.css`, `bootstrap.min.css`, `bootstrap-icons.min.css`), sus maps y `fonts/` (woff/woff2, la fuente
  real de los iconos). Smoke HTTP 200 en todos + suite QA 131/131.
- [ ] Confirmar con el **contador de la organización** el mapeo compra/venta del TC (ingreso→compra,
  gasto→venta, saldo→compra): está derivado por lógica NIC 21, no por norma interna (plan de montos §5.3).
  **Documento de consulta listo (2026-07-16): `docs/confirmar-tc-contador.md`** — mapeo implementado,
  5 preguntas concretas y los puntos exactos del código a tocar si el contador contradice. Resolver ANTES
  de registrar transacciones reales (el TC congelado se copia por valor y no se recalcula).
- [x] ~~**`sql_mode` sin `STRICT_TRANS_TABLES`**~~ — **HECHO 2026-07-16:** `conectarDB()` fija
  `SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'` (por sesión →
  portable a Hostinger sin `my.cnf`; cubre app + `migrate.php`). También se hizo explícito
  `mysqli_report(MYSQLI_REPORT_OFF)` (estaba documentado pero no existía en el código; sin él, PHP 8.2 lanza
  excepciones y el patrón del código es comprobar valores de retorno). Verificado: replay greenfield
  (baseline + 001-032 + seed/seed_demo/seed_qa) bajo estricto sin errores + suite QA 131/131.
  Nota: `includes/config/database.php` ya estaba versionado (sin secretos) — se corrigió la doc que decía "gitignored".
- [x] ~~Revisar las discrepancias restantes del modelo de datos~~ — **HECHO 2026-07-16:** barrido completo
  contra la BD viva; `docs/modelo-datos-detalle.md` actualizado. Solo quedan abiertos: auditoría (⏸ v1.1,
  por decisión) y B2/reportes Excel (item 9, v1.1).
