<?php

// ============================================================================
// Configuración SMTP (recuperación de contraseña).
// SIN secretos: los valores se leen del .env (igual que database.php).
// El .env se carga en el bootstrap (includes/config/database.php → cargarEnv()).
// Si MAIL_USERNAME/MAIL_PASSWORD están vacíos → modo desarrollo: el token se
// registra en includes/logs/mail.log y no se envía correo real.
// ============================================================================

return [
    'host'       => $_ENV['MAIL_HOST']       ?? getenv('MAIL_HOST')       ?: 'smtp.gmail.com',
    'username'   => $_ENV['MAIL_USERNAME']   ?? getenv('MAIL_USERNAME')   ?: '',
    'password'   => $_ENV['MAIL_PASSWORD']   ?? getenv('MAIL_PASSWORD')   ?: '',
    'port'       => $_ENV['MAIL_PORT']       ?? getenv('MAIL_PORT')       ?: 587,
    'secure'     => $_ENV['MAIL_SECURE']     ?? getenv('MAIL_SECURE')     ?: 'tls',
    'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? getenv('MAIL_FROM_EMAIL') ?: 'no-reply@sysai.local',
    'from_name'  => $_ENV['MAIL_FROM_NAME']  ?? getenv('MAIL_FROM_NAME')  ?: 'Área de TI - SysAI',
];
