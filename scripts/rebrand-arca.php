<?php
/**
 * Barrido de marca visible: "SysAI" / "SysAi"  ->  "Arca"
 * ------------------------------------------------------------------
 * Ejecutar desde la raíz del repo o desde cualquier lado:
 *     php scripts/rebrand-arca.php
 *
 * Qué hace:
 *   - Cambia SOLO la superficie visible al usuario: el <title> y la
 *     <meta description> de los 4 layouts (+ 404), y el nombre del
 *     remitente de correos (plantilla .env.example + fallback en código).
 *
 * Qué NO toca (a propósito):
 *   - El identificador TÉCNICO "sysai": nombre de BD, namespace PHP,
 *     repo, rutas. La marca cambia; el id interno se queda.
 *   - Tu .env LOCAL (no versionado): actualiza MAIL_FROM_NAME a mano
 *     si quieres que los correos ya salgan como "Arca" en este equipo.
 *   - Comentarios internos de dev (CLAUDE.md, README de BD, SQL, SCSS):
 *     "SysAI" queda como nombre-clave interno del proyecto. Ver la
 *     sección OPCIONAL al final si algún día quieres barrerlos también.
 *
 * Es idempotente: si ya se aplicó, no encuentra nada que cambiar (no-op).
 * No requiere recompilar assets (ningún archivo tocado es SCSS/JS).
 */

$root = dirname(__DIR__);

// Layouts visibles: comparten EXACTAMENTE el mismo <title> y <meta>.
$layouts = [
    'views/layout_admin.php',
    'views/layout_contador.php',
    'views/layout_coordinador.php',
    'views/error404.php',
];

// [ ruta => [ [buscar, reemplazar], ... ] ]
$reglas = [];

foreach ($layouts as $f) {
    $reglas[$f] = [
        ['<title>SysAI</title>', '<title>Arca &middot; Arco Iris</title>'],
        [
            'content="SysAi - Sistema de reportes contables para la ONG Arco Iris"',
            'content="Arca - Sistema de gestión presupuestal y rendición de cuentas de la ONG Arco Iris"',
        ],
    ];
}

// Remitente de correos.
$reglas['.env.example'] = [
    ['MAIL_FROM_NAME=Área de TI - SysAI', 'MAIL_FROM_NAME=Área de TI - Arca'],
];
$reglas['includes/config/mail.php'] = [
    ["'Área de TI - SysAI'", "'Área de TI - Arca'"],
];

// Red de seguridad: en ESAS mismas rutas, cualquier "SysAI"/"SysAi" que
// haya quedado (por diferencias de redacción) se normaliza a "Arca".
$fallbackTokens = ['SysAI', 'SysAi'];

$totalCambios = 0;
$archivosTocados = 0;

echo "== Barrido de marca visible -> Arca ==\n\n";

foreach ($reglas as $rel => $pares) {
    $ruta = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($ruta)) {
        echo "  [!] no existe (se omite): $rel\n";
        continue;
    }
    $original = file_get_contents($ruta);
    $contenido = $original;
    $cambiosArchivo = 0;

    foreach ($pares as [$buscar, $reemplazar]) {
        $n = 0;
        $contenido = str_replace($buscar, $reemplazar, $contenido, $n);
        $cambiosArchivo += $n;
    }
    // Red de seguridad de tokens sueltos en este mismo archivo.
    foreach ($fallbackTokens as $tok) {
        $n = 0;
        $contenido = str_replace($tok, 'Arca', $contenido, $n);
        $cambiosArchivo += $n;
    }

    if ($contenido !== $original) {
        file_put_contents($ruta, $contenido);
        echo "  [ok] $rel  ({$cambiosArchivo} reemplazo/s)\n";
        $archivosTocados++;
        $totalCambios += $cambiosArchivo;
    } else {
        echo "  [--] $rel  (sin cambios / ya aplicado)\n";
    }
}

echo "\nResumen: {$totalCambios} reemplazo(s) en {$archivosTocados} archivo(s).\n";

// Verificación: no debe quedar marca visible sin barrer en las rutas objetivo.
$residuo = [];
foreach (array_keys($reglas) as $rel) {
    $ruta = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (is_file($ruta) && preg_match('/SysA[Ii]/', file_get_contents($ruta))) {
        $residuo[] = $rel;
    }
}
if ($residuo) {
    echo "\n[!] Todavía hay marca visible en: " . implode(', ', $residuo) . "\n";
    echo "    Revísalos a mano.\n";
    exit(1);
}

echo "\nListo. Recordatorios:\n";
echo "  1) Actualiza MAIL_FROM_NAME en tu .env LOCAL (no versionado) si aplica.\n";
echo "  2) El logo definitivo (PNG) se cablea aparte en views/login.php.\n";
echo "  3) Revisa en el navegador y commitea.\n";
