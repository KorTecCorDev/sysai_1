const { src, dest, watch , parallel, series } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('autoprefixer');
const postcss    = require('gulp-postcss')
const sourcemaps = require('gulp-sourcemaps')
const cssnano = require('cssnano');
const concat = require('gulp-concat');
const terser = require('gulp-terser-js');
const rename = require('gulp-rename');
const imagemin = require('gulp-imagemin');
const notify = require('gulp-notify');
const cache = require('gulp-cache');
const webp = require('gulp-webp');

const paths = {
    scss: 'src/scss/**/*.scss',
    js: 'src/js/**/*.js',
    imagenes: 'src/img/**/*'
}

// css es una función que se puede llamar automaticamente
function css() {
    return src(paths.scss)
        .pipe(sourcemaps.init())
        .pipe(sass())
        .pipe(postcss([autoprefixer(), cssnano()]))
        // .pipe(postcss([autoprefixer()]))
        .pipe(sourcemaps.write('.'))
        .pipe( dest('./build/css') );
}


function javascript() {
    return src(paths.js)
      .pipe(sourcemaps.init())
      .pipe(concat('bundle.js')) // final output file name
      .pipe(terser())
      .pipe(sourcemaps.write('.'))
      .pipe(rename({ suffix: '.min' }))
      .pipe(dest('./build/js'))
}

function imagenes() {
    return src(paths.imagenes)
        .pipe(cache(imagemin({ optimizationLevel: 3})))
        .pipe(dest('./build/img'))
        .pipe(notify({ message: 'Imagen Completada'}));
}

function versionWebp() {
    return src(paths.imagenes)
        .pipe( webp() )
        .pipe(dest('./build/img'))
        .pipe(notify({ message: 'Imagen Completada'}));
}


function watchArchivos() {
    watch( paths.scss, css );
    watch( paths.js, javascript );
    watch( paths.imagenes, imagenes );
    watch( paths.imagenes, versionWebp );
}

// Solo estilos: vigila src/scss y recompila el CSS al detectar cambios.
function watchEstilos() {
    watch( paths.scss, css );
}

// `gulp css` / `npm run css`: compila los estilos UNA vez y queda como OYENTE,
// recompilando automáticamente ante cambios en src/scss.
exports.css = series(css, watchEstilos);

// Tareas individuales de un solo paso (one-shot)
exports.js = javascript;
exports.imagenes = imagenes;
exports.webp = versionWebp;

// Build completo SIN watcher (útil para producción / CI)
exports.build = parallel(css, javascript, imagenes, versionWebp);

// Por defecto (`gulp` / `npm run dev`): compila todo y queda escuchando cambios.
exports.default = parallel(css, javascript,  imagenes, versionWebp, watchArchivos );