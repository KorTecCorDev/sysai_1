<?php

namespace Controllers;

use Model\SaldoFuenteFinanciamientoVista;
use MVC\Router;
use Model\FuentePresupuestoAnual;
use Model\Poa;
use Model\Rendicion;
use Model\TipoCambio;
use Model\VistaTotalIngresos;
use Model\VistaTotalEgresos;
use Model\VistaSaldoContable;
use Model\SaldoSobreVista;

class SaldoContableController
{
    public static function index(Router $router)
    {
        exigirRol([1, 2]);
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

        // Item 8 — cierre anual: datos para el botón (aviso de re-cierre y de
        // rendiciones pendientes) y el histórico por año desde la tabla anual.
        $anio = date('Y');
        $fechaCierre = FuentePresupuestoAnual::fechaCierre($anio);
        $rendicionesPendientes = Rendicion::contarPendientes();
        $historicoAnual = FuentePresupuestoAnual::historico();

        // Item 8 — "saldo de programa visible solo con POA aprobado": mapa
        // programa_id => ¿POA del año vigente Aprobado? (sin POA = no aprobado).
        $poaAprobado = [];
        $docs = Poa::consultarPreparado("SELECT programa_id, estado FROM poa WHERE anio = ?", 's', [$anio]) ?: [];
        foreach ($docs as $p) {
            $poaAprobado[(int) $p->programa_id] = ((int) $p->estado === Poa::APROBADO);
        }

        $router->render('saldos_contables/saldos', [
            'ingresos'        => $ingresos,
            'egresos'         => $egresos,
            'saldo'           => $saldo,
            'saldofuentes'    => $saldofuentes,
            'desglosefuentes' => $desglosefuentes,
            'saldosobres'     => $saldosobres,
            'tcUsdCierre'     => $tcUsdCierre,
            'tcEurCierre'     => $tcEurCierre,
            'anio'            => $anio,
            'fechaCierre'     => $fechaCierre,
            'rendicionesPendientes' => $rendicionesPendientes,
            'historicoAnual'  => $historicoAnual,
            'poaAprobado'     => $poaAprobado,
            'resultado'       => $_GET['resultado'] ?? null,
        ]);
    }

    /**
     * Item 8 — Registrar el cierre anual (decisiones confirmadas 2026-07-16):
     * lo dispara el Contador/Admin con el botón de la pantalla de saldos; es un
     * UPSERT por (fuente, año) — re-cerrar sobrescribe el snapshot con aviso
     * previo en el confirm; las rendiciones pendientes advierten, no bloquean.
     */
    public static function cerrar(Router $router)
    {
        exigirRol([1, 2]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /saldos_contables/saldos');
            exit();
        }
        FuentePresupuestoAnual::setUsuarioActual();
        $registradas = FuentePresupuestoAnual::cerrarAnio(date('Y'));
        header('Location: /saldos_contables/saldos?resultado=' . ($registradas > 0 ? 23 : 24));
        exit();
    }
}
