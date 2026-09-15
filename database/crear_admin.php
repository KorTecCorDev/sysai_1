<?php
/**
 * Alta del ADMINISTRADOR inicial de una instalación nueva.
 *
 * USO
 *   php database/crear_admin.php --email <correo> --nombres "<nombres>" --apellido-paterno "<apellido>" --dni <numero>
 *        [--apellido-materno "<apellido>"] [--telefono <numero>] [--probar-correo] [--forzar]
 *
 *   php database/crear_admin.php --email <correo> --probar-correo
 *        Reenvía un código de activación a un usuario que YA existe.
 *
 * POR QUÉ EXISTE (2026-09-14)
 *   seed.sql sembraba admin@arcoiris.pe con una contraseña escrita en el propio repositorio, y ese
 *   buzón no existe: el admin no podía usar la recuperación y la clave conocida quedaba viva en
 *   producción. Ahora el admin nace como cualquier otro usuario: con una contraseña aleatoria que
 *   nadie conoce, y la activa por correo (/chgpsswd → /token_verify → /updtepsswd).
 *
 * --probar-correo
 *   Genera un código de activación REAL (vigencia Login::RECUP_TOKEN_TTL) y lo envía por el mismo
 *   camino que la web (enviarTokenRecuperacion). Es a la vez la prueba del SMTP desde el servidor:
 *   si falla aquí, nadie podrá activar su cuenta, y hay que resolverlo antes de abrir el sitio.
 *
 * --forzar
 *   Permite crear otro administrador cuando ya existe alguno.
 */

// Solo por línea de comandos: por HTTP, $argv se puebla desde la query string y esto sería un
// alta de administradores accesible por URL. Mismo criterio que migrate.php y smtp_test.php.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/config/database.php';
require __DIR__ . '/../includes/funciones.php';

use Model\ActiveRecord;
use Model\Login;
use Model\Persona;
use Model\Usuario;

$db = conectarDB();
ActiveRecord::setDB($db);

function ca_opcion(array $argv, string $nombre): string
{
    $pos = array_search("--{$nombre}", $argv, true);
    return $pos === false ? '' : trim((string) ($argv[$pos + 1] ?? ''));
}

function ca_abortar(string $mensaje, int $codigo = 1): never
{
    fwrite(STDERR, "\n  [X] {$mensaje}\n\n");
    exit($codigo);
}

/**
 * Genera y envía un código de activación para una cuenta existente. Devuelve true solo si el
 * correo salió de verdad: en modo log (sin transporte) no se envía nada y eso aquí es un fallo.
 */
function ca_enviarCodigo(string $email): bool
{
    $filas = Login::consultarPreparado(
        'SELECT id, email, datos FROM login_session_vista WHERE email = ? LIMIT 1',
        's',
        [$email]
    );
    $cuenta = $filas[0] ?? null;
    if (!$cuenta) {
        fwrite(STDERR, "\n  [X] No hay ninguna cuenta con el correo {$email}.\n\n");
        return false;
    }

    // Sin transporte, enviarTokenRecuperacion() "tiene éxito" en desarrollo sin mandar nada.
    // Se comprueba antes de guardar el código para no dejar uno vivo que nadie recibió.
    try {
        $hayTransporte = construirMailer() !== null;
    } catch (\Throwable) {
        $hayTransporte = true; // configuración inválida: que enviarTokenRecuperacion lo registre
    }
    if (!$hayTransporte) {
        fwrite(STDERR, "\n  [X] No hay transporte de correo configurado (claves MAIL_* del .env): no se envió nada.\n\n");
        return false;
    }

    $codigo = generarCodigoAleatorioSimple(10);
    $login = new Login(['id' => $cuenta->id, 'email' => $cuenta->email]);
    $login->reset_token = hash('sha256', $codigo);
    if (!$login->guardarToken()) {
        fwrite(STDERR, "\n  [X] No se pudo guardar el código en la base de datos.\n\n");
        return false;
    }

    if (!enviarTokenRecuperacion($cuenta->email, (string) $cuenta->datos, $codigo)) {
        fwrite(STDERR, "\n  [X] El correo NO salió: nadie podrá activar su cuenta hasta resolverlo.\n"
            . "      Diagnóstico: php database/smtp_test.php {$email}\n"
            . "      Bitácora:    MAIL_LOG_PATH del .env (o includes/logs/mail.log)\n\n");
        return false;
    }

    $minutos = intdiv(Login::RECUP_TOKEN_TTL, 60);
    echo "\n  [OK] Código de activación enviado a {$email} (vigente {$minutos} min).\n";
    echo "       Ingrésalo en /token_verify y define la contraseña.\n\n";
    return true;
}

// ---------------------------------------------------------------------------
// Argumentos
// ---------------------------------------------------------------------------
$email  = ca_opcion($argv, 'email');
$probar = in_array('--probar-correo', $argv, true);
$forzar = in_array('--forzar', $argv, true);

if ($email === '') {
    fwrite(STDERR, "Uso: php database/crear_admin.php --email <correo> --nombres \"...\" --apellido-paterno \"...\" --dni <numero>\n"
        . "          [--apellido-materno \"...\"] [--telefono <numero>] [--probar-correo] [--forzar]\n"
        . "     php database/crear_admin.php --email <correo> --probar-correo   (reenvía el código a una cuenta existente)\n");
    exit(2);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ca_abortar("'{$email}' no es un correo válido.", 2);
}

// ---------------------------------------------------------------------------
// Cuenta existente: solo se permite reenviar el código
// ---------------------------------------------------------------------------
$existente = Usuario::consultarPreparado('SELECT * FROM usuario WHERE email = ? LIMIT 1', 's', [$email]);
$existente = $existente[0] ?? null;
if ($existente) {
    if (!$probar) {
        ca_abortar("Ya existe un usuario con el correo {$email} (id {$existente->id}). Para enviarle un código: --probar-correo");
    }
    exit(ca_enviarCodigo($email) ? 0 : 1);
}

// ---------------------------------------------------------------------------
// Alta
// ---------------------------------------------------------------------------
$nombres = ca_opcion($argv, 'nombres');
$apPat   = ca_opcion($argv, 'apellido-paterno');
$apMat   = ca_opcion($argv, 'apellido-materno');
$dni     = ca_opcion($argv, 'dni');
$tel     = ca_opcion($argv, 'telefono');

$faltan = [];
if ($nombres === '') $faltan[] = '--nombres';
if ($apPat === '')   $faltan[] = '--apellido-paterno';
// persona.nro_documento es int(11): más de 9 dígitos desbordaría (error en modo estricto).
if (!ctype_digit($dni) || strlen($dni) > 9) $faltan[] = '--dni (solo dígitos, hasta 9)';
if ($faltan) {
    ca_abortar('Faltan o no son válidos: ' . implode(', ', $faltan), 2);
}

$admins = (int) $db->query('SELECT COUNT(*) FROM usuario WHERE cargo_id = 1')->fetch_row()[0];
if ($admins > 0 && !$forzar) {
    ca_abortar("Ya hay {$admins} administrador(es). Si de verdad hace falta otro, repite con --forzar.");
}

$persona = new Persona([
    'nro_documento'    => $dni,
    'apellido_paterno' => $apPat,
    'apellido_materno' => $apMat,
    'nombres'          => $nombres,
    'telefono'         => $tel,
]);
if (Persona::existeDato($persona, ['nro_documento'])) {
    ca_abortar("Ya existe una persona con el documento {$dni}.");
}

// Usuario genera su contraseña provisional aleatoria: se hashea y no se muestra nunca.
$usuario = new Usuario(['email' => $email, 'cargo_id' => 1]);
$errores = $usuario->validar();
if ($errores) {
    ca_abortar(implode(' / ', $errores));
}
$usuario->password = password_hash($usuario->password, PASSWORD_DEFAULT);

// Persona y usuario van juntos o no van: nada de personas huérfanas si falla el segundo INSERT.
$db->begin_transaction();
if (!$persona->guardarsinRedireccion()) {
    $db->rollback();
    ca_abortar('No se pudo registrar la persona.');
}
$usuario->persona_id = $persona->devolverIdLastInsercion();
if (!$usuario->guardarsinRedireccion()) {
    $db->rollback();
    ca_abortar('No se pudo registrar el usuario.');
}
$usuarioId = $usuario->devolverIdLastInsercion();
$db->commit();

echo "\n  [OK] Administrador creado: {$email} (usuario id {$usuarioId}).\n";
echo "       Su contraseña es aleatoria y nadie la conoce: se activa por correo.\n";

if ($probar) {
    exit(ca_enviarCodigo($email) ? 0 : 1);
}
echo "\n  Siguiente paso: /chgpsswd con ese correo, o repetir con --probar-correo para enviar el código desde aquí.\n\n";
exit(0);
