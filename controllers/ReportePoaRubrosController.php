<?php

namespace Controllers;

use MVC\Router;
use Model\Programa;
use Model\Rendicion;
use Model\TipoCambioEuro;
use Model\TipoCambioDolar;
use Model\ReportePoaRubros;
use Model\ReporteEgresosVista;


use Model\ReporteFuentesVista;
use Model\FuenteFinanciamiento;
use Model\ReporteIngresosVista;
use Model\RendicionFuentesVista;
use Model\ReportePoaRubrosSumas;
use Model\ReporteRendicionesVista;
use Model\UsuarioDisponiblePrograma;
use Model\ReporteFuentesProgramaVista;
use Model\RendicionFuentesCantidadVista;

class ReportePoaRubrosController
{
    public static function index(Router $router)
    {
        // Obtenemos todos los IDs de programa como enteros
        $programas = Programa::all();

        // Obtenemos todos los registros de ReportePoaRubros
        $resbienes = ReportePoaRubros::all();

        // Obtenemos los demás datos requeridos
        $sumas = ReportePoaRubrosSumas::all();
        $tcdolar = TipoCambioDolar::findlast();
        $tceuro = TipoCambioEuro::findlast();

        $router->render('reporte/poa', [
            'tcdolar'    => $tcdolar,
            'tceuro'     => $tceuro,
            'programas'  => $programas,
            'resbienes'  => $resbienes,
            'sumas'      => $sumas
        ]);
    }

    public static function indexrendicion(Router $router)
    {
        // Obtenemos todos los IDs de programa como enteros
        $programas = Programa::all();

        // Obtenemos todos los registros de ReportePoaRubros
        $resbienes = ReportePoaRubros::all();

        // Obtenemos los demás datos requeridos
        $sumas = ReportePoaRubrosSumas::all();
        $rendiciones = RendicionFuentesVista::all();
        $tcdolar = TipoCambioDolar::findlast();
        $tceuro = TipoCambioEuro::findlast();
        $ffnro   = RendicionFuentesCantidadVista::all();
        $fuentes = ReporteFuentesProgramaVista::all();
        $router->render('reporte/poarendicion', [
            'tcdolar'    => $tcdolar,
            'programas'  => $programas,
            'tceuro'     => $tceuro,
            'resbienes'  => $resbienes,
            'ffnro'      => $ffnro,
            'sumas'      => $sumas,
            'fuentes'    => $fuentes,
            'rendiciones' => $rendiciones
        ]);
    }


    public static function indexrubro(Router $router)
    {
        // Obtenemos todos los IDs de programa como enteros
        $programas = Programa::all();

        // Obtenemos todos los registros de ReportePoaRubros
        $resbienes = ReportePoaRubros::all();

        // Obtenemos los demás datos requeridos
        $sumas = ReportePoaRubrosSumas::all();
        $tcdolar = TipoCambioDolar::findlast();
        $tceuro = TipoCambioEuro::findlast();

        $router->render('reporte/poarubros', [
            'tcdolar'    => $tcdolar,
            'tceuro'     => $tceuro,
            'programas'  => $programas,
            'resbienes'  => $resbienes,
            'sumas'      => $sumas
        ]);
    }

    public static function indexreporterendiciones(Router $router)
    {
        $resreporterendiciones = ReporteRendicionesVista::all();
        $resreporteegresos = ReporteEgresosVista::all();

        $router->render('reporte/rendiciones', [
            'resreporterendiciones' => $resreporterendiciones,
            'resreporteegresos' => $resreporteegresos
        ]);
    }
    public static function indexreporteingresos(Router $router)
    {
        $resreportefuentes = ReporteFuentesVista::all();
        $resreporteingresos = ReporteIngresosVista::all();

        $router->render('reporte/ingresos', [
            'resreportefuentes' => $resreportefuentes,
            'resreporteingresos' => $resreporteingresos
        ]);
    }
    public static function indexsaldos(Router $router)
    {
        $resreportefuentes = ReporteFuentesVista::all();
        $resreporteingresos = ReporteIngresosVista::all();

        $router->render('saldos_contables/saldos', [
            
        ]);
    }
    public static function crearpoa(Router $router)
    {
            $usuariosdispo=UsuarioDisponiblePrograma::all();
        $router->render('reporte/guardarpoa', [
            'usuariodispo' => $usuariosdispo
        ]);
    }
}
