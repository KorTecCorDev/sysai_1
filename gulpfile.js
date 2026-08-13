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
const browserSync = require('browser-sync').create();
const { spawn } = require('child_process');
const net = require('net');

const paths = {
    scss: 'src/scss/**/*.scss',
    js: 'src/js/**/*.js',
    imagenes: 'src/img/**/*',
    // El backend es PHP: al tocar una vista o un controlador hay que RECARGAR,
    // no inyectar. Se excluye lo que no es fuente propia.
    php: ['*.php', 'controllers/**/*.php', 'models/**/*.php', 'views/**/*.php', 'includes/**/*.php',
          '!node_modules/**', '!vendor/**']
}

// ---------------------------------------------------------------------------
// Servidor de desarrollo
// ---------------------------------------------------------------------------
// BrowserSync NO ejecuta PHP: solo sabe servir estáticos. Por eso trabaja en
// modo PROXY delante de un servidor PHP real. `gulp` levanta ese servidor
// (php -S) y pone BrowserSync por delante, de modo que un solo comando deja
// todo listo.
//
// Para trabajar contra el vhost de Apache (multiproceso y con .htaccess activo,
// que es lo más parecido a producción):
//     PHP_PORT=8080 npx gulp        (PowerShell: $env:PHP_PORT=8080; npx gulp)
const PHP_HOST = '127.0.0.1';
const PHP_PORT = process.env.PHP_PORT || 3000;
const BS_PORT  = 3001;   // no puede coincidir con PHP_PORT ni con el 8080 de Apache

let procesoPhp = null;

// ¿Hay algo escuchando ya en ese puerto? (Apache, u otra consola con php -S)
function puertoOcupado(puerto) {
    return new Promise((resolve) => {
        const socket = net.createConnection({ host: PHP_HOST, port: puerto });
        socket.setTimeout(800);
        socket.on('connect', () => { socket.destroy(); resolve(true); });
        socket.on('error',   () => { resolve(false); });
        socket.on('timeout', () => { socket.destroy(); resolve(false); });
    });
}

// Levanta `php -S` salvo que el puerto ya esté servido (así `gulp` no pelea con
// un servidor que ya tengas abierto, ni con Apache si apuntas al 8080).
async function servidorPhp(cb) {
    if (await puertoOcupado(PHP_PORT)) {
        console.log(`[gulp] Ya hay un servidor escuchando en ${PHP_HOST}:${PHP_PORT}; lo reutilizo.`);
        return cb();
    }
    console.log(`[gulp] Levantando php -S ${PHP_HOST}:${PHP_PORT}`);
    procesoPhp = spawn('php', ['-S', `${PHP_HOST}:${PHP_PORT}`], {
        cwd: __dirname,
        shell: true,
        stdio: ['ignore', 'ignore', 'inherit']   // los errores de PHP sí se ven
    });
    procesoPhp.on('error', (err) => console.error('[gulp] No se pudo iniciar PHP:', err.message));
    // Pequeña espera a que el socket acepte conexiones antes de proxear.
    setTimeout(cb, 1200);
}

function cerrarPhp() {
    if (procesoPhp && !procesoPhp.killed) {
        procesoPhp.kill();
        procesoPhp = null;
    }
}
process.on('exit',   cerrarPhp);
process.on('SIGINT', () => { cerrarPhp(); process.exit(0); });

function servidor(cb) {
    browserSync.init({
        proxy: {
            target: `http://${PHP_HOST}:${PHP_PORT}`,
            // La app emite una CSP estricta (script-src 'self'; connect-src 'self').
            // BrowserSync inyecta un script INLINE y abre un WebSocket a otro
            // puerto: con esa CSP el navegador bloquea ambos y la recarga falla
            // EN SILENCIO (solo se ve en la consola del navegador). Se retira la
            // cabecera aquí, en el proxy de desarrollo: el CSP del código queda
            // intacto y producción no se entera de esto.
            proxyRes: [
                function (proxyRes) {
                    delete proxyRes.headers['content-security-policy'];
                    delete proxyRes.headers['Content-Security-Policy'];
                }
            ]
        },
        port: BS_PORT,
        ui: { port: BS_PORT + 1 },
        // ghostMode APAGADO a propósito: por defecto BrowserSync espeja clics,
        // scroll y formularios entre TODOS los navegadores conectados. En este
        // proyecto se trabaja con dos sesiones abiertas a la vez (coordinador y
        // contador) para probar el flujo de aprobación: con el espejo activo, un
        // clic en una ventana movería la otra y las pruebas serían inservibles.
        ghostMode: false,
        notify: true,
        open: false          // no secuestra el navegador en cada arranque
    }, cb);
}

// Recarga completa (cambios de PHP, JS o imágenes).
function recargar(cb) {
    browserSync.reload();
    cb();
}

// css es una función que se puede llamar automaticamente
function css() {
    return src(paths.scss)
        .pipe(sourcemaps.init())
        .pipe(sass())
        .pipe(postcss([autoprefixer(), cssnano()]))
        // .pipe(postcss([autoprefixer()]))
        .pipe(sourcemaps.write('.'))
        .pipe( dest('./build/css') )
        // Inyecta el CSS sin recargar: se conservan el scroll y el estado del
        // formulario que estés probando. Si BrowserSync no está activo (p. ej.
        // `gulp build`), stream() es inocuo.
        .pipe( browserSync.stream() );
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
    watch( paths.scss, css );                              // inyecta (sin recargar)
    watch( paths.js, series( javascript, recargar ) );
    watch( paths.imagenes, series( imagenes, recargar ) );
    watch( paths.imagenes, versionWebp );
    watch( paths.php, recargar );                          // vistas y controladores
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

// `gulp servidor`: solo levanta PHP + BrowserSync, sin recompilar nada.
exports.servidor = series(servidorPhp, servidor);

// Por defecto (`gulp` / `npm run dev`): compila todo, levanta el servidor PHP,
// pone BrowserSync por delante y queda escuchando cambios.
//   → http://localhost:3001   (la app, con recarga automática)
//   → http://localhost:3002   (panel de BrowserSync)
exports.default = series(
    parallel(css, javascript, imagenes, versionWebp),
    servidorPhp,
    servidor,
    watchArchivos
);