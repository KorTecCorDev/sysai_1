<?php

namespace Controllers;

use Model\SaldoFuenteFinanciamientoVista;
use MVC\Router;
use Model\VistaTotalIngresos;
use Model\VistaTotalEgresos;
use Model\VistaSaldoContable;

class SaldoContableController
{
    public static function index(Router $router)
    {
        $totalingresos = VistaTotalIngresos::all();
        $totalegresos = VistaTotalEgresos::all();
        $totalsaldocontable = VistaSaldoContable::all();
        $totalsaldofuentes = SaldoFuenteFinanciamientoVista::all();

        $router->render('saldos_contables/saldos', [
            'totalingresos' => $totalingresos,
            'totalegresos' => $totalegresos,
            'totalsaldocontable' => $totalsaldocontable,
            'totalsaldofuentes' => $totalsaldofuentes,

        ]);
    }
}
