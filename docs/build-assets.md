# Build de assets (Gulp)

> **Referencia.** Extraído de `CLAUDE.md` el 2026-07-09.

- **Pipeline** (`gulpfile.js`): SCSS `src/scss/**` → Dart Sass + autoprefixer + cssnano + sourcemaps →
  `build/css/app.css`. JS `src/js/**` → concat `bundle.js` + terser → `build/js/bundle.min.js`.
  Imágenes `src/img/**` → imagemin → `build/img/` y versión `.webp`.
- **Tareas:** `gulp css` (compila y queda en watch de `src/scss`), `gulp js`, `gulp imagenes`, `gulp webp`,
  `gulp build` (todo, sin watcher; para CI/prod), `gulp` / `npm run dev` (todo + watcher). `npm run css` → `gulp css`.
- **Fixes ya aplicados:** eliminado `node-sass` muerto (el gulpfile usa Dart Sass `require('sass')`);
  `@use "sass:color";` añadido en `_variables.scss`/`_sidebar.scss`; `npm run css` exportada.
- **Pendiente menor:** los `@import` de Sass están *deprecated* (migrar a `@use/@forward`); `build/css/`
  contiene SVGs de bootstrap-icons commiteados (revisar/limpiar).
