<?php
/**
 * Alta de los participantes de la capacitacion.
 *
 * USO
 *   php database/preparar_capacitacion.php                 # da de alta lo que falte
 *   php database/preparar_capacitacion.php --simular       # muestra que haria, sin tocar nada
 *   php database/preparar_capacitacion.php --clave <email> # red de seguridad (ver abajo)
 *
 * DE DONDE SALEN LOS DATOS
 *   De database/participantes.csv, que NO esta en el repositorio (contiene nombres y
 *   correos reales). Si no existe, este script escribe una plantilla y explica que hacer.
 *
 * QUE CREA POR CADA PARTICIPANTE
 *   - persona + usuario con su cargo (coordinador o contador).
 *   - Al coordinador, ademas: un PROGRAMA propio, el vinculo coordinador-programa, y un
 *     SOBRE sobre la fuente de la capacitacion.
 *
 *   El sobre no es un extra: sin el, la puerta de sobres deja al coordinador sin poder
 *   registrar NADA presupuestal -ni rubros, ni POA Presupuestal, ni rendiciones- y la
 *   capacitacion se detendria en la primera pantalla util. Ver CLAUDE.md, "SIN SOBRES NO
 *   HAY PRESUPUESTO".
 *
 * CONTRASENAS: NO SE FIJAN AQUI, A PROPOSITO
 *   El alta genera una contrasena provisional aleatoria que nadie conoce -ni el
 *   administrador-, igual que el alta normal de la aplicacion. Cada participante activa
 *   la suya desde /chgpsswd con el codigo que le llega por correo. Sembrar contrasenas
 *   conocidas ensenaria a los participantes justo lo contrario de lo que el sistema hace.
 *
 *   RED DE SEGURIDAD: si a alguien no le llega el correo el dia de la capacitacion,
 *   `--clave <email>` le fija una contrasena provisional y la imprime en pantalla, para
 *   que pueda entrar y seguir la sesion. Es una salida de emergencia, no el camino normal.
 *
 * ES RE-EJECUTABLE
 *   Salta a quien ya existe (por email). Si anadiste a alguien a la lista a ultima hora,
 *   vuelve a correrlo: solo dara de alta lo que falte.
 */

// Solo por linea de comandos. Con register_argc_argv activo -lo esta- $argv se puebla
// desde la QUERY STRING al servirse por HTTP: sin esta guarda, este script seria un alta
// masiva de usuarios accesible por URL. Mismo criterio que migrate.php y smtp_test.php.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/config/database.php';
require __DIR__ . '/../includes/funciones.php';

use Model\ActiveRecord;
use Model\Persona;
use Model\Usuario;
use Model\Programa;
use Model\FuenteFinanciamiento;
use Model\DetalleFinanciamiento;
use Model\CoordinadorPrograma;

$db = conectarDB();
ActiveRecord::setDB($db);

// ---------------------------------------------------------------------------
// Parametros de la capacitacion
// ---------------------------------------------------------------------------
// Una sola fuente compartida, y un sobre por coordinador. Cifras redondas y
// holgadas: el objetivo es que nadie choque con un tope mientras practica.
const FUENTE_NOMBRE   = 'FONDO DE CAPACITACION';
const FUENTE_MONTO    = 1000000.00;   // S/ 1 000 000
const SOBRE_POR_COORD =  100000.00;   // S/   100 000 para cada coordinador
const CSV             = __DIR__ . '/participantes.csv';

$simular = in_array('--simular', $argv, true);

// ---------------------------------------------------------------------------
// Red de seguridad: --clave <email>
// ---------------------------------------------------------------------------
$posClave = array_search('--clave', $argv, true);
if ($posClave !== false) {
    $email = $argv[$posClave + 1] ?? '';
    if ($email === '') {
        fwrite(STDERR, "Uso: php database/preparar_capacitacion.php --clave <email>\n");
        exit(2);
    }
    $usuario = Usuario::findxatributo('email', $email);
    $usuario = is_array($usuario) ? ($usuario[0] ?? null) : $usuario;
    if (!$usuario) {
        fwrite(STDERR, "No hay ningun usuario con el correo {$email}.\n");
        exit(1);
    }
    $provisional = 'Arca' . random_int(1000, 9999) . '*';
    $stmt = $db->prepare('UPDATE usuario SET password = ?, reset_token = NULL WHERE id = ?');
    $hash = password_hash($provisional, PASSWORD_DEFAULT);
    $stmt->bind_param('si', $hash, $usuario->id);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        fwrite(STDERR, "No se pudo actualizar la contrasena.\n");
        exit(1);
    }
    echo "\n  Contrasena provisional para {$email}:  {$provisional}\n";
    echo "  Entregasela EN MANO y pidale cambiarla desde su perfil al terminar.\n\n";
    exit(0);
}

// ---------------------------------------------------------------------------
// La lista de participantes
// ---------------------------------------------------------------------------
if (!is_file(CSV)) {
    file_put_contents(CSV, implode("\n", [
        '# Participantes de la capacitacion. Una linea por persona.',
        '# Las lineas que empiezan por # se ignoran.',
        '#',
        '# rol: coordinador | contador',
        '#   coordinador -> recibe ademas un programa propio con su sobre',
        '#   contador    -> revisa y aprueba lo de todos; con uno o dos basta',
        '#',
        '# programa: nombre del programa del coordinador. Si se deja vacio se usa',
        '#           "PROGRAMA DE <NOMBRES>". Los contadores lo dejan vacio.',
        '#',
        '# Los dos ejemplos van comentados A PROPOSITO: si se dejaran activos, ejecutar',
        '# el script sin haber editado nada daria de alta dos usuarios ficticios que',
        '# luego habria que ir a borrar a mano. Escribe tus lineas debajo de la cabecera.',
        '#',
        '# Maria,Quispe,Huaman,40111222,943111222,maria.ejemplo@gmail.com,coordinador,PROGRAMA COMUNIDAD',
        '# Jose,Ramirez,Vega,40333444,943333444,jose.ejemplo@gmail.com,contador,',
        '',
        'nombres,apellido_paterno,apellido_materno,nro_documento,telefono,email,rol,programa',
    ]) . "\n");
    echo "\n  Se creo la plantilla:  database/participantes.csv\n";
    echo "  Anade una linea por participante debajo de la cabecera y vuelve a ejecutar\n";
    echo "  este script. El archivo NO se sube al repositorio.\n\n";
    exit(0);
}

$filas = [];
$numero = 0;
foreach (file(CSV, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
    $numero++;
    $t = trim($linea);
    if ($t === '' || $t[0] === '#') {
        continue;
    }
    $campos = str_getcsv($t);
    if (strtolower(trim($campos[0])) === 'nombres') {
        continue;                                   // la cabecera
    }
    if (count($campos) < 7) {
        fwrite(STDERR, "Linea {$numero}: se esperaban al menos 7 columnas, hay " . count($campos) . ".\n");
        exit(1);
    }
    [$nombres, $apPaterno, $apMaterno, $documento, $telefono, $email, $rol] = array_map('trim', $campos);
    $programa = trim($campos[7] ?? '');

    $rol = strtolower($rol);
    if (!in_array($rol, ['coordinador', 'contador'], true)) {
        fwrite(STDERR, "Linea {$numero}: rol '{$rol}' no valido (coordinador | contador).\n");
        exit(1);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fwrite(STDERR, "Linea {$numero}: '{$email}' no es un correo valido.\n");
        exit(1);
    }
    $filas[] = compact('nombres', 'apPaterno', 'apMaterno', 'documento', 'telefono', 'email', 'rol', 'programa');
}

if (!$filas) {
    echo "\n  database/participantes.csv no tiene participantes todavia.\n\n";
    exit(0);
}

// Correos repetidos: el UNIQUE de la migr. 032 lo atraparia a mitad del alta, dejando el
// trabajo hecho a medias. Mejor detectarlo antes de tocar la base.
$correos = array_column($filas, 'email');
$repetidos = array_diff_assoc($correos, array_unique($correos));
if ($repetidos) {
    fwrite(STDERR, "Correos repetidos en el CSV: " . implode(', ', array_unique($repetidos)) . "\n");
    exit(1);
}

printf("\n  %d participante(s) en la lista%s\n\n", count($filas), $simular ? '   [SIMULACION: no se escribe nada]' : '');

// ---------------------------------------------------------------------------
// Fuente de financiamiento de la capacitacion
// ---------------------------------------------------------------------------
$fuente = null;
foreach (FuenteFinanciamiento::all() as $f) {
    if (strcasecmp($f->nombre, FUENTE_NOMBRE) === 0) {
        $fuente = $f;
        break;
    }
}
if (!$fuente) {
    if ($simular) {
        echo "  [+] crearia la fuente '" . FUENTE_NOMBRE . "' con S/ " . number_format(FUENTE_MONTO, 2) . "\n";
    } else {
        $fuente = new FuenteFinanciamiento([
            'codigo'      => FuenteFinanciamiento::siguienteCodigo(),
            'nombre'      => FUENTE_NOMBRE,
            'descripcion' => 'Fondo ficticio para las practicas de la capacitacion.',
            'presupuesto' => FUENTE_MONTO,
        ]);
        if (!$fuente->guardarsinRedireccion()) {
            fwrite(STDERR, "No se pudo crear la fuente de financiamiento.\n");
            exit(1);
        }
        $fuente->id = $db->insert_id;
        echo "  [+] fuente '" . FUENTE_NOMBRE . "'  (id {$fuente->id}, S/ " . number_format(FUENTE_MONTO, 2) . ")\n";
    }
} else {
    echo "  [=] fuente '" . FUENTE_NOMBRE . "' ya existia (id {$fuente->id})\n";
}

// ---------------------------------------------------------------------------
// Alta de cada participante
// ---------------------------------------------------------------------------
$creados = 0;
$saltados = 0;
$fallidos = [];

foreach ($filas as $p) {
    $yaExiste = Usuario::findxatributo('email', $p['email']);
    $yaExiste = is_array($yaExiste) ? ($yaExiste[0] ?? null) : $yaExiste;
    if ($yaExiste) {
        echo "  [=] {$p['email']} ya existe, se salta\n";
        $saltados++;
        continue;
    }

    if ($simular) {
        $extra = $p['rol'] === 'coordinador' ? ' + programa + sobre' : '';
        echo "  [+] crearia {$p['email']} ({$p['rol']}){$extra}\n";
        $creados++;
        continue;
    }

    // 1) Persona
    $persona = new Persona([
        'nro_documento'    => $p['documento'],
        'apellido_paterno' => $p['apPaterno'],
        'apellido_materno' => $p['apMaterno'],
        'nombres'          => $p['nombres'],
        'telefono'         => $p['telefono'],
    ]);
    if (!$persona->guardarsinRedireccion()) {
        $fallidos[] = "{$p['email']}: no se pudo crear la persona (documento repetido?)";
        continue;
    }
    $personaId = $db->insert_id;
    $persona->id = $personaId;

    // 2) Usuario. La contrasena provisional aleatoria la pone el constructor de Usuario;
    //    aqui solo se hashea, igual que hace UsuarioController::crear().
    $usuario = new Usuario([
        'persona_id' => $personaId,
        'cargo_id'   => $p['rol'] === 'coordinador' ? 3 : 2,
        'email'      => $p['email'],
    ]);
    $usuario->password = password_hash($usuario->password, PASSWORD_DEFAULT);
    if (!$usuario->guardarsinRedireccion()) {
        $persona->eliminarsinRedireccion();          // no dejar personas huerfanas
        $fallidos[] = "{$p['email']}: no se pudo crear el usuario";
        continue;
    }
    $usuarioId = $db->insert_id;

    // 3) Solo coordinadores: programa propio + vinculo + sobre
    if ($p['rol'] === 'coordinador') {
        $nombrePrograma = $p['programa'] !== ''
            ? $p['programa']
            : 'PROGRAMA DE ' . mb_strtoupper($p['nombres']);

        $programa = new Programa([
            'nombre'           => $nombrePrograma,
            'codigo'           => Programa::siguienteCodigo(),
            'descripcion'      => 'Programa de practicas de ' . $p['nombres'] . ' ' . $p['apPaterno'] . '.',
            'tipo_programa_id' => 1,                  // Nacional
        ]);
        if (!$programa->guardarsinRedireccion()) {
            $fallidos[] = "{$p['email']}: usuario creado, pero fallo el programa";
            continue;
        }
        $programaId = $db->insert_id;

        CoordinadorPrograma::asignarPrograma($usuarioId, $programaId);

        $sobre = new DetalleFinanciamiento([
            'programa_id'              => $programaId,
            'fuente_financiamiento_id' => $fuente->id,
            'monto_asignado'           => SOBRE_POR_COORD,
        ]);
        if (!$sobre->guardarsinRedireccion()) {
            $fallidos[] = "{$p['email']}: programa creado, pero fallo el sobre (revisar capacidad asignable de la fuente)";
            continue;
        }

        echo "  [+] {$p['email']}  coordinador  ->  {$nombrePrograma} (sobre S/ " . number_format(SOBRE_POR_COORD, 2) . ")\n";
    } else {
        echo "  [+] {$p['email']}  contador\n";
    }
    $creados++;
}

// ---------------------------------------------------------------------------
echo "\n";
printf("  Creados: %d   Ya existian: %d   Con problemas: %d\n", $creados, $saltados, count($fallidos));
foreach ($fallidos as $f) {
    echo "    ! {$f}\n";
}

if (!$simular && $creados > 0) {
    echo "\n  Nadie tiene contrasena todavia, y es lo correcto. Cada participante entra asi:\n";
    echo "    1) http://<ip>:8080/login  ->  \"Cambiar contrasena\"\n";
    echo "    2) escribe su correo, recibe un codigo\n";
    echo "    3) lo teclea y define su contrasena\n";
    echo "\n  Si a alguien no le llega el correo:\n";
    echo "    php database/preparar_capacitacion.php --clave <su-correo>\n";
}
echo "\n";
