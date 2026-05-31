<?php

// ============================================================================
// PLANTILLA de configuración SMTP (recuperación de contraseña).
// Copiar a `mail.php` (gitignored) y completar con credenciales reales.
// NUNCA poner secretos reales en este archivo de ejemplo.
// ============================================================================

return [
    'host'       => 'smtp.gmail.com',
    'username'   => '',            // usuario SMTP
    'password'   => '',            // app password (NO la contraseña normal de la cuenta)
    'port'       => 587,
    'secure'     => 'tls',         // 'tls' o 'ssl'
    'from_email' => 'no-reply@sysai.local',
    'from_name'  => 'Área de TI - SysAI',
];
