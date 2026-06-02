<?php
/**
 * Runner de migraciones SysAI.
 *
 * Ejecuta en orden los archivos database/migrations/NNN_*.sql que aún no se
 * hayan aplicado, registrando cada versión en la tabla `schema_migrations`.
 *
 * Uso (desde la raíz del proyecto):
 *   php database/migrate.php            -> aplica las migraciones pendientes
 *   php database/migrate.php --status   -> lista aplicadas y pendientes
 *
 * Idempotente: una migración ya registrada se omite. Las credenciales se leen
 * del .env mediante includes/config/database.php (mismo que usa la app).
 */

require __DIR__ . '/../includes/config/database.php';

$db = conectarDB();

$db->query(
    "CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `version` varchar(255) NOT NULL,
        `ejecutada_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`version`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
);

// Versiones ya aplicadas
$aplicadas = [];
$res = $db->query("SELECT `version` FROM `schema_migrations`");
while ($r = $res->fetch_assoc()) {
    $aplicadas[$r['version']] = true;
}

$archivos = glob(__DIR__ . '/migrations/*.sql');
sort($archivos, SORT_STRING);

// Modo --status: solo informar
if (in_array('--status', $argv, true)) {
    echo "Migraciones:\n";
    foreach ($archivos as $archivo) {
        $version = substr(basename($archivo), 0, 3);
        $estado  = isset($aplicadas[$version]) ? '[X] aplicada ' : '[ ] pendiente';
        echo "  {$estado}  " . basename($archivo) . "\n";
    }
    exit(0);
}

$ejecutadas = 0;
foreach ($archivos as $archivo) {
    $nombre  = basename($archivo);
    $version = substr($nombre, 0, 3);

    if (isset($aplicadas[$version])) {
        echo "SKIP  {$nombre}\n";
        continue;
    }

    $sql = file_get_contents($archivo);
    echo "RUN   {$nombre} ... ";

    if ($db->multi_query($sql)) {
        do {
            if ($r = $db->store_result()) {
                $r->free();
            }
        } while ($db->more_results() && $db->next_result());
    }

    if ($db->errno) {
        echo "ERROR\n  -> " . $db->error . "\n";
        echo "Migración detenida. Corrige el archivo y vuelve a ejecutar.\n";
        exit(1);
    }

    // Respaldo del registro por si el .sql no lo insertó (INSERT IGNORE = sin duplicados)
    $db->query("INSERT IGNORE INTO `schema_migrations` (`version`) VALUES ('{$version}')");
    echo "OK\n";
    $ejecutadas++;
}

echo $ejecutadas === 0
    ? "Sin migraciones pendientes.\n"
    : "Listo: {$ejecutadas} migración(es) aplicada(s).\n";
