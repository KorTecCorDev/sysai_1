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
const path = require('path');
const fs = require('fs');

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

// Mailpit: servidor SMTP de desarrollo que ACEPTA TODO y NO REENVIA NADA a
// Internet, con bandeja web para leer los correos. Desarrollar contra el no
// requiere ninguna credencial: por eso el .env de desarrollo no guarda secretos
// (ver docs/plan-secretos-y-hardening.md).
const MAILPIT_SMTP = 1025;
const MAILPIT_UI   = 8025;

let procesoPhp = null;
let procesoMailpit = null;

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
// `async` sin callback: Gulp espera la promesa devuelta. Mezclar ambos (recibir
// `cb` y ademas ser async) hacia que la tarea se diera por terminada de
// inmediato, sin esperar a que el socket estuviera listo.
async function servidorPhp() {
    if (await puertoOcupado(PHP_PORT)) {
        console.log(`[gulp] Ya hay un servidor escuchando en ${PHP_HOST}:${PHP_PORT}; lo reutilizo.`);
        return;
    }
    console.log(`[gulp] Levantando php -S ${PHP_HOST}:${PHP_PORT}`);
    procesoPhp = spawn('php', ['-S', `${PHP_HOST}:${PHP_PORT}`], {
        cwd: __dirname,
        shell: true,
        stdio: ['ignore', 'ignore', 'inherit']   // los errores de PHP sí se ven
    });
    procesoPhp.on('error', (err) => console.error('[gulp] No se pudo iniciar PHP:', err.message));
    // Pequeña espera a que el socket acepte conexiones antes de proxear.
    await new Promise((r) => setTimeout(r, 1200));
}

// Arranca Mailpit si no hay uno escuchando ya. Degrada limpiamente: si el
// binario no esta instalado NO se aborta el arranque -- se avisa y el correo
// sigue funcionando en modo log (MAIL_TRANSPORT=log en el .env).
//   Instalacion:  winget install Axllent.Mailpit
// Localiza el binario. `mailpit` a secas solo funciona si la consola se abrio
// DESPUES de instalarlo (winget modifica el PATH y avisa de que hay que
// reiniciar la shell), asi que se prueba tambien la ruta donde winget lo deja.
// MAILPIT_BIN en el entorno tiene prioridad sobre todo lo demas.
function rutaMailpit() {
    if (process.env.MAILPIT_BIN) {
        return process.env.MAILPIT_BIN;
    }
    const base = path.join(process.env.LOCALAPPDATA || '', 'Microsoft', 'WinGet', 'Packages');
    try {
        const dir = fs.readdirSync(base).find((d) => d.toLowerCase().startsWith('axllent.mailpit'));
        if (dir) {
            const exe = path.join(base, dir, 'mailpit.exe');
            if (fs.existsSync(exe)) return exe;
        }
    } catch (e) { /* no es Windows, o no hay paquetes de winget */ }
    return 'mailpit';   // confiamos en el PATH
}

async function servidorMailpit() {
    if (await puertoOcupado(MAILPIT_SMTP)) {
        console.log(`[gulp] Mailpit ya escucha en ${PHP_HOST}:${MAILPIT_SMTP}; lo reutilizo.`);
        return;
    }
    // Ambos sockets atados a 127.0.0.1 A PROPOSITO: por defecto Mailpit escucha
    // en todas las interfaces y la bandeja -con los correos y sus tokens de
    // recuperacion- quedaria legible desde cualquier equipo de la red local.
    procesoMailpit = spawn(rutaMailpit(), [
        '--listen', `${PHP_HOST}:${MAILPIT_UI}`,
        '--smtp',   `${PHP_HOST}:${MAILPIT_SMTP}`,
    ], { cwd: __dirname, shell: true, stdio: ['ignore', 'ignore', 'ignore'] });

    procesoMailpit.on('error', () => { procesoMailpit = null; });

    // La tarea es `async`: Gulp espera la PROMESA que devuelve, no un callback
    // (mezclar ambos hacia que la tarea se diera por terminada al instante).
    await new Promise((r) => setTimeout(r, 1500));

    if (await puertoOcupado(MAILPIT_SMTP)) {
        console.log(`[gulp] Mailpit: SMTP en ${MAILPIT_SMTP} · bandeja en http://localhost:${MAILPIT_UI}`);
    } else {
        procesoMailpit = null;
        console.log('[gulp] Mailpit no disponible (winget install Axllent.Mailpit).');
        console.log('       Sin el, poner MAIL_TRANSPORT=log en el .env; ver docs/plan-secretos-y-hardening.md');
    }
}

// En Windows, `spawn(..., {shell:true})` cuelga php.exe de un cmd.exe intermedio:
// matar el hijo directo puede dejar php.exe VIVO y aferrado al puerto, y el
// siguiente `gulp` "reutilizaria" un servidor fantasma de la sesion anterior.
// `taskkill /T` se lleva el arbol completo.
function matarArbol(proceso) {
    if (!proceso || proceso.killed) {
        return;
    }
    const pid = proceso.pid;
    if (process.platform === 'win32') {
        try {
            require('child_process').execSync(`taskkill /pid ${pid} /T /F`, { stdio: 'ignore' });
        } catch (e) { /* ya habia muerto */ }
    } else {
        try { process.kill(-pid); } catch (e) { /* ya habia muerto */ }
    }
}

// Se cierran los DOS servicios que levanta gulp. Dejar Mailpit vivo seria peor
// que dejar PHP: mantiene abierta una bandeja con los correos de la sesion.
function cerrarServicios() {
    matarArbol(procesoPhp);     procesoPhp = null;
    matarArbol(procesoMailpit); procesoMailpit = null;
}
process.on('exit',    cerrarServicios);
process.on('SIGINT',  () => { cerrarServicios(); process.exit(0); });
process.on('SIGTERM', () => { cerrarServicios(); process.exit(0); });
process.on('SIGBREAK',() => { cerrarServicios(); process.exit(0); });   // Ctrl+Break en Windows

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
        // Sin el cartel "Connected to BrowserSync": es un <div id="__bs_notify__">
        // que el cliente inyecta EN LA PROPIA PÁGINA al conectar y en cada recarga.
        // Se superpone a la UI (en /login cae sobre el formulario) y ensucia las
        // capturas y las pruebas manuales. Apagarlo no afecta la recarga: solo
        // silencia el aviso visual. La consola de gulp sigue informando.
        notify: false,
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


// Recibe `cb` y lo llama en cuanto los vigilantes quedan registrados. Sin eso,
// Gulp 4 considera que la tarea nunca termino y al cerrar con Ctrl+C imprime
// "The following tasks did not complete / Did you forget to signal async
// completion?". El proceso NO se cierra al llamar cb(): los watchers de chokidar
// mantienen vivo el bucle de eventos, que es justo lo que queremos.
function watchArchivos(cb) {
    watch( paths.scss, css );                              // inyecta (sin recargar)
    watch( paths.js, series( javascript, recargar ) );
    watch( paths.imagenes, series( imagenes, recargar ) );
    watch( paths.imagenes, versionWebp );
    watch( paths.php, recargar );                          // vistas y controladores
    cb();
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

// `gulp servidor`: solo levanta los servicios, sin recompilar nada.
exports.servidor = series(servidorMailpit, servidorPhp, servidor);

// `gulp correo`: solo el catcher de correo (bandeja en http://localhost:8025).
exports.correo = servidorMailpit;

// Por defecto (`gulp` / `npm run dev`): compila todo, levanta el servidor PHP,
// pone BrowserSync por delante y queda escuchando cambios.
//   → http://localhost:3001   (la app, con recarga automática)
//   → http://localhost:3002   (panel de BrowserSync)
//   → http://localhost:8025   (bandeja de correo de desarrollo — Mailpit)
exports.default = series(
    parallel(css, javascript, imagenes, versionWebp),
    servidorMailpit,
    servidorPhp,
    servidor,
    watchArchivos
);