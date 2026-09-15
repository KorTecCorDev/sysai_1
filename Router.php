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
            // Acciones de flujo documental (POA Indicadores/Presupuestal/Rendición) y
            // captura de indicadores: mutan estado y por tanto también exigen token.
            || str_ends_with($url, '/enviar')
            || str_ends_with($url, '/observar')
            || str_ends_with($url, '/aprobar')
            || str_ends_with($url, '/guardar')
            || in_array($url, [
                '/login', '/chgpsswd', '/token_verify', '/updtepsswd',
                // /logout también: por GET cualquier página externa podía cerrarle la
                // sesión a un usuario con un <img src="/logout"> (auditoría 2026-09-14).
                '/logout',
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
            // La sesión se revalida contra la BD en cada petición (auditoría 2026-09-14, M3):
            // si el usuario se eliminó, cambió de cargo o de programa, o cambió su contraseña,
            // la sesión abierta deja de valer al instante. Antes conservaba el cargo y el
            // programa con los que entró mientras siguiera activo.
            if (!\Model\Login::sesionSigueValida()) {
                $_SESSION = [];
                session_destroy();
                header('Location: /login?revocada=1');
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

        // /logout sin sesión (expirada o ya cerrada): no hay nada que cerrar ni token que
        // validar. Se lleva al login en vez de mostrar un 403 que el usuario no entiende.
        if ($urlActual === '/logout' && !$logueado) {
            header('Location: /login');
            exit;
        }

        // Protección CSRF en acciones POST sensibles (eliminaciones y cambio de estado del POA)
        if ($metodo === 'POST' && $this->requiereCsrf($urlActual) && !verificar_csrf()) {
            // 403 y no 419: el 419 no es un código HTTP estándar (lo inventó Laravel) y
            // bajo Apache sale convertido en 500, que además de mentir dispara alertas
            // de error de servidor (auditoría de seguridad 2026-09-14).
            http_response_code(403);
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
