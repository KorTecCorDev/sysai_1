
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
use Controllers\RendicionFfController;
use Controllers\IngresoEgresoController;
use Controllers\CategoriaRubroController;
use Controllers\ReportePoaRubrosController;
use Controllers\DetalleFinanciamientoController;
use Controllers\Fuente_FinanciamientoController;
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


//Rutas para los REPORTES
//Descargas de reportes
$router->get('/descargar', [ReportePoaRubrosController::class, 'indexdescarga']);


//Rutas para las Rendiciones
$router->get('/rendicion/admin', [RendicionController::class, 'index']);
$router->get('/rendicion/crear', [RendicionController::class, 'crear']);
$router->post('/rendicion/crear', [RendicionController::class, 'crear']);
$router->get('/rendicion/actualizar', [RendicionController::class, 'actualizar']);
$router->post('/rendicion/actualizar', [RendicionController::class, 'actualizar']);
$router->post('/rendicion/eliminar', [RendicionController::class, 'eliminar']);

//Rutas para la selección de fuentes de financiamiento de rendiciones
$router->get('/rendicionff/admin', [RendicionFfController::class, 'index']);
$router->get('/rendicionff/crear', [RendicionFfController::class, 'crear']);
$router->post('/rendicionff/crear', [RendicionFfController::class, 'crear']);


// Rutas para Tipos de Cambio Dólar
//RUTA PROHIBIDA PARA EL COORDINADOR


// Rutas para Tipos de Cambio Euro
//RUTA PROHIBIDA PARA EL COORDINADOR


//Rutas para el reporte de rendiciones
$router->get('/reporte/poa', [ReportePoaRubrosController::class, 'index']);
$router->get('/reporte/guardarpoa', [ReportePoaRubrosController::class, 'crearpoa']);
$router->get('/reporte/poarendicion', [ReportePoaRubrosController::class, 'indexrendicion']);


//Rutas para los saldos contables
//RUTA PROHIBIDA PARA EL COORDINADOR


//Ruta para los otros egresos del coordinador
//¡¡PENDIENTE!!

//Vistaff(selección de fuentes)
$router->get('/ingreso_egreso/ff', [Controllers\IngresoEgresoController::class, 'indexff']);
$router->post('/ingreso_egreso/ff', [Controllers\IngresoEgresoController::class, 'indexff']);


//Ruta del guardado del POA
$router->post('/reporte/guardarpoa', [ReportePoaRubrosController::class, 'indexguardarpoa']);
//Ruta de modificación de estados en el admin del POA
//RUTA PROHIBIDA PARA EL COORDINADOR
