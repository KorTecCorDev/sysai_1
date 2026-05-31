<?php

// ============================================================================
// PLANTILLA de configuración de base de datos.
// Copiar este archivo a `database.php` y ajustar las credenciales del entorno.
// `database.php` está en .gitignore → NUNCA se versiona (no contiene secretos en git).
// ============================================================================

function conectarDB(): mysqli
{
    // --- Credenciales por entorno (ajustar) ---
    $host = '127.0.0.1';   // local XAMPP/MariaDB. En producción: el host del proveedor.
    $user = 'root';        // local: root
    $pass = '';            // local: sin contraseña
    $name = 'sysai';       // nombre de la base de datos
    $port = 3306;

    // El código de la app comprueba valores de retorno (no usa try/catch),
    // así que desactivamos el modo excepción de mysqli (default en PHP 8.1+).
    mysqli_report(MYSQLI_REPORT_OFF);

    $db = @new mysqli($host, $user, $pass, $name, $port);
    if ($db->connect_errno) {
        error_log('Error de conexión a la BD: ' . $db->connect_error);
        http_response_code(500);
        exit('Error de conexión a la base de datos. Revise la configuración.');
    }
    return $db;
}
