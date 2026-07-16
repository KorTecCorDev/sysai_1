
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
use Controllers\SaldoContableController;
use Controllers\PoaIndicadoresController;
use Controllers\DetalleActividadController;

//Ruta de Categoría Rubros
$router->get('/categoria_rubro/admin', [CategoriaRubroController::class, 'index']);
$router->post('/categoria_rubro/crear', [CategoriaRubroController::class, 'crear']);
$router->get('/categoria_rubro/crear', [CategoriaRubroController::class, 'crear']);
$router->post('/categoria_rubro/actualizar', [CategoriaRubroController::class, 'actualizar']);
$router->get('/categoria_rubro/actualizar', [CategoriaRubroController::class, 'actualizar']);
$router->post('/categoria_rubro/eliminar', [CategoriaRubroController::class, 'eliminar']);

//Rutas para Programas
$router->get('/programa/admin', [ProgramaController::class, 'index']);
$router->post('/programa/crear', [ProgramaController::class, 'crear']);
$router->get('/programa/crear', [ProgramaController::class, 'crear']);
$router->post('/programa/actualizar', [ProgramaController::class, 'actualizar']);
$router->get('/programa/actualizar', [ProgramaController::class, 'actualizar']);
$router->post('/programa/eliminar', [ProgramaController::class, 'eliminar']);

//Rutas para Fuentes de Financiamiento
$router->get('/fuente_financiamiento/admin', [Fuente_FinanciamientoController::class, 'index']);
$router->post('/fuente_financiamiento/crear', [Fuente_FinanciamientoController::class, 'crear']);
$router->get('/fuente_financiamiento/crear', [Fuente_FinanciamientoController::class, 'crear']);
$router->post('/fuente_financiamiento/actualizar', [Fuente_FinanciamientoController::class, 'actualizar']);
$router->get('/fuente_financiamiento/actualizar', [Fuente_FinanciamientoController::class, 'actualizar']);
$router->post('/fuente_financiamiento/eliminar', [Fuente_FinanciamientoController::class, 'eliminar']);

//Rutas para Personas y Usuario
//RUTAS PROHIBIDAS PARA EL CONTADOR

//Rutas para Detalle_financiamiento
$router->get('/dfinanciamiento/crear', [DetalleFinanciamientoController::class, 'crear']);
$router->post('/dfinanciamiento/crear', [DetalleFinanciamientoController::class, 'crear']);

//Rutas para POA Presupuestal (el Contador revisa: aprueba / observa)
$router->get('/poa/admin', [PoaController::class, 'index']);
$router->get('/poa/revisar', [PoaController::class, 'revisar']);
$router->post('/poa/observar', [PoaController::class, 'observar']);
$router->post('/poa/aprobar', [PoaController::class, 'aprobar']);
//El Contador ELABORA el POA del programa Institucional (migr. 033): iniciar y
//enviar SOLO de ese programa (guarda en el controlador); en los demás sigue
//siendo revisor/adenda.
$router->post('/poa/crear', [PoaController::class, 'crear']);
$router->post('/poa/enviar', [PoaController::class, 'enviar']);




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

//Rutas para el POA de Indicadores (el Contador revisa: observa / aprueba)
$router->get('/poa_indicadores/admin', [PoaIndicadoresController::class, 'index']);
$router->get('/poa_indicadores/revisar', [PoaIndicadoresController::class, 'revisar']);
$router->post('/poa_indicadores/observar', [PoaIndicadoresController::class, 'observar']);
$router->post('/poa_indicadores/aprobar', [PoaIndicadoresController::class, 'aprobar']);

//Rutas para los indicadores por actividad (detalle_actividad)
$router->get('/detalle_actividad/editar', [DetalleActividadController::class, 'editar']);
$router->post('/detalle_actividad/guardar', [DetalleActividadController::class, 'guardar']);


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


// Rutas para Tipos de Cambio (unificado USD/EUR — la moneda viaja como ?moneda=, migr. 028)
$router->get('/tcambio/admin', [TipoCambioController::class, 'index']);
$router->get('/tcambio/crear', [TipoCambioController::class, 'crear']);
$router->post('/tcambio/crear', [TipoCambioController::class, 'crear']);
$router->get('/tcambio/actualizar', [TipoCambioController::class, 'actualizar']);
$router->post('/tcambio/actualizar', [TipoCambioController::class, 'actualizar']);
$router->post('/tcambio/eliminar', [TipoCambioController::class, 'eliminar']);
$router->get('/tcambio/sbs', [TipoCambioController::class, 'sbs']);   // consulta informativa (Fase 6): pre-llena, no guarda

//Rutas para el reporte de rendiciones
$router->get('/reporte/poa', [ReportePoaRubrosController::class, 'index']);
$router->get('/reporte/guardarpoa', [ReportePoaRubrosController::class, 'crearpoa']);
$router->get('/reporte/poarendicion', [ReportePoaRubrosController::class, 'indexrendicion']);
$router->get('/reporte/poarubros', [ReportePoaRubrosController::class, 'indexrubro']);
$router->get('/reporte/rendiciones', [ReportePoaRubrosController::class, 'indexreporterendiciones']);
$router->post('/reporte/rendiciones', [ReportePoaRubrosController::class, 'indexreporterendiciones']);
// El formulario de rendiciones/ingresos redirige a las rutas *desc: sin ellas el
// contador caía en 404 (solo estaban registradas para el admin).
$router->get('/reporte/rendicionesdesc', [ReportePoaRubrosController::class, 'indexreporterendicionesdescargar']);
$router->get('/reporte/ingresos', [ReportePoaRubrosController::class, 'indexreporteingresos']);
$router->post('/reporte/ingresos', [ReportePoaRubrosController::class, 'indexreporteingresos']);
$router->get('/reporte/ingresosdesc', [ReportePoaRubrosController::class, 'indexreporteingresosdescargar']);


//Rutas para los saldos contables
$router->get('/saldos_contables/saldos', [SaldoContableController::class, 'index']);
// Item 8 — cierre anual (snapshot por fuente): sufijo /guardar => protegido por CSRF.
$router->post('/cierre_anual/guardar', [SaldoContableController::class, 'cerrar']);

//Rutas para los Otros Ingresos y Egresos
$router->get('/ingreso_egreso/admin', [Controllers\IngresoEgresoController::class, 'index']);
//Creando
$router->get('/ingreso_egreso/crear', [Controllers\IngresoEgresoController::class, 'crear']);
$router->post('/ingreso_egreso/crear', [Controllers\IngresoEgresoController::class, 'crear']);
//Actualizando
$router->get('/ingreso_egreso/actualizar', [Controllers\IngresoEgresoController::class, 'actualizar']);
$router->post('/ingreso_egreso/actualizar', [Controllers\IngresoEgresoController::class, 'actualizar']);
//Eliminando
$router->post('/ingreso_egreso/eliminar', [Controllers\IngresoEgresoController::class, 'eliminar']);

//Ruta del guardado del POA
$router->post('/reporte/guardarpoa', [ReportePoaRubrosController::class, 'indexguardarpoa']);
//Ruta de modificación de estados en el admin del POA
$router->post('/reporte/modificarpoa', [ReportePoaRubrosController::class, 'updateguardarpoa']);
