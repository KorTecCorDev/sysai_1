#!/usr/bin/env node
/* ===========================================================================
 * Transporte del .env entre equipos, a través del propio repositorio.
 * ---------------------------------------------------------------------------
 *   npm run env:push   .env            →  secrets/.env.enc   (cifra; se commitea)
 *   npm run env:pull   secrets/.env.enc →  .env              (descifra; ignorado)
 *
 * POR QUÉ ASÍ
 * El .env no puede viajar en claro: el historial de git es permanente, de modo
 * que un secreto commiteado no se retira con otro commit — obliga a rotar la
 * credencial. Cifrado, en cambio, el repo lo transporta sin exponerlo, que es
 * la práctica habitual (git-crypt, SOPS, blackbox hacen esto mismo).
 *
 * Lo ÚNICO que no viaja por el repo es la passphrase. Tiene que ser así: si la
 * clave viajara junto al archivo cifrado, el cifrado no protegería nada.
 *
 * POR QUÉ OPENSSL Y NO age/sops/git-crypt
 * OpenSSL viene incluido en Git for Windows, así que está garantizado en
 * cualquier equipo donde puedas clonar el repo. Cero instalaciones al dar de
 * alta una máquina nueva, que era el objetivo.
 *
 * POR QUÉ NODE Y NO UN .sh o UN .bat
 * Node ya es requisito del proyecto (gulp), y los scripts de npm en Windows se
 * ejecutan con cmd.exe, donde un .sh no corre. Esto funciona igual en Windows,
 * Linux y macOS.
 *
 * La passphrase NUNCA pasa por argumentos ni por variables de este proceso: se
 * delega el prompt al propio OpenSSL (stdio heredado). Los argumentos de un
 * proceso son legibles por otros procesos de la máquina.
 * ========================================================================= */

const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const RAIZ    = path.resolve(__dirname, '..');
const CLARO   = path.join(RAIZ, '.env');
const CIFRADO = path.join(RAIZ, 'secrets', '.env.enc');

// AES-256-CBC con PBKDF2. 600 000 iteraciones es la recomendación actual de
// OWASP para PBKDF2-SHA256; sobre un archivo de 1 KB el coste es imperceptible
// y encarece muchísimo un ataque por fuerza bruta contra la passphrase, que es
// lo único que protege este archivo una vez está en el historial de git.
// -a (armor base64) para que git lo trate como texto y no como binario opaco.
// ⚠️ Estos parámetros deben COINCIDIR al cifrar y descifrar.
const ALGORITMO  = 'aes-256-cbc';
const ITERACIONES = 600000;

function argumentosBase() {
    return ['enc', `-${ALGORITMO}`, '-pbkdf2', '-iter', String(ITERACIONES), '-a', '-salt'];
}

// Permite automatizar (CI, un equipo nuevo desatendido) sin teclear nada. Si no
// está definida, OpenSSL pregunta por consola, que es el uso normal.
function origenPassphrase() {
    return process.env.SYSAI_ENV_PASSPHRASE ? ['-pass', 'env:SYSAI_ENV_PASSPHRASE'] : [];
}

/**
 * Localiza el binario de OpenSSL.
 *
 * `openssl` a secas NO basta en Windows: los scripts de npm corren con cmd.exe,
 * y Git for Windows solo añade sus utilidades Unix al PATH del sistema si al
 * instalarlo se eligió esa opción — que no es la predeterminada. En un equipo
 * donde no se eligió, `openssl` existe en disco pero es invisible desde cmd, y
 * la promesa de "cero instalaciones al clonar" se caería. Por eso se prueban
 * también las rutas conocidas de Git y de XAMPP.
 *
 * OPENSSL_BIN en el entorno tiene prioridad sobre todo lo demás.
 */
function localizarOpenssl() {
    const candidatos = [];
    if (process.env.OPENSSL_BIN) {
        candidatos.push(process.env.OPENSSL_BIN);
    }
    candidatos.push('openssl');                       // PATH (Linux, macOS, y Windows bien configurado)

    if (process.platform === 'win32') {
        const programas = [process.env.ProgramFiles, process.env['ProgramFiles(x86)'], 'C:\\Program Files']
            .filter(Boolean);
        for (const base of programas) {
            candidatos.push(path.join(base, 'Git', 'mingw64', 'bin', 'openssl.exe'));
            candidatos.push(path.join(base, 'Git', 'usr', 'bin', 'openssl.exe'));
        }
        candidatos.push('C:\\xampp\\apache\\bin\\openssl.exe');
    }

    for (const candidato of candidatos) {
        const r = spawnSync(candidato, ['version'], { stdio: 'ignore' });
        if (!r.error && r.status === 0) {
            return candidato;
        }
    }
    return null;
}

function ejecutarOpenssl(args) {
    const binario = localizarOpenssl();
    if (binario === null) {
        console.error('\n[env] No se encontró OpenSSL en este equipo.');
        console.error('      Viene incluido con Git for Windows; se buscó en el PATH y en las rutas');
        console.error('      habituales de Git y XAMPP. Si lo tienes en otro sitio:');
        console.error('        set OPENSSL_BIN=C:\\ruta\\a\\openssl.exe   (o $env:OPENSSL_BIN en PowerShell)\n');
        process.exit(1);
    }
    const r = spawnSync(binario, args, { stdio: 'inherit' });
    return r.status === 0;
}

function cifrar() {
    if (!fs.existsSync(CLARO)) {
        console.error(`[env] No existe ${CLARO}. Nada que cifrar.`);
        process.exit(1);
    }
    fs.mkdirSync(path.dirname(CIFRADO), { recursive: true });

    // Se cifra a un temporal y solo se reemplaza si OpenSSL terminó bien: un
    // fallo a mitad de escritura dejaría secrets/.env.enc corrupto, y el
    // siguiente equipo que hiciera pull se quedaría sin configuración.
    const temporal = CIFRADO + '.tmp';
    const ok = ejecutarOpenssl([...argumentosBase(), ...origenPassphrase(), '-in', CLARO, '-out', temporal]);
    if (!ok) {
        fs.rmSync(temporal, { force: true });
        console.error('\n[env] El cifrado falló: secrets/.env.enc queda como estaba.\n');
        process.exit(1);
    }
    fs.renameSync(temporal, CIFRADO);

    // El .env local acaba de PRODUCIR ese cifrado, asi que esta al dia por
    // definicion. Sin esto queda con fecha anterior y comprobarEnvDesactualizado()
    // -que compara fechas- lo tomaria por atrasado: la app respondia 500
    // "Configuracion desactualizada" justo despues de un push correcto,
    // pidiendo descifrar lo que uno mismo acaba de cifrar.
    const ahora = new Date();
    fs.utimesSync(CLARO, ahora, ahora);

    console.log(`\n[env] Cifrado  →  secrets/.env.enc`);
    console.log('[env] Ahora publícalo para que llegue a tus otros equipos:');
    console.log('        git add secrets/.env.enc && git commit -m "chore(env): actualizar configuracion cifrada" && git push\n');
}

function descifrar() {
    if (!fs.existsSync(CIFRADO)) {
        console.error('[env] No existe secrets/.env.enc. ¿Hiciste `git pull`?');
        process.exit(1);
    }

    // Se descifra a un temporal por la misma razón, y además porque una
    // passphrase equivocada NO puede destruir el .env que ya funcionaba.
    const temporal = CLARO + '.tmp';
    const ok = ejecutarOpenssl([...argumentosBase(), '-d', ...origenPassphrase(), '-in', CIFRADO, '-out', temporal]);
    if (!ok) {
        fs.rmSync(temporal, { force: true });
        console.error('\n[env] No se pudo descifrar. Casi siempre es la passphrase equivocada.');
        console.error('      Tu .env actual NO se ha tocado.\n');
        process.exit(1);
    }

    // Comprobación de cordura: una passphrase errónea normalmente hace fallar a
    // OpenSSL, pero no está garantizado. Si lo que sale no parece un .env, se
    // descarta antes de pisar el bueno.
    const contenido = fs.readFileSync(temporal, 'utf8');
    if (!/^\s*[A-Z_]+=/m.test(contenido)) {
        fs.rmSync(temporal, { force: true });
        console.error('\n[env] Lo descifrado no parece un archivo .env. Se descarta por seguridad.\n');
        process.exit(1);
    }

    fs.renameSync(temporal, CLARO);
    console.log('\n[env] Descifrado  →  .env   (sigue ignorado por git)');
    console.log('[env] Listo: `npm run dev`\n');
}

const accion = process.argv[2];
if (accion === 'push' || accion === 'cifrar')       { cifrar(); }
else if (accion === 'pull' || accion === 'descifrar') { descifrar(); }
else {
    console.error('Uso:  node scripts/env.js <push|pull>');
    console.error('      npm run env:push    cifra .env      → secrets/.env.enc  (para commitear)');
    console.error('      npm run env:pull    descifra .env.enc → .env            (tras clonar o hacer pull)');
    process.exit(2);
}
