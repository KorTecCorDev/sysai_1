# Build de assets (Gulp)

> **Referencia.** Extraído de `CLAUDE.md` el 2026-07-09.

- **Pipeline** (`gulpfile.js`): SCSS `src/scss/**` → Dart Sass + autoprefixer + cssnano + sourcemaps →
  `build/css/app.css`. JS `src/js/**` → concat `bundle.js` + terser → `build/js/bundle.min.js`.
  Imágenes `src/img/**` → imagemin → `build/img/` y versión `.webp`.
- **Tareas:** `gulp css` (compila y queda en watch de `src/scss`), `gulp js`, `gulp imagenes`, `gulp webp`,
  `gulp build` (todo, sin watcher; para CI/prod), `gulp` / `npm run dev` (todo + watcher). `npm run css` → `gulp css`.
- **Fixes ya aplicados:** eliminado `node-sass` muerto (el gulpfile usa Dart Sass `require('sass')`);
  `@use "sass:color";` añadido en `_variables.scss`/`_sidebar.scss`; `npm run css` exportada.
- ✅ **B7 cerrado (2026-07-16):** SCSS migrado de `@import` a `@use` (CSS compilado byte-idéntico) y
  `build/css/` depurado a lo que los layouts realmente cargan: `app.css`, `bootstrap.min.css`,
  `bootstrap-icons.min.css` (+ maps) y `fonts/` con la fuente de iconos (woff/woff2). Se eliminaron
  2.051 SVGs sueltos y las variantes bootstrap sin uso (~30 css/map).
