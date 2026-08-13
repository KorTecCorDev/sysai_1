<?php
// Diagnóstico del SMTP configurado en el .env (recuperación de contraseña).
//
// Uso:  php database/smtp_test.php  destinatario@ejemplo.com
//
// Comprueba, en orden: que el .env trae credenciales, que la extensión openssl
// y el bundle de CA están operativos (en Windows es el fallo más común), que el
// handshake TLS con el servidor SMTP funciona y, por último, envía un correo de
// prueba real. Cada paso informa por separado para saber DÓNDE se rompe: un
// "no llegó el correo" sin más es lo que hace eternas estas configuraciones.
//
// No toca la base de datos ni genera tokens: es seguro correrlo cuantas veces
// haga falta. Para ver el diálogo SMTP completo: MAIL_DEBUG=1 en el .env.

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/config/database.php';   // carga el .env
require __DIR__ . '/../includes/funciones.php';

$destino = $argv[1] ?? '';
if ($destino === '' || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php database/smtp_test.php <destinatario@ejemplo.com>\n");
    exit(2);
}

$cfg = require __DIR__ . '/../includes/config/mail.php';
$fallos = 0;
function paso(string $etiqueta, bool $ok, string $detalle = ''): void
{
    global $fallos;
    if (!$ok) { $fallos++; }
    printf("  [%s] %s%s\n", $ok ? 'OK  ' : 'FALL', $etiqueta, $detalle !== '' ? " — {$detalle}" : '');
}

echo "\n=== 1) Configuración leída del .env ===\n";
printf("  host=%s  puerto=%s  seguridad=%s\n", $cfg['host'], $cfg['port'], $cfg['secure']);
printf("  usuario=%s\n", $cfg['username'] !== '' ? $cfg['username'] : '(vacío)');
printf("  remitente=%s <%s>\n", $cfg['from_name'], $cfg['from_email']);
paso('Hay credenciales SMTP (si no, la app queda en modo DEV y solo escribe mail.log)',
     $cfg['username'] !== '' && $cfg['password'] !== '');
if ($cfg['username'] === '' || $cfg['password'] === '') {
    echo "\n  → Completa MAIL_USERNAME y MAIL_PASSWORD en el .env y vuelve a correr.\n\n";
    exit(1);
}
if ($cfg['host'] === 'smtp.gmail.com' && strlen(str_replace(' ', '', $cfg['password'])) !== 16) {
    paso('La contraseña de Gmail parece NO ser una App Password', false,
         'Google exige una clave de aplicación de 16 caracteres; la del correo no funciona con SMTP');
}

echo "\n=== 2) TLS en esta máquina (php.ini) ===\n";
paso('Extensión openssl activa', extension_loaded('openssl'));
$cafile = ini_get('openssl.cafile');
paso('openssl.cafile configurado', $cafile !== '' && is_file($cafile), $cafile !== '' ? $cafile : 'vacío');

echo "\n=== 3) Handshake con el servidor SMTP ===\n";
$destinoTls = ((int) $cfg['port'] === 465 ? 'ssl://' : 'tcp://') . $cfg['host'] . ':' . $cfg['port'];
$conn = @stream_socket_client($destinoTls, $errno, $errstr, 10);
paso("Conexión a {$destinoTls}", $conn !== false, $conn === false ? "{$errstr} ({$errno})" : '');
if ($conn) { fclose($conn); }

echo "\n=== 4) Envío real a {$destino} ===\n";
$mail = construirMailer();
if ($mail === null) {
    paso('construirMailer() devolvió null', false, 'no hay credenciales');
} else {
    try {
        $mail->addAddress($destino);
        $mail->isHTML(true);
        $mail->Subject = 'Prueba de SMTP — Arca';
        $mail->Body    = '<p>Si lees esto, el SMTP de <strong>Arca</strong> quedó configurado correctamente.</p>'
                       . '<p>Enviado el ' . date('d/m/Y H:i:s') . ' desde el entorno de '
                       . (($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development')) . '.</p>'
                       . '<hr><small>Arca — Organización Arco Iris</small>';
        $mail->AltBody = 'Prueba de SMTP de Arca enviada el ' . date('d/m/Y H:i:s') . '.';
        $enviado = $mail->send();
        paso('Correo de prueba enviado', $enviado, $enviado ? '' : $mail->ErrorInfo);
    } catch (\Throwable $e) {
        paso('Correo de prueba enviado', false, $e->getMessage());
    }
}

echo "\n" . ($fallos === 0
    ? "=== SMTP OPERATIVO — revisa la bandeja de {$destino} (y la carpeta de spam) ===\n\n"
    : "=== {$fallos} comprobación(es) con problemas — ver el detalle arriba ===\n\n");
exit($fallos === 0 ? 0 : 1);
