<?php

namespace MVC;

class Router
{

    public $rutasGET = [];
    public $rutasPOST = [];


    public function get($url, $fn)
    {
        $this->rutasGET[$url] = $fn;
    }
    public function post($url, $fn)
    {
        $this->rutasPOST[$url] = $fn;
    }

    // Rutas públicas: únicas accesibles sin sesión iniciada.
    public $rutas_publicas = ['/', '/login', '/logout', '/chgpsswd', '/token_verify', '/updtepsswd', '/error'];

    // ¿La ruta POST exige token CSRF? (todas las acciones que mutan datos, incluidos
    // los formularios públicos de autenticación — M5: login-CSRF y cambio de contraseña).
    private function requiereCsrf(string $url): bool
    {
        return str_ends_with($url, '/crear')
            || str_ends_with($url, '/actualizar')
            || str_ends_with($url, '/eliminar')
            || in_array($url, [
                '/reporte/modificarpoa', '/reporte/guardarpoa',
                '/login', '/chgpsswd', '/token_verify', '/updtepsswd',
            ], true);
    }

    public function comprobarRutas()
    {
        // Iniciar sesión si no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Obtener URL actual
        $urlActual = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $urlActual = $urlActual === '/' ? $urlActual : rtrim($urlActual, '/');
        $metodo = $_SERVER['REQUEST_METHOD'];

        $logueado = isset($_SESSION['login']) && $_SESSION['login'] === true;

        // M2 — Timeout de sesión por inactividad (30 min). Si se supera, se cierra la
        // sesión y se redirige al login; en cada petición autenticada se renueva la marca.
        $inactividadMax = 1800;
        if ($logueado) {
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactividadMax) {
                $_SESSION = [];
                session_destroy();
                header('Location: /login?expirado=1');
                exit;
            }
            $_SESSION['last_activity'] = time();
        }

        // Por defecto TODO requiere sesión, salvo la lista blanca de rutas públicas.
        if (!in_array($urlActual, $this->rutas_publicas, true) && !$logueado) {
            header('Location: /login');
            exit;
        }

        // Evitar bucle en /login si ya está autenticado
        if ($urlActual === '/login' && $logueado) {
            header('Location: /');
            exit;
        }

        // Protección CSRF en acciones POST sensibles (eliminaciones y cambio de estado del POA)
        if ($metodo === 'POST' && $this->requiereCsrf($urlActual) && !verificar_csrf()) {
            http_response_code(419);
            exit('Token de seguridad inválido o expirado. Recargue la página e intente de nuevo.');
        }

        // Despacho de la ruta
        $fn = ($metodo === 'GET') ? ($this->rutasGET[$urlActual] ?? null) : ($this->rutasPOST[$urlActual] ?? null);

        if ($fn) {
            call_user_func($fn, $this);
        } else {
            //Redireccionar a error 404
            header('Location: /error');
            exit;
        }
    }

    //Muestra una vista
    public function render($view, $datos = [])
    {
        foreach ($datos as $key => $value) {
            $$key = $value;
        }
        ob_start(); //Inicia el almacenamiento en memoria
        include __DIR__ . "/views/{$view}.php"; //aquí almacenamos en memoria a que le estamos dando render
        $contenido = ob_get_clean(); //Lo almacenamos en la variable de contenido
        include __DIR__ . "/views/layout.php";
    }
    //Mostrando la vista de login sin el sidebar
    public function renderssdbr($view, $datos = [])
    {
        foreach ($datos as $key => $value) {
            $$key = $value;
        }
        ob_start(); //Inicia el almacenamiento en memoria
        include __DIR__ . "/views{$view}.php"; //aquí almacenamos en memoria a que le estamos dando render
        $contenido = ob_get_clean(); //Lo almacenamos en la variable de contenido
        include __DIR__ . "/views/layout_login.php";
    }
}
