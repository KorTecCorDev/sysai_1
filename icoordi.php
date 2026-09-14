<?php

use Controllers\PoaController;
use Controllers\RubroController;
use Controllers\UsuarioController;
use Controllers\ProductoController;
use Controllers\ProgramaController;
use Controllers\ActividadController;
use Controllers\RendicionController;
use Controllers\ResultadoController;
use Controllers\TipoCambioController;
use Controllers\IngresoEgresoController;
use Controllers\CategoriaRubroController;
use Controllers\ReportePoaRubrosController;
use Controllers\DetalleFinanciamientoController;
use Controllers\Fuente_FinanciamientoController;
use Controllers\PoaIndicadoresController;
use Controllers\DetalleActividadController;

// Solo se carga desde index.php, con $router ya creado. Pedido directamente por URL
// (/icoordi.php) respondía con un fatal error que revela la ruta del servidor.
if (!isset($router)) {
    http_response_code(404);
    exit;
}

//Ruta de Categoría Rubros
//RUTA PROHIBIDA PARA EL COORDINADOR


//Rutas para Programas
//RUTA PROHIBIDA PARA EL COORDINADOR

//Rutas para Fuentes de Financiamiento
//RUTA PROHIBIDA PARA EL COORDINADOR


//Rutas para Personas y Usuario
//RUTAS PROHIBIDAS PARA EL COORDINADOR

//Rutas para Detalle_financiamiento
//RUTA PROHIBIDA PARA EL COORDINADOR


//Rutas para POA
//RUTA PROHIBIDA PARA EL COORDINADOR





//Rutas para el RESULTADO
$router->get('/resultado/admin', [ResultadoController::class, 'index']);
$router->post('/resultado/admin', [ResultadoController::class, 'index']);
$router->get('/resultado/crear', [ResultadoController::class, 'crear']);
$router->post('/resultado/crear', [ResultadoController::class, 'crear']);
$router->get('/resultado/actualizar', [ResultadoController::class, 'actualizar']);
$router->post('/resultado/actualizar', [ResultadoController::class, 'actualizar']);
$router->post('/resultado/eliminar', [ResultadoController::class, 'eliminar']);

//Rutas para los productos
$router->get('/producto/admin', [ProductoController::class, 'index']);
$router->get('/producto/crear', [ProductoController::class, 'crear']);
$router->post('/producto/crear', [ProductoController::class, 'crear']);
$router->get('/producto/actualizar', [ProductoController::class, 'actualizar']);
$router->post('/producto/actualizar', [ProductoController::class, 'actualizar']);
$router->post('/producto/eliminar', [ProductoController::class, 'eliminar']);

//Rutas para las actividades
$router->get('/actividad/admin', [ActividadController::class, 'index']);
$router->get('/actividad/crear', [ActividadController::class, 'crear']);
$router->post('/actividad/crear', [ActividadController::class, 'crear']);
$router->get('/actividad/actualizar', [ActividadController::class, 'actualizar']);
$router->post('/actividad/actualizar', [ActividadController::class, 'actualizar']);
$router->post('/actividad/eliminar', [ActividadController::class, 'eliminar']);

//Rutas para los rubros
$router->get('/rubro/admin', [RubroController::class, 'index']);
$router->get('/rubro/crear', [RubroController::class, 'crear']);
$router->post('/rubro/crear', [RubroController::class, 'crear']);
$router->get('/rubro/actualizar', [RubroController::class, 'actualizar']);
$router->post('/rubro/actualizar', [RubroController::class, 'actualizar']);
$router->post('/rubro/eliminar', [RubroController::class, 'eliminar']);

//Rutas para POA Presupuestal (el Coordinador elabora: inicia / envía)
$router->get('/poa/admin', [PoaController::class, 'index']);
$router->get('/poa/revisar', [PoaController::class, 'revisar']);
$router->post('/poa/crear', [PoaController::class, 'crear']);
$router->post('/poa/enviar', [PoaController::class, 'enviar']);

//Rutas para el POA de Indicadores (el Coordinador elabora: crea / envía)
$router->get('/poa_indicadores/admin', [PoaIndicadoresController::class, 'index']);
$router->get('/poa_indicadores/revisar', [PoaIndicadoresController::class, 'revisar']);
$router->post('/poa_indicadores/crear', [PoaIndicadoresController::class, 'crear']);
$router->post('/poa_indicadores/enviar', [PoaIndicadoresController::class, 'enviar']);

//Rutas para los indicadores por actividad (detalle_actividad)
$router->get('/detalle_actividad/editar', [DetalleActividadController::class, 'editar']);
$router->post('/detalle_actividad/guardar', [DetalleActividadController::class, 'guardar']);


//Rutas para los REPORTES


//Rutas para las Rendiciones
$router->get('/rendicion/admin', [RendicionController::class, 'index']);
$router->get('/rendicion/crear', [RendicionController::class, 'crear']);
$router->post('/rendicion/crear', [RendicionController::class, 'crear']);
$router->get('/rendicion/actualizar', [RendicionController::class, 'actualizar']);
$router->post('/rendicion/actualizar', [RendicionController::class, 'actualizar']);
$router->post('/rendicion/eliminar', [RendicionController::class, 'eliminar']);



// Rutas para Tipos de Cambio Dólar
//RUTA PROHIBIDA PARA EL COORDINADOR


// Rutas para Tipos de Cambio Euro
//RUTA PROHIBIDA PARA EL COORDINADOR


//Rutas para el reporte de rendiciones
$router->get('/reporte/poa', [ReportePoaRubrosController::class, 'index']);
$router->get('/reporte/poarendicion', [ReportePoaRubrosController::class, 'indexrendicion']);


//Rutas para los saldos contables
//RUTA PROHIBIDA PARA EL COORDINADOR


//Otros Ingresos/Egresos: SOLO Contador (aprobación automática) — el coordinador no
//tiene rutas OIE (regla confirmada, item 7)


//Ruta del guardado del POA
//Ruta de modificación de estados en el admin del POA
