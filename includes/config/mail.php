<?php

// ============================================================================
// Configuración del correo saliente (recuperación de contraseña).
// SIN secretos: los valores se leen del .env (igual que database.php).
// El .env se carga en el bootstrap (includes/config/database.php → cargarEnv()).
//
// Transporte (MAIL_TRANSPORT):
//   smtp → se habla SMTP de verdad. Con MAIL_USERNAME vacío va SIN autenticar:
//          es como funciona el catcher local de desarrollo (Mailpit), que acepta
//          todo y no reenvía nada a Internet ⇒ desarrollo SIN credenciales.
//   log  → no se envía nada; queda constancia en includes/logs/ (sin el token).
//   auto → compatibilidad con la configuración histórica: smtp si hay
//          credenciales, log si no.
// ============================================================================

/**
 * Lee una variable del entorno distinguiendo "no definida" de "definida vacía".
 *
 * El patrón anterior —`$_ENV[$k] ?? getenv($k) ?: $porDefecto`— agrupa como
 * `($_ENV[$k] ?? getenv($k)) ?: $porDefecto` porque `??` liga más fuerte que
 * `?:`, de modo que un valor VACÍO A PROPÓSITO caía siempre en el valor por
 * defecto. Eso hacía imposible expresar `MAIL_SECURE=` ("sin cifrado") y
 * PHPMailer terminaba negociando STARTTLS contra un servidor local sin TLS.
 *
 * @param bool $vacioEsAusente true para claves donde la cadena vacía no tiene
 *                             sentido (host, puerto): ahí sí se usa el default.
 */
// Este archivo se incluye con `require` (no `require_once`) desde
// construirMailer(): sin esta guarda, una segunda llamada en la misma petición
// abortaría con "Cannot redeclare mailEnv()".
if (!function_exists('mailEnv')) {
    function mailEnv(string $clave, string $porDefecto = '', bool $vacioEsAusente = false): string
    {
        $valor = $_ENV[$clave] ?? getenv($clave);
        if ($valor === false || $valor === null) {
            return $porDefecto;                       // no definida
        }
        $valor = (string) $valor;
        if ($valor === '' && $vacioEsAusente) {
            return $porDefecto;
        }
        return $valor;                                // definida: se respeta, aunque sea ''
    }
}

return [
    'transport'  => strtolower(mailEnv('MAIL_TRANSPORT', 'auto', true)),
    'host'       => mailEnv('MAIL_HOST', 'smtp.gmail.com', true),
    'username'   => mailEnv('MAIL_USERNAME'),
    'password'   => mailEnv('MAIL_PASSWORD'),
    'port'       => (int) mailEnv('MAIL_PORT', '587', true),
    // Vacío = sin cifrado (catcher local). 'tls' = STARTTLS, 'ssl' = SMTPS.
    'secure'     => mailEnv('MAIL_SECURE'),
    'from_email' => mailEnv('MAIL_FROM_EMAIL', 'no-reply@sysai.local', true),
    'from_name'  => mailEnv('MAIL_FROM_NAME', 'Área de TI - Arca', true),
    // MAIL_DEBUG=1 → PHPMailer vuelca el diálogo SMTP al error_log. Solo para
    // diagnosticar un envío que falla; dejar vacío en operación normal.
    'debug'      => mailEnv('MAIL_DEBUG'),
];
