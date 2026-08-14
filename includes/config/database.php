<?php

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

cargarEnv(rutaEnv());

function conectarDB(): mysqli {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';
    $name = $_ENV['DB_NAME'] ?? '';

    // El código comprueba valores de retorno (no usa try/catch): sin esto, PHP >= 8.1
    // lanza mysqli_sql_exception y cualquier error de SQL sería un fatal no capturado.
    mysqli_report(MYSQLI_REPORT_OFF);

    $db = new mysqli($host, $user, $pass, $name);

    if ($db->connect_errno) {
        $entorno = $_ENV['APP_ENV'] ?? 'production';
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

    return $db;
}
