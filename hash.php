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
 *
 * ⚠️ No sirve para dar de alta contraseñas de usuarios reales: el alta genera una
 * aleatoria que nadie conoce y cada uno activa la suya por correo (/chgpsswd). El
 * administrador inicial se crea con database/crear_admin.php.
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

// --- Sin argumentos: ayuda ------------------------------------------------------
// Antes emitía UPDATE con una lista de correos y contraseñas en claro escrita en este
// archivo (la del admin del seed incluida): quedaban publicadas en el repositorio.
fwrite(STDERR, "Uso: php hash.php \"clave\"            (imprime su hash)\n"
    . "     php hash.php \"clave\" '<hash>'   (verifica si coinciden)\n");
exit(2);
