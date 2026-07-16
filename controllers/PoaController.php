<?php

namespace Controllers;

use MVC\Router;
use Model\Poa;
use Model\Rubro;
use Model\Programa;
use Model\Producto;
use Model\Actividad;
use Model\Resultado;
use Model\Rendicion;

class PoaController
{
    // Listado del documento POA Presupuestal.
    //  - Coordinador: solo el de SU programa (año vigente); puede iniciarlo si falta.
    //  - Admin / Contador: todos los documentos para gestionar el flujo de aprobación.
    public static function index(Router $router)
    {
        exigirRol([1, 2, 3]);
        $anio = date('Y');
        $resultado = $_GET['resultado'] ?? null;

        if (esCoordinador()) {
            $programaId = programaIdCoordinador();
            $programa = $programaId ? Programa::find($programaId) : null;
            $documento = $programaId ? Poa::porProgramaAnio($programaId, $anio) : null;
            $documentos = $documento ? [$documento] : [];
            // Total en vivo de los rubros (lo que tendrá el presupuesto al enviar).
            $presupuestoVivo = $programaId ? Poa::presupuestoCalculado($programaId) : 0;
        } else {
            $programa = null;
            $documentos = Poa::all();
            $presupuestoVivo = 0;
        }

        // Mapa programa_id => nombre para mostrar en el listado (admin/contador).
        $programas = [];
        foreach (Programa::all() as $p) {
            $programas[$p->id] = $p->nombre;
        }

        // Tope por sobres (item 4): Σ monto_asignado por programa, para mostrar el
        // margen Σ rubros vs Σ sobres en el panel del coordinador y en el listado.
        $topesSobres = [];
        foreach ($documentos as $d) {
            $topesSobres[$d->programa_id] = Poa::topeSobres((int) $d->programa_id);
        }
        $topeSobres = esCoordinador() && !empty($programaId) ? Poa::topeSobres((int) $programaId) : 0.0;

        $router->render('poa/admin', [
            'documentos'      => $documentos,
            'programas'       => $programas,
            'programa'        => $programa,
            'anio'            => $anio,
            'presupuestoVivo' => $presupuestoVivo,
            'topeSobres'      => $topeSobres,
            'topesSobres'     => $topesSobres,
            'resultado'       => $resultado
        ]);
    }

    // El coordinador inicia el documento de su programa para el año vigente (uno solo).
    public static function crear(Router $router)
    {
        exigirRol([1, 2, 3]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /poa/admin');
            exit();
        }

        $anio = date('Y');
        // El programa lo determina la sesión del coordinador (no se acepta vía POST).
        $programaId = esCoordinador()
            ? programaIdCoordinador()
            : (int) ($_POST['programa_id'] ?? 0);

        if (!$programaId) {
            header('Location: /poa/admin?resultado=10');
            exit();
        }

        // Puerta de sobres (item 4): sin sobres asignados el coordinador no inicia
        // el POA Presupuestal (Contador/Admin pasan).
        exigirSobreAsignado($programaId);

        // Uno solo por programa/año: si ya existe, no se duplica.
        if (Poa::porProgramaAnio($programaId, $anio)) {
            header('Location: /poa/admin?resultado=17');
            exit();
        }

        $doc = new Poa([
            'programa_id' => $programaId,
            'usuario_id'  => $_SESSION['id'],
            'anio'        => $anio,
            'presupuesto' => Poa::presupuestoCalculado($programaId),
            'estado'      => Poa::BORRADOR
        ]);
        $doc->validar();
        $errores = Poa::getErrores();
        if (empty($errores)) {
            Poa::setUsuarioActual();
            $doc->guardarsinRedireccion();
            header('Location: /poa/admin?resultado=1');
            exit();
        }
        header('Location: /poa/admin?resultado=12');
        exit();
    }

    // Coordinador: Borrador/Observado -> Enviado. Recalcula el presupuesto desde los
    // rubros y a partir de aquí queda bloqueado para editar rubros.
    public static function enviar(Router $router)
    {
        exigirRol([1, 2, 3]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /poa/admin');
            exit();
        }

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            header('Location: /poa/admin');
            exit();
        }

        $doc = Poa::find($id);
        if (!$doc) {
            header('Location: /poa/admin');
            exit();
        }

        // El coordinador solo opera sobre el documento de su propio programa.
        exigirProgramaPropio($doc->programa_id);

        // Puerta de sobres (item 4): sin sobres, el coordinador no envía (19).
        exigirSobreAsignado($doc->programa_id);

        if (!in_array((int) $doc->estado, [Poa::BORRADOR, Poa::OBSERVADO], true)) {
            header('Location: /poa/admin?resultado=13');
            exit();
        }

        // Tope por sobres (item 4): Σ rubros ≤ Σ sobres del programa; si excede,
        // no se envía (19 = sin sobres, 20 = sobre el tope).
        $tope = Poa::validarTopeSobres((int) $doc->programa_id);
        if (!$tope['ok']) {
            header('Location: /poa/admin?resultado=' . ($tope['sobres'] <= 0 ? 19 : 20));
            exit();
        }

        $doc->estado = Poa::ENVIADO;
        $doc->observacion = null;
        // El presupuesto del documento se congela con la suma de rubros vigente.
        $doc->presupuesto = Poa::presupuestoCalculado($doc->programa_id);
        Poa::setUsuarioActual();
        $doc->guardarsinRedireccion();
        header('Location: /poa/admin?resultado=2');
        exit();
    }

    // Contador/Admin: Enviado -> Observado (devuelve al coordinador para subsanar).
    // Requiere un comentario que explique qué debe corregir.
    public static function observar(Router $router)
    {
        exigirRol([1, 2]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /poa/admin');
            exit();
        }

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $observacion = trim($_POST['observacion'] ?? '');
        if (!$id) {
            header('Location: /poa/admin');
            exit();
        }

        $doc = Poa::find($id);
        if (!$doc) {
            header('Location: /poa/admin');
            exit();
        }

        // Solo desde Enviado.
        if ((int) $doc->estado !== Poa::ENVIADO) {
            header('Location: /poa/admin?resultado=13');
            exit();
        }

        // El comentario de observación es obligatorio.
        if ($observacion === '') {
            header('Location: /poa/revisar?id=' . $doc->id . '&resultado=15');
            exit();
        }

        $doc->estado = Poa::OBSERVADO;
        $doc->observacion = $observacion;
        Poa::setUsuarioActual();
        $doc->guardarsinRedireccion();
        header('Location: /poa/admin?resultado=2');
        exit();
    }

    // Contador/Admin: Enviado -> Aprobado.
    // (El presupuesto_comprometido NO se calcula aquí: es la Σ de los sobres de la
    //  fuente — detalle_financiamiento.monto_asignado, migr. 020 — y las vistas de
    //  saldo lo calculan en vivo. Ver CLAUDE.md → [REGLAS DE NEGOCIO] → Fuentes.)
    // Item 6 ("POA Rendición" = este mismo documento): al aprobar, las rendiciones del
    // programa se APRUEBAN (estado=1) y se congelan; recién entonces descuentan el saldo
    // contable (las vistas de saldo filtran estado=1 — migr. 018).
    public static function aprobar(Router $router)
    {
        exigirRol([1, 2]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /poa/admin');
            exit();
        }

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            header('Location: /poa/admin');
            exit();
        }

        $doc = Poa::find($id);
        if (!$doc) {
            header('Location: /poa/admin');
            exit();
        }

        if ((int) $doc->estado !== Poa::ENVIADO) {
            header('Location: /poa/admin?resultado=13');
            exit();
        }

        // Tope por sobres (item 4): se REVALIDA al aprobar — los sobres pueden
        // haber bajado entre el envío y la aprobación. Vuelve a la pantalla de
        // revisión, donde el Contador ve Σ rubros vs Σ sobres.
        $tope = Poa::validarTopeSobres((int) $doc->programa_id);
        if (!$tope['ok']) {
            header('Location: /poa/revisar?id=' . $doc->id . '&resultado=' . ($tope['sobres'] <= 0 ? 19 : 20));
            exit();
        }

        // Cobertura de tipo de cambio (migr. 029, plan de montos Fase 3): las
        // rendiciones "pendientes de TC" se reintentan (quizá ya se cargaron las
        // tasas); si alguna sigue sin cobertura, la aprobación se bloquea — la
        // pantalla de revisión detalla qué fechas faltan.
        Rendicion::recongelarPendientesPorPrograma($doc->programa_id);
        if (Rendicion::fechasSinTcPorPrograma($doc->programa_id)) {
            header('Location: /poa/revisar?id=' . $doc->id . '&resultado=22');
            exit();
        }

        $doc->estado = Poa::APROBADO;
        $doc->observacion = null;
        Poa::setUsuarioActual();
        $doc->guardarsinRedireccion();
        // Item 6: congela y aprueba las rendiciones del programa -> descuento del saldo contable.
        Rendicion::aprobarPorPrograma($doc->programa_id);
        header('Location: /poa/admin?resultado=2');
        exit();
    }

    // Vista de revisión consolidada (solo lectura) del árbol Resultado→Producto→
    // Actividad→Rubros, con el total presupuestado. La usa el Contador/Admin para
    // decidir y el Coordinador para previsualizar.
    public static function revisar(Router $router)
    {
        exigirRol([1, 2, 3]);
        $id = validarORedireccionar('/poa/admin');
        $doc = Poa::find($id);
        if (!$doc) {
            header('Location: /poa/admin');
            exit();
        }
        // El coordinador solo revisa el documento de su propio programa.
        if (esCoordinador()) {
            exigirProgramaPropio($doc->programa_id);
        }

        $programa = Programa::find($doc->programa_id);

        // Árbol consolidado: resultado -> productos -> actividades -> rubros.
        $arbol = [];
        $total = 0.0;
        foreach (Resultado::findxatributo('programa_id', $doc->programa_id) as $r) {
            $productos = [];
            foreach (Producto::findxatributo('resultado_id', $r->id) as $p) {
                $actividades = [];
                foreach (Actividad::findxatributo('producto_id', $p->id) as $a) {
                    $rubros = Rubro::findxatributo('actividad_id', $a->id);
                    foreach ($rubros as $ru) {
                        $total += (float) $ru->monto;
                    }
                    $actividades[] = ['actividad' => $a, 'rubros' => $rubros];
                }
                $productos[] = ['producto' => $p, 'actividades' => $actividades];
            }
            $arbol[] = ['resultado' => $r, 'productos' => $productos];
        }

        $resultado = $_GET['resultado'] ?? null;
        $router->render('poa/revisar', [
            'doc'        => $doc,
            'programa'   => $programa,
            'arbol'      => $arbol,
            'total'      => $total,
            // Tope por sobres (item 4): el revisor decide viendo el margen.
            'topeSobres' => Poa::topeSobres((int) $doc->programa_id),
            // Fechas de rendiciones sin TC congelado (Fase 3): si hay, no se puede aprobar.
            'fechasSinTc' => Rendicion::fechasSinTcPorPrograma((int) $doc->programa_id),
            'resultado'  => $resultado
        ]);
    }
}
