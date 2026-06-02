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
    return $db;
}
