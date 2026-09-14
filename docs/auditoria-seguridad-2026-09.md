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
| H1 | Credenciales en el historial de git (BD vieja en `database.php`, SMTP de Gmail en `LoginController`); las de Mailtrap pasaron por un chat | ⏳ **Rotar** (solo el usuario). Reescribir el historial es opcional |
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
- [ ] Importar baseline + `php database/migrate.php` (001-035) + `seed.sql`. Ajustar o quitar el `DEFINER`
      de las vistas.
- [ ] **`.env` en `../secrets/.env`** con permisos 600 y `APP_ENV=production`; `MAIL_LOG_PATH` fuera del
      document root.
- [ ] **Subir por lista blanca:** sin `database/`, `.git/`, `src/`, `node_modules/` ni `docs/`. Compilar antes
      con `npx gulp build`.
- [ ] **Activar el admin del seed** con clave nueva por `/chgpsswd`, y quitar la clave de ejemplo de `hash.php`.
- [ ] **Correo:** decidir el emisor (ver la contradicción anotada en `CLAUDE.md`), comprobar la salida al
      puerto 587 (o 465) y usar una credencial propia del servidor.
- [ ] **Verificar con `curl`** que `/.env`, `/CLAUDE.md`, `/docs/`, `/iadmin.php`, `/database/migrate.php`,
      `/includes/logs/mail.log` y `/.git/config` devuelven 403/404, y que `http://` redirige a `https://`.
- [ ] Probar en el navegador el login, el botón "Cerrar sesión" y la descarga de un reporte.

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
