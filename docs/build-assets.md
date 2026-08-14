# Build de assets (Gulp)

> **Referencia.** Extraído de `CLAUDE.md` el 2026-07-09.

- **Pipeline** (`gulpfile.js`): SCSS `src/scss/**` → Dart Sass + autoprefixer + cssnano + sourcemaps →
  `build/css/app.css`. JS `src/js/**` → concat `bundle.js` + terser → `build/js/bundle.min.js`.
  Imágenes `src/img/**` → imagemin → `build/img/` y versión `.webp`.
- **Tareas** (⚠️ **no hay `gulp-cli` global** — invocar con `npx gulp <tarea>`; `gulp` pelado da
  `command not found`): `npx gulp css` (compila y queda en watch de `src/scss`), `npx gulp js`,
  `npx gulp imagenes`, `npx gulp webp`, `npx gulp build` (todo, sin watcher; para CI/prod),
  `npx gulp servidor` (solo PHP + BrowserSync, sin recompilar), **`npm run dev`** = `npx gulp`
  (**todo + servidor + watcher**, ver abajo). `npm run css` → `gulp css`.

## Entorno de desarrollo: `npm run dev` como iniciador único (2026-08-13)

`npm run dev` (= `npx gulp`) deja el entorno completo en pie con un solo comando: compila CSS/JS/imágenes,
levanta el servidor PHP, pone **BrowserSync** por delante y queda vigilando cambios.

| URL | Qué es |
|---|---|
| **http://localhost:3001** | La app, con recarga automática ← la que se usa |
| http://localhost:3002 | Panel de control de BrowserSync |
| http://localhost:3000 | El `php -S` crudo por debajo (sirve, pero sin recarga) |

Decisiones que hacen que esto funcione **en un proyecto PHP** (todas viven en `gulpfile.js`):

- **BrowserSync no ejecuta PHP**, solo sirve estáticos → trabaja en modo **proxy** delante de `php -S`, que
  el propio gulp levanta. Si el puerto ya está servido lo **reutiliza**, para no pelear con una consola
  abierta ni con Apache. Con `PHP_PORT=8080` (`$env:PHP_PORT=8080; npx gulp`) apunta al vhost de Apache,
  que es lo más parecido a producción (multiproceso y con `.htaccess` activo).
- ⚠️ **La CSP se retira en tránsito, solo en el proxy.** La app emite `script-src 'self'; connect-src 'self'`,
  que bloquearía el script inline y el WebSocket de BrowserSync: la recarga fallaría **en silencio** (solo
  visible en la consola del navegador). El `proxyRes` borra esa cabecera; **el CSP del código queda intacto**
  y producción no se entera. Corolario: **en dev no se está probando la CSP real** — si se toca la cabecera
  en `index.php`, verificarla contra `local3000` (:3000) o Apache, no contra :3001.
- **`ghostMode: false`** a propósito: por defecto BrowserSync espeja clics, scroll y formularios entre todos
  los navegadores conectados, y aquí se trabaja con **dos sesiones abiertas a la vez** (coordinador y
  contador) para probar el flujo de aprobación. Con el espejo activo esas pruebas serían inservibles.
- **`notify: false`** (2026-08-13): BrowserSync inyecta por defecto un cartel *"Connected to BrowserSync"*
  (`<div id="__bs_notify__">`) **dentro de la página**, al conectar y en cada recarga. Se superponía al
  formulario de `/login` y ensuciaba capturas y pruebas manuales. Apagarlo **no afecta la recarga**; la
  consola de gulp sigue informando. ⚠️ La config se lee **al arrancar**: hay que reiniciar `npm run dev`.
- **CSS se inyecta, PHP recarga:** `.scss` → `browserSync.stream()` (conserva scroll y el estado del
  formulario que estés probando); `.php` (vistas, controladores, modelos, `includes/`) y `.js` → recarga
  completa.
- **Si arranca y falla:** `Cannot find module 'browser-sync'` significa que `node_modules` está por detrás de
  `package.json` (BrowserSync se sumó en `e81409c`) → `npm install`. `bash: gulp: command not found` es otra
  cosa: no hay `gulp-cli` global, usar `npm run dev` / `npx gulp`. Si la app responde pero sale
  `No se encontró el archivo .env` o `Failed opening ... vendor/autoload.php`, el entorno está a medio
  instalar: `cp .env.example .env` y `composer install` (ninguno de los dos viaja en el repo).
- ⚠️ **Con una instancia ya corriendo, la segunda se va a otros puertos EN SILENCIO.** BrowserSync
  auto-incrementa si 3001/3002 están ocupados (→ 3003/3004) y solo lo dice en una línea de su salida; el
  `php -S` sí avisa (*"Ya hay un servidor escuchando… lo reutilizo"*). Si tras un `npm run dev` la recarga
  "no hace nada", es probable que estés mirando el 3001 de la sesión anterior. Comprobar con
  `Get-NetTCPConnection -State Listen -LocalPort 3000,3001,3002,3003,3004` y cerrar la vieja con
  `taskkill /PID <pid> /T /F`.
- **Verificar la inyección desde consola** (no desde el navegador): BrowserSync solo inyecta su snippet si la
  petición **declara que acepta HTML**. Un `curl` pelado (`Accept: */*`) NO trae el snippet y parece un fallo
  cuando no lo es. Usar `curl -s -H "Accept: text/html" http://localhost:3001/login | grep browser-sync`.
- **Cierre limpio en Windows:** con `shell: true`, `php.exe` cuelga de un `cmd.exe` intermedio; matar solo al
  hijo directo dejaba `php.exe` vivo aferrado al puerto y el siguiente `gulp` "reutilizaba" ese servidor
  fantasma. Se termina el **árbol completo** (`taskkill /T`) en `exit`/`SIGINT`/`SIGTERM`/`SIGBREAK`.
- `watchArchivos` recibe y llama a `cb()`: sin eso Gulp 4 daba *"Did you forget to signal async completion?"*
  al cerrar con Ctrl+C. El proceso sigue vivo porque son los watchers de chokidar los que sostienen el bucle
  de eventos.
- **Fixes ya aplicados:** eliminado `node-sass` muerto (el gulpfile usa Dart Sass `require('sass')`);
  `@use "sass:color";` añadido en `_variables.scss`/`_sidebar.scss`; `npm run css` exportada.
- ✅ **B7 cerrado (2026-07-16):** SCSS migrado de `@import` a `@use` (CSS compilado byte-idéntico) y
  `build/css/` depurado a lo que los layouts realmente cargan: `app.css`, `bootstrap.min.css`,
  `bootstrap-icons.min.css` (+ maps) y `fonts/` con la fuente de iconos (woff/woff2). Se eliminaron
  2.051 SVGs sueltos y las variantes bootstrap sin uso (~30 css/map).
