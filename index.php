<?php
// M1 — script-src sin 'unsafe-inline'/'unsafe-eval' (no quedan scripts ni handlers inline ni eval).
// style-src conserva 'unsafe-inline' por necesidad (atributos style= y estilos que inyectan Bootstrap/AOS).
// Auditoría 2026-09-14 (M5): fuera cdn.jsdelivr.net — ninguna vista carga nada de ahí, y permitir
// un CDN público entero en script-src anula la CSP ante un XSS (cualquier paquete npm sirve de
// gadget). frame-ancestors protege del clickjacking sin depender de mod_headers.
// ⚠️ Mantener IDÉNTICA a la del .htaccess: bajo Apache, `Header set` la sobrescribe.
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-src 'none'; object-src 'none'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// M2 — Endurecimiento de sesión y transporte (defensa en profundidad, además del .htaccess).
$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) == 443)
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

// Cookie de sesión: httponly + SameSite=Lax siempre; secure solo bajo HTTPS (en dev http no rompe).
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => $esHttps,
    'samesite' => 'Lax',
]);

// HSTS: forzar HTTPS en navegadores (solo tiene efecto y se emite si ya vamos por HTTPS).
if ($esHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Modo estricto: PHP rechaza IDs de sesión que él no generó (cierra la fijación de sesión
// por ID inventado) y solo acepta la sesión por cookie, nunca por URL. En el .htaccess estaba
// dentro de <IfModule mod_php.c>, inerte en Hostinger (auditoría 2026-09-14, M5).
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_start();

require_once __DIR__ . '/includes/app.php';

use MVC\Router;
use Controllers\PaginasController;
use Controllers\ProgramaController;
use Controllers\Fuente_FinanciamientoController;
use Controllers\UsuarioController;
use Controllers\CategoriaRubroController;
use Controllers\DetalleFinanciamientoController;
use Controllers\LoginController;
use Controllers\PoaController;
use Controllers\ResultadoController;
use Controllers\RubroController;
use Controllers\ProductoController;
use Controllers\ActividadController;
use Controllers\ReportePoaRubrosController;
use Controllers\RendicionController;
use Controllers\TipoCambioController;
use Model\ReportePoaRubros;

$router = new Router();
//Ruta principal al ingresar -> directo al login
$router->get('/', [PaginasController::class, 'index']);
//Ruta para la página de error 404
$router->get('/error', [PaginasController::class, 'error404']);

//Rutas para el LOGIN
$router->get('/login', [LoginController::class, 'login']);
$router->post('/login', [LoginController::class, 'login']);

//Ruta de Logout: solo POST con token CSRF (Router::requiereCsrf). Por GET cualquier
//página externa podía cerrarle la sesión a un usuario (auditoría 2026-09-14).
$router->post('/logout', [LoginController::class, 'logout']);
//Rutas en caso haya un cambio de password
$router->get('/chgpsswd', [LoginController::class, 'cambiarPassword']);
$router->post('/chgpsswd', [LoginController::class, 'cambiarPassword']);
//Rutas para verificacion de reset_token
$router->get('/token_verify', [LoginController::class, 'token_verify']);
$router->post('/token_verify', [LoginController::class, 'token_verify']);
//Rutas para actualizacion de contraseña
$router->get('/updtepsswd', [LoginController::class, 'updatePassword']);
$router->post('/updtepsswd', [LoginController::class, 'updatePassword']);

//Existe un logueo activo?
if (isset($_SESSION['login']) && $_SESSION['login'] == true) {
    // Importando rutas del administrador, contador y coordinador
    //Creamos un switch para los 3 index a insertar
    switch ($_SESSION['cargo_id']) {
        case 1:
            // SIDEBAR ADMIN en caso el $cargo=1
            require_once __DIR__ . '/iadmin.php';
            break;
        case 2:
            // SIDEBAR CONTADOR en caso el $cargo=2
            require_once __DIR__ . '/iconta.php';
            break;
        case 3:
            // SIDEBAR COORDINADOR en caso el $cargo=3
            require_once __DIR__ . '/icoordi.php';
            break;
        default:
            break;
    }
}
//Comprobando las rutas
$router->comprobarRutas();
