<?php
require 'funciones.php';
require 'config/database.php';

// Fuera de desarrollo los errores van al log, nunca a la pantalla: un aviso de PHP
// revela rutas del servidor, consultas y nombres de tablas. No se confía en la
// configuración del hosting (auditoría de seguridad 2026-09-14, H2). Sin APP_ENV
// se asume producción: el descuido debe fallar hacia el lado seguro.
if (strtolower((string) ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production')) !== 'development') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
require __DIR__.'/../vendor/autoload.php';

use Model\ActiveRecord;

//Conectando a la BD
$db=conectarDB();

//Instanciando el método de conectividad a BDs
ActiveRecord::setDB($db);