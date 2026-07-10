<?php
/**
 * hash.php — Utilidad de línea de comandos para generar y verificar hashes
 * de contraseña, usando el MISMO algoritmo que la app (PASSWORD_DEFAULT = bcrypt).
 *
 * ⚠️ Solo CLI. No debe ejecutarse por web.
 *
 * Uso:
 *   php hash.php "MiClave"                 → imprime el hash bcrypt de "MiClave"
 *   php hash.php "MiClave" '<hash>'        → verifica si "MiClave" coincide con <hash>
 *   php hash.php                           → hashea el set de usuarios de ejemplo
 *                                            y emite los UPDATE SQL listos para pegar
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo disponible por línea de comandos.');
}

$args = array_slice($argv, 1);

// --- Modo verificación: php hash.php "clave" "<hash>" -------------------------
if (count($args) === 2) {
    [$clave, $hash] = $args;
    $ok = password_verify($clave, $hash);
    echo ($ok ? "[OK] COINCIDE" : "[X] NO COINCIDE")
        . "  -  '{$clave}'\n";
    exit($ok ? 0 : 1);
}

// --- Modo hash simple: php hash.php "clave" -----------------------------------
if (count($args) === 1) {
    echo password_hash($args[0], PASSWORD_DEFAULT) . "\n";
    exit(0);
}

// --- Sin argumentos: set de ejemplo + UPDATE SQL ------------------------------
// Ajusta esta lista con los usuarios/contraseñas que quieras sembrar o resetear.
$usuarios = [
    // email                    => contraseña en claro
    'admin@arcoiris.pe'         => 'Arcoiris2026*',
    // 'contador@arcoiris.pe'   => 'Contador2026*',
    // 'coordinador@arcoiris.pe'=> 'Coord2026*',
];

echo "-- Hashes bcrypt (PASSWORD_DEFAULT) — mismo algoritmo que la app\n";
echo "-- Genera/actualiza contraseñas ejecutando estos UPDATE en la BD `sysai`.\n\n";

foreach ($usuarios as $email => $clave) {
    $hash = password_hash($clave, PASSWORD_DEFAULT);
    echo "-- {$email}  →  {$clave}\n";
    echo "UPDATE usuario SET password = '{$hash}' WHERE email = '{$email}';\n\n";
}
