# Auditoría pre-despliegue — 2026-09-14

> **Estado:** ✅ correcciones de código **hechas y pusheadas a `dev`** (suite QA **193/193**).
> Queda lo que no es código: rotar credenciales, verificar en un Apache real, la checklist del
> despliegue greenfield y el merge `dev → main`. **La lista consolidada de pendientes vive en
> `CLAUDE.md` (*PENDIENTES / FOLLOW-UPS*)**; aquí está el detalle.

Revisión completa del código antes del despliegue greenfield en Hostinger: router y rutas por rol, los
20 controladores, `ActiveRecord`, login y recuperación, vistas, `.htaccess`, manejo del `.env`, historial
de git, dependencias (Dependabot) y el entorno de desarrollo.

## 1. Veredicto

- **Sin inyección SQL ni XSS explotables.** Toda consulta con datos del usuario usa sentencias
  preparadas; las vistas escapan con `s()`.
- **El control por rol funciona:** las rutas solo se registran para el cargo de la sesión, con
  `exigirRol()` como segunda barrera. Un coordinador que pide `/usuario/admin` o `/saldos_contables/saldos`
  cae en `/error`.
- **Nadie entra a la base de datos sin credenciales:** el `.env` puede vivir fuera del document root y
  nunca estuvo versionado.

## 2. Hallazgos y estado

| # | Hallazgo | Estado |
|---|---|---|
| H1 | Credenciales en el historial de git (BD vieja en `database.php`, SMTP de Gmail en `LoginController`); las de Mailtrap pasaron por un chat | ✅ **2026-09-15**: App Passwords de `pruebaskorteccorsmtp@gmail.com` y `korteccor@gmail.com` revocadas (la segunda seguía viva en un `.env` local y cifrada en `secrets/.env.enc`); passphrase rotada; historial **no** se reescribe (decisión del usuario). BD vieja de Hostinger eliminada junto con su sitio (confirmado por el usuario); inbox de Mailtrap borrado. **Cerrado** |
| H2 | `CLAUDE.md`, `docs/` y los PHP de la raíz servibles; sin `display_errors=0` | ✅ `863161c`: `.htaccess` deniega `*.md`, `docs/` e `iadmin/iconta/icoordi/Router/hash.php`; guardas en los archivos de rutas; `display_errors=0` fuera de desarrollo |
| H3 | HTTPS no forzado | ✅ `863161c`: redirección 301 (exentos localhost y LAN privada). ⏳ Verificar en Apache real |
| H4 | PhpSpreadsheet 4.1.0 con 9 avisos | ✅ `fdf10f3`: 5.9.0, `composer audit` limpio |
| H5 | Admin del seed con clave conocida (`hash.php`, docs) | ⏳ Checklist de despliegue: activarlo con clave nueva |
| M1 | El coordinador leía productos, actividades y rubros de otros programas cambiando el id de la URL | ✅ `c7d3de1`: `exigirProgramaPropio*` en los tres `index`; QA +5 |
| M2 | `/rendicionff/*` sin guardas (código muerto) | ✅ `c7d3de1`: retirado |
| M3 | La sesión no se revalidaba (cargo, programa o contraseña cambiados seguían valiendo) | ✅ `021b26e`: `Login::sesionSigueValida()` en cada petición |
| M4 | Inyección de fórmulas en los Excel (textos que empiezan por `=`) | ✅ `fdf10f3`: `Model\XlsxValorSeguroBinder` vía `nuevoLibroXlsx()` |
| M5 | CSP con `cdn.jsdelivr.net`, sin `frame-ancestors`; `use_strict_mode` inerte | ✅ `863161c` |
| M6 | Acceso a la BD en producción | ✅ B9 corregido (`021b26e`). ⏳ Usuario MySQL de mínimo privilegio y MySQL remoto apagado |
| M7 | El bloqueo por email dejaba que cualquiera bloqueara la cuenta de otro | ✅ `021b26e`: bloqueo por pareja (IP, email) + tope de 30 por IP |
| Baja | `/logout` por GET, CSRF con 419, `Options -Indexes` dentro de `<IfModule>` | ✅ `863161c` |
| Baja | Enumeración por tiempo en el login; `CURLOPT_FOLLOWLOCATION` en la consulta SBS; `style-src 'unsafe-inline'` | ⏸ Sin tratar (riesgo bajo) |
| Dev | MariaDB de XAMPP escucha en todas las interfaces con `root` sin contraseña | ⏳ Bloquear el 3306 en el firewall antes de otra sesión en la LAN |

## 3. Dependabot (74 alertas abiertas)

- **Composer (9):** PhpSpreadsheet ya está en 5.9 en `dev`.
- **npm (65, todas de la cadena de build):** `1a9db52`. Se retiraron 7 plugins de Gulp sin uso real
  (imagemin, webp, cache, notify, clean, sourcemaps, autoprefixer); subieron gulp 5, cssnano 7 y
  gulp-sass 6. Detalle en `docs/build-assets.md`.
- **`immutable` 3.x en BrowserSync:** descartada en GitHub como riesgo tolerable. Forzar la 4 rompe
  BrowserSync y solo corre en desarrollo, atado a 127.0.0.1.
- ⚠️ **Dependabot analiza `main`:** el panel se limpia al mergear `dev → main`.

## 4. Entorno: PHP 8.3 y zona horaria (`7b4744b`)

- **PHP 8.3 es la versión oficial.** La 8.2 deja de recibir parches el 31 dic 2026.
  - Consola, Composer y QA usan `C:\php` (8.3.28).
  - Apache de XAMPP queda en 8.2.12, como diferencia aceptada.
  - `composer.json` fija `config.platform.php = 8.3.0`.
- **Zona horaria fijada en código:** `America/Lima` en PHP y `-05:00` en la sesión MySQL. Antes dependía
  del `php.ini` (UTC o Berlín).

## 5. Checklist del despliegue greenfield (Hostinger)

- [ ] Elegir **PHP 8.3** en hPanel y activar **SSL**.
- [ ] Crear la BD con un **usuario MySQL exclusivo** (SELECT/INSERT/UPDATE/DELETE; las migraciones con otro
      usuario) y dejar **MySQL remoto apagado**.
- [ ] Importar baseline + `php database/migrate.php` (001-035) + `seed.sql` (solo catálogos). El `DEFINER` de
      las vistas ya se retiró del baseline (2026-09-15): con él la importación fallaba sin privilegio SUPER.
      **Procedimiento completo paso a paso: `docs/despliegue-hostinger.md`.**
- [ ] **`.env` en `../secrets/.env`** con permisos 600 y `APP_ENV=production`; `MAIL_LOG_PATH` fuera del
      document root.
- [ ] **Subir por lista blanca:** sin `.git/`, `.claude/`, `src/`, `node_modules/` ni `docs/`. Compilar antes
      con `npx gulp build`. `database/` se sube **temporalmente** (baseline, migraciones, seed y
      `crear_admin.php` corren en el servidor por SSH) y **se borra al terminar**.
- [ ] **Correo:** la cuenta dedicada **ya existe** (`cronosarca2024@gmail.com`, creada y probada el
      2026-09-15). Al desplegar: **generar una App Password nueva solo para el servidor** y revocar la de
      prueba (pasó por una sesión de chat). Plantilla en `.env.example`. Cuando haya dominio, pasar a
      `no-reply@<dominio>` en Hostinger cambiando solo el `.env`.
- [ ] **Crear el administrador y probar el correo desde el servidor:**
      `php database/crear_admin.php --email <correo real> --nombres "…" --apellido-paterno "…" --dni <n> --probar-correo`.
      Si falla, el 587 (o el 465) no sale o la credencial no sirve: sin eso nadie activa su cuenta. El seed ya
      no trae admin ni `hash.php` claves de ejemplo (2026-09-14).
- [ ] **`REMOTE_ADDR` es la IP real del cliente:** sin CDN delante (o con la IP real restaurada). Si todas las
      peticiones llegan con la IP del CDN, los límites de login y de recuperación se comparten entre todos.
- [ ] En `phpinfo()` (temporal, luego borrarlo): `session.save_path` propio de la cuenta y
      `session.gc_maxlifetime` = 3600 (lo fija `index.php`).
- [ ] **Verificar el `.htaccess` en el servidor:** `pwsh -File database\verificar_htaccess.ps1 -BaseUrl
      https://<dominio>` desde el equipo local → 0 FAIL (43 rutas sensibles en 403/404, cabeceras, HSTS,
      redirección a HTTPS). Ensayo contra el Apache de XAMPP hecho el 2026-09-15 (58/58), que destapó y
      corrigió dos cosas: los `ErrorDocument` mandaban los bloqueos a la app (respondían **302 → /login**
      en vez de 403/404) y `X-Powered-By` anunciaba la versión de PHP. Hostinger usa LiteSpeed: repetir allí.
- [ ] Probar en el navegador el login, el botón "Cerrar sesión" y la descarga de un reporte. Ensayo en el
      Apache de XAMPP (2026-09-15): login y "Cerrar sesión" OK con la CSP del `.htaccess` activa (consola sin
      errores; `GET /logout` no cierra la sesión, `POST /logout` sí). Repetir en el servidor.

## 6. Cómo se verificó (para repetirlo)

La suite corre contra `php -S` con una **copia** del `.env`, para no tocar el real (con el `.env`
desactualizado la app responde 500). El runner resetea la BD, así que se respalda antes:

```powershell
pwsh -File database\respaldo.ps1 guardar -Etiqueta pre-qa
$env:SYSAI_ENV_FILE="<copia del .env>"; php -S localhost:3005      # en otra consola
pwsh -File database\qa_all.ps1 -BaseUrl http://localhost:3005
pwsh -File database\respaldo.ps1 restaurar -Archivo <el respaldo> -Si
```

`qa_reportes.ps1` exige la migración 035; sin ella da 7 fallos que no son regresiones.
