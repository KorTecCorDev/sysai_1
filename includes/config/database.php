<?php

// Zona horaria de la organización (Huaraz, Perú; sin horario de verano). Se fija en
// código y no en el php.ini porque cada PHP trae la suya: el de consola venía en UTC,
// el de XAMPP en Europe/Berlin y Hostinger suele usar UTC. Con UTC, un gasto
// registrado a las 19:00 quedaba fechado al día siguiente y el 31 de diciembre
// date('Y') cambiaba de ejercicio cinco horas antes (decisión 2026-09-14).
// Va aquí porque este archivo lo cargan la web (includes/app.php) y todos los
// scripts de consola de database/ que trabajan con fechas.
date_default_timezone_set('America/Lima');

/**
 * Localiza el .env, priorizando las ubicaciones FUERA del document root.
 *
 * Un .env dentro del directorio publicado depende, para no ser descargable, de
 * que la configuracion del servidor sea correcta: un .htaccess ignorado, un
 * modulo ausente o un servidor que no lo procesa (php -S) y el archivo se sirve
 * como texto plano. Fuera del docroot esa clase entera de fallo desaparece:
 * ningun request puede alcanzar lo que no esta bajo la raiz publicada.
 *
 * En Hostinger compartido:
 *   /home/uXXXX/domains/<dominio>/secrets/.env      <- aqui
 *   /home/uXXXX/domains/<dominio>/public_html/      <- el proyecto
 *
 * Orden: variable de entorno explicita > secrets/ hermano del proyecto > local.
 * Ver docs/plan-secretos-y-hardening.md.
 */
function rutaEnv(): string {
    $explicita = getenv('SYSAI_ENV_FILE');
    if (is_string($explicita) && $explicita !== '' && is_file($explicita)) {
        return $explicita;
    }
    $raizProyecto = dirname(__DIR__, 2);
    $fueraDelDocroot = dirname($raizProyecto) . DIRECTORY_SEPARATOR . 'secrets' . DIRECTORY_SEPARATOR . '.env';
    if (is_file($fueraDelDocroot)) {
        return $fueraDelDocroot;
    }
    return $raizProyecto . DIRECTORY_SEPARATOR . '.env';   // desarrollo
}

function cargarEnv(string $ruta): void {
    if (!file_exists($ruta)) {
        die('<b>Error de configuración:</b> No se encontró el archivo <code>.env</code>.<br>
             Copia <code>.env.example</code> como <code>.env</code> y configura tus credenciales.<br>
             Se buscó, en orden: <code>$SYSAI_ENV_FILE</code>, <code>../secrets/.env</code> y el <code>.env</code> del proyecto.');
    }

    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (str_starts_with(trim($linea), '#')) continue;
        if (!str_contains($linea, '=')) continue;

        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);

        // El ENTORNO REAL manda sobre el archivo. Antes solo se consultaba
        // $_ENV -que PHP no puebla salvo que variables_order incluya "E"- y
        // acto seguido putenv() pisaba la variable de verdad: el .env ganaba
        // siempre. Con esto, una variable definida en el panel del hosting, por
        // SetEnv o al invocar el script no puede ser sobrescrita por un archivo
        // versionable, que es la precedencia que espera cualquiera.
        if (getenv($clave) === false && !array_key_exists($clave, $_ENV)) {
            $_ENV[$clave] = $valor;
            putenv("$clave=$valor");
        }
    }
}

/**
 * Avisa cuando el .env cifrado del repositorio es MAS NUEVO que el .env en claro
 * de este equipo: has hecho `git pull` y falta `npm run env:pull`.
 *
 * Sin esta comprobacion el fallo es SILENCIOSO, y ya ocurrio: un equipo con un
 * .env viejo siguio funcionando con la configuracion anterior -otro remitente de
 * correo- sin que nada lo indicara. Un desajuste de configuracion que no avisa
 * se diagnostica mirando el sintoma equivocado durante horas.
 *
 * Solo en DESARROLLO y solo si el .env usado es el local del proyecto: en
 * produccion el archivo vive fuera del docroot y no se gestiona con estos
 * scripts, asi que ahi esta comprobacion no aplica.
 */
function comprobarEnvDesactualizado(string $rutaUsada): void {
    $raizProyecto = dirname(__DIR__, 2);
    $local   = $raizProyecto . DIRECTORY_SEPARATOR . '.env';
    $cifrado = $raizProyecto . DIRECTORY_SEPARATOR . 'secrets' . DIRECTORY_SEPARATOR . '.env.enc';

    if ($rutaUsada !== $local || !is_file($cifrado) || !is_file($local)) {
        return;
    }
    $entorno = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development';
    if (strtolower((string) $entorno) !== 'development') {
        return;
    }
    // Margen de 2 s: `git pull` puede escribir ambos archivos casi a la vez.
    if (filemtime($cifrado) <= filemtime($local) + 2) {
        return;
    }

    $aviso = 'La configuracion cifrada del repositorio (secrets/.env.enc) es mas '
           . 'nueva que tu .env local. Ejecuta:  npm run env:pull';

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "\n[AVISO] {$aviso}\n\n");
        return;                       // en CLI no se aborta: rompe la suite de QA
    }
    http_response_code(500);          // un error de configuracion no es un 200
    die('<b>Configuración desactualizada:</b> ' . htmlspecialchars($aviso)
        . '<br><small>Este aviso solo aparece en desarrollo. Ver docs/plan-secretos-y-hardening.md.</small>');
}

$rutaEnvUsada = rutaEnv();
cargarEnv($rutaEnvUsada);
comprobarEnvDesactualizado($rutaEnvUsada);

/**
 * Valor de configuración: el ENTORNO REAL manda, el .env es el respaldo.
 *
 * B9 (2026-08-14, corregido 2026-09-14): conectarDB() leía solo $_ENV, pero
 * cargarEnv() NO copia al $_ENV las claves que ya existen en el entorno real y
 * PHP no puebla $_ENV salvo con variables_order="E". Resultado: definir DB_NAME
 * como variable de entorno —lo normal en un hosting— hacía DESAPARECER el valor
 * en vez de imponerse. getenv() primero cierra el agujero sin tocar la carga.
 */
function envValor(string $clave, ?string $defecto = null): ?string {
    $real = getenv($clave);
    if ($real !== false) {
        return $real;
    }
    return array_key_exists($clave, $_ENV) ? (string) $_ENV[$clave] : $defecto;
}

function conectarDB(): mysqli {
    $host = envValor('DB_HOST', 'localhost');
    $user = envValor('DB_USER', '');
    $pass = envValor('DB_PASS', '');
    $name = envValor('DB_NAME', '');

    // El código comprueba valores de retorno (no usa try/catch): sin esto, PHP >= 8.1
    // lanza mysqli_sql_exception y cualquier error de SQL sería un fatal no capturado.
    mysqli_report(MYSQLI_REPORT_OFF);

    $db = new mysqli($host, $user, $pass, $name);

    if ($db->connect_errno) {
        $entorno = envValor('APP_ENV', 'production');
        if ($entorno === 'development') {
            die('<b>Error de conexión a la BD:</b> ' . $db->connect_error);
        } else {
            die('Error interno del servidor. Intenta más tarde.');
        }
    }

    $db->set_charset('utf8mb4');

    // Modo estricto por sesión (portable a Hostinger, donde no controlamos my.cnf):
    // todo truncamiento/overflow pasa de warning silencioso a error ruidoso.
    // Lista explícita → dev y prod idénticos por construcción.
    $db->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

    // Misma zona horaria que PHP (ver date_default_timezone_set al inicio de este archivo):
    // NOW()/CURDATE() se usan en inserciones y en las ventanas del límite de intentos, y en
    // Hostinger el servidor de BD suele estar en UTC. Desfase fijo y no 'America/Lima': las
    // tablas de zonas horarias de MySQL no vienen cargadas por defecto (tampoco en XAMPP) y
    // Perú no tiene horario de verano.
    $db->query("SET time_zone = '-05:00'");

    return $db;
}
