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

    public function comprobarRutas()
{
    // Iniciar sesión si no está activa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Obtener URL actual
    $urlActual = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $urlActual = $urlActual === '/' ? $urlActual : rtrim($urlActual, '/');

    // Rutas protegidas
    //Falta las rutas de todas la vistas, a excepción de login todas deben estar protegidas.
    $rutas_protegidas = ['/programa/admin', '/programa/crear', '/programa/actualizar', '/actividad/admin','/actividad/actualizar','/actividad/crear', '/actividad/eliminar','/categoria_rubro/admin','/categoria_rubro/actualizar','/categoria_rubro/crear','/categoria_rubro/eliminar','/dfinanciamiento/admin','/dfinanciamiento/actualizar','/dfinanciamiento/crear','/dfinanciamiento/eliminar','/fuente_financiamiento/admin','/fuente_financiamiento/actualizar','/fuente_financiamiento/crear','/fuente_financiamiento/eliminar','/ingreso_egreso/admin','/ingreso_egreso/actualizar','/ingreso_egreso/crear','/ingreso_egreso/eliminar','/poa/admin','/poa/actualizar','/poa/crear','/poa/eliminar','/producto/admin','/producto/actualizar','/producto/crear','/producto/eliminar','/rendicion/admin','/rendicion/actualizar','/rendicion/crear','/rendicion/eliminar','/rendicionff/admin','/rendicionff/actualizar','/rendicionff/crear','/rendicionff/eliminar','/reporte/guardarpoa','/reporte/ingresos','/reporte/ingresosdesc','/reporte/modificarpoa','/reporte/poa','/reporte/poarendicion','/reporte/poarubros','/reporte/rendiciones','/reporte/rendicionesdesc','/resultado/admin','/resultado/actualizar','/resultado/crear','/resultado/eliminar','/resultado/admin','/resultado/actualizar','/resultado/crear','/resultado/eliminar'];
    
    // Verificar rutas protegidas
    if (in_array($urlActual, $rutas_protegidas)) {
        if (!isset($_SESSION['login'])) {
            header('Location: /login');
            exit;
        }
    }

    // Evitar bucle en /login si ya está autenticado
    if ($urlActual === '/login' && isset($_SESSION['login'])) {
        header('Location: /');
        exit;
    }

    // Resto de la lógica del router...
    $metodo = $_SERVER['REQUEST_METHOD'];
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
