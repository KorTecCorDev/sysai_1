<?php

function cargarEnv(string $ruta): void {
    if (!file_exists($ruta)) {
        die('<b>Error de configuración:</b> No se encontró el archivo <code>.env</code>.<br>
             Copia <code>.env.example</code> como <code>.env</code> y configura tus credenciales.');
    }

    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (str_starts_with(trim($linea), '#')) continue;
        if (!str_contains($linea, '=')) continue;

        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);

        if (!array_key_exists($clave, $_ENV)) {
            $_ENV[$clave] = $valor;
            putenv("$clave=$valor");
        }
    }
}

cargarEnv(__DIR__ . '/../../.env');

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
