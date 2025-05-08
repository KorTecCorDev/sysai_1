<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline' 'unsafe-eval'; style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-src 'none'; object-src 'none'");

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
use Controllers\RendicionFfController;
use Controllers\TipoCambioController;
use Model\ReportePoaRubros;

$router = new Router();
//Ruta principal al ingresar -> directo al login
$router->get('/', [PaginasController::class, 'index']);

//Rutas para el LOGIN
$router->get('/login', [LoginController::class, 'login']);
$router->post('/login', [LoginController::class, 'login']);

//Ruta de Logout
$router->get('/logout', [LoginController::class, 'logout']);
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
if (isset($_SESSION['login'])) {
    
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
