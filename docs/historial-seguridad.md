# Historial — Sprint de Seguridad (Hardening)

> **Archivo histórico.** Extraído de `CLAUDE.md` el 2026-07-09 para aligerar la memoria caliente.
> Este sprint ya está **hecho, integrado y mergeado a `main`**. Se conserva como registro.

## Estado

✅ Este sprint (originalmente rama `seguridad/hardening-y-despliegue-local`) ya fue **integrado por
merge curado** (`4c4e6eb`) y **mergeado a `main`** (fast-forward, tip `5e24398`). El código de hardening
está en `main`; el SMTP se externalizó a `.env` y las 3 migraciones de seguridad viven en
`database/migrations/010-012` (aplicadas en la BD local y registradas en `schema_migrations`). Probado
end-to-end por HTTP (CSRF 419, auth, login 3 roles, saldos, errores neutros). Despliegue **greenfield**
(la producción de Hostinger fue dada de baja): al desplegar, importar `schema_baseline.sql` + migraciones
001-019 + `seed.sql` en BD nueva. QA visual de CSP/confirmaciones en local **verificado**. Ya **no es
urgente** (no hay sistema en vivo expuesto).

## Resumen (en la rama de seguridad)

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

## Migraciones de la rama de seguridad (ya portadas al runner)

Estos `.sql` vivían en `db/` (carpeta inexistente hoy). Ya portadas al runner como 010-012:
1. `migracion_saldos.sql` (B1 — 4 vistas de saldos; corrige también el fan-out B2)
2. `migracion_rate_limit_login.sql` (C2 — tabla `login_intentos`)
3. `migracion_recuperacion_segura.sql` (A3 — `reset_token` sha256/64 + `reset_token_expira` + tabla `recuperacion_intentos`)

## QA / acciones manuales de ese sprint

- [x] ✅ **QA visual de M1 — VERIFICADO:** botones de eliminar siguen pidiendo confirmación y la consola no muestra violaciones de CSP.
- **VULN-1 (residual):** el app-password de Gmail sigue en el **historial git** (commit `594f8e5`); opcional purgar con `git filter-repo`/BFG + `push --force` (destructivo). Verificar/rotar también las creds de BD de producción.
- [x] **Dependabot — REPARADO 2026-09-14** (74 alertas abiertas en ese momento). Composer: PhpSpreadsheet
  4.1 → 5.9 (9 alertas). npm (65, todas de desarrollo): se retiraron `gulp-imagemin`, `gulp-webp`,
  `gulp-cache`, `gulp-notify`, `gulp-clean`, `gulp-sourcemaps` y `gulp-autoprefixer` (sin uso real o
  reemplazados por Gulp nativo), subieron gulp 5, cssnano 7 y gulp-sass 6. Queda UNA alerta aceptada: `immutable`
  3.8.4 dentro de BrowserSync (solo se corrige en la 4, y forzarla rompe BrowserSync); descartada en
  GitHub como riesgo tolerable porque solo corre en desarrollo, atado a 127.0.0.1. ⚠️ Dependabot analiza **`main`**: el panel se limpia al
  mergear `dev → main`. Detalle en `docs/build-assets.md`.
