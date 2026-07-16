<?php

namespace Controllers;

use Model\SaldoFuenteFinanciamientoVista;
use MVC\Router;
use Model\TipoCambio;
use Model\VistaTotalIngresos;
use Model\VistaTotalEgresos;
use Model\VistaSaldoContable;
use Model\SaldoSobreVista;

class SaldoContableController
{
    public static function index(Router $router)
    {
        // Totales: vistas de agregación (una sola fila). Extraemos el escalar
        // con fallback a 0 para que el KPI nunca quede vacío si no hay datos.
        $agIngresos = VistaTotalIngresos::all();
        $agEgresos  = VistaTotalEgresos::all();
        $agSaldo    = VistaSaldoContable::all();

        $ingresos = $agIngresos[0]->total_ingresos ?? 0;
        $egresos  = $agEgresos[0]->total_egresos ?? 0;
        $saldo    = $agSaldo[0]->saldo_contable ?? 0;

        // Saldo por fuente + desglose de sus componentes (presupuesto, ingresos,
        // egresos, rendiciones aprobadas), mapeado por id. La vista de saldo solo
        // expone el total; el desglose alimenta las tarjetas y la barra sin tocar
        // el esquema SQL.
        $saldofuentes = SaldoFuenteFinanciamientoVista::all();
        $desglosefuentes = SaldoFuenteFinanciamientoVista::desglosePorFuente();

        // Nivel sobre (programa, fuente): saldo contable de cada sub-presupuesto.
        $saldosobres = SaldoSobreVista::all();

        // Conversión al CIERRE (plan de montos, Fase 4): un saldo es partida monetaria
        // => TC de COMPRA vigente a hoy (NIC 21). null sin cobertura: la vista muestra
        // "sin tipo de cambio registrado" — nunca revienta ni inventa.
        $hoy = date('Y-m-d');
        $tcUsdCierre = TipoCambio::vigente('USD', $hoy);
        $tcEurCierre = TipoCambio::vigente('EUR', $hoy);

        $router->render('saldos_contables/saldos', [
            'ingresos'        => $ingresos,
            'egresos'         => $egresos,
            'saldo'           => $saldo,
            'saldofuentes'    => $saldofuentes,
            'desglosefuentes' => $desglosefuentes,
            'saldosobres'     => $saldosobres,
            'tcUsdCierre'     => $tcUsdCierre,
            'tcEurCierre'     => $tcEurCierre,
        ]);
    }
}
