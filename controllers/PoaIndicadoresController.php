<?php

namespace Controllers;

use MVC\Router;
use Model\Programa;
use Model\Producto;
use Model\Actividad;
use Model\Resultado;
use Model\PoaIndicadores;
use Model\DetalleActividad;

class PoaIndicadoresController
{
    // Listado de documentos POA Indicadores.
    //  - Coordinador: solo el de SU programa (año vigente); se crea al vuelo si falta el contexto.
    //  - Admin / Contador: todos los documentos para gestionar el flujo de aprobación.
    public static function index(Router $router)
    {
        exigirRol([1, 2, 3]);
        $anio = date('Y');
        $resultado = $_GET['resultado'] ?? null;

        if (esCoordinador()) {
            $programaId = programaIdCoordinador();
            $programa = $programaId ? Programa::find($programaId) : null;
            $documento = $programaId ? PoaIndicadores::porProgramaAnio($programaId, $anio) : null;
            $documentos = $documento ? [$documento] : [];
        } else {
            $programa = null;
            $documentos = PoaIndicadores::all();
        }

        // Mapa programa_id => nombre para mostrar en el listado (admin/contador).
        $programas = [];
        foreach (Programa::all() as $p) {
            $programas[$p->id] = $p->nombre;
        }

        $router->render('poa_indicadores/admin', [
            'documentos' => $documentos,
            'programas'  => $programas,
            'programa'   => $programa,
            'anio'       => $anio,
            'resultado'  => $resultado
        ]);
    }

    // El coordinador crea el documento de su programa para el año vigente (uno solo).
    public static function crear(Router $router)
    {
        exigirRol([1, 2, 3]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /poa_indicadores/admin');
            exit();
        }

        $anio = date('Y');
        // El programa lo determina la sesión del coordinador (no se acepta vía POST).
        $programaId = esCoordinador()
            ? programaIdCoordinador()
            : (int) ($_POST['programa_id'] ?? 0);

        if (!$programaId) {
            header('Location: /poa_indicadores/admin?resultado=10');
            exit();
        }

        // Uno solo por programa/año: si ya existe, no se duplica.
        if (PoaIndicadores::porProgramaAnio($programaId, $anio)) {
            header('Location: /poa_indicadores/admin?resultado=11');
            exit();
        }

        $doc = new PoaIndicadores([
            'programa_id' => $programaId,
            'usuario_id'  => $_SESSION['id'],
            'anio'        => $anio,
            'estado'      => PoaIndicadores::BORRADOR
        ]);
        $doc->validar();
        $errores = PoaIndicadores::getErrores();
        if (empty($errores)) {
            PoaIndicadores::setUsuarioActual();
            $doc->guardarsinRedireccion();
            header('Location: /poa_indicadores/admin?resultado=1');
            exit();
        }
        header('Location: /poa_indicadores/admin?resultado=12');
        exit();
    }

    // Coordinador: Borrador/Observado -> Enviado. A partir de aquí queda bloqueado.
    public static function enviar(Router $router)
    {
        self::transicionar(
            [1, 2, 3],
            [PoaIndicadores::BORRADOR, PoaIndicadores::OBSERVADO],
            PoaIndicadores::ENVIADO,
            true // solo el coordinador dueño (o admin) puede enviar
        );
    }

    // Contador/Admin: Enviado -> Observado (devuelve al coordinador para subsanar).
    // Requiere un comentario que explique qué debe corregir el coordinador.
    public static function observar(Router $router)
    {
        exigirRol([1, 2]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /poa_indicadores/admin');
            exit();
        }

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $observacion = trim($_POST['observacion'] ?? '');
        if (!$id) {
            header('Location: /poa_indicadores/admin');
            exit();
        }

        $doc = PoaIndicadores::find($id);
        if (!$doc) {
            header('Location: /poa_indicadores/admin');
            exit();
        }

        // Solo desde Enviado.
        if ((int) $doc->estado !== PoaIndicadores::ENVIADO) {
            header('Location: /poa_indicadores/admin?resultado=13');
            exit();
        }

        // El comentario de observación es obligatorio (el coordinador debe saber qué subsanar).
        if ($observacion === '') {
            header('Location: /poa_indicadores/revisar?id=' . $doc->id . '&resultado=15');
            exit();
        }

        $doc->estado = PoaIndicadores::OBSERVADO;
        $doc->observacion = $observacion;
        PoaIndicadores::setUsuarioActual();
        $doc->guardarsinRedireccion();
        header('Location: /poa_indicadores/admin?resultado=2');
        exit();
    }

    // Vista de revisión consolidada (solo lectura) del árbol Resultado→Producto→
    // Actividad→indicador. La usa el Contador/Admin para decidir y el Coordinador
    // para previsualizar. Las acciones (aprobar/observar) se muestran en la vista.
    public static function revisar(Router $router)
    {
        exigirRol([1, 2, 3]);
        $id = validarORedireccionar('/poa_indicadores/admin');
        $doc = PoaIndicadores::find($id);
        if (!$doc) {
            header('Location: /poa_indicadores/admin');
            exit();
        }
        // El coordinador solo revisa el documento de su propio programa.
        if (esCoordinador()) {
            exigirProgramaPropio($doc->programa_id);
        }

        $programa = Programa::find($doc->programa_id);

        // Árbol consolidado: por cada resultado, sus productos; por cada producto,
        // sus actividades; por cada actividad, su indicador (detalle_actividad).
        $arbol = [];
        foreach (Resultado::findxatributo('programa_id', $doc->programa_id) as $r) {
            $productos = [];
            foreach (Producto::findxatributo('resultado_id', $r->id) as $p) {
                $actividades = [];
                foreach (Actividad::findxatributo('producto_id', $p->id) as $a) {
                    $actividades[] = [
                        'actividad' => $a,
                        'indicador' => DetalleActividad::porActividad($a->id)
                    ];
                }
                $productos[] = ['producto' => $p, 'actividades' => $actividades];
            }
            $arbol[] = ['resultado' => $r, 'productos' => $productos];
        }

        $resultado = $_GET['resultado'] ?? null;
        $router->render('poa_indicadores/revisar', [
            'doc'       => $doc,
            'programa'  => $programa,
            'arbol'     => $arbol,
            'resultado' => $resultado
        ]);
    }

    // Contador/Admin: Enviado -> Aprobado.
    public static function aprobar(Router $router)
    {
        self::transicionar(
            [1, 2],
            [PoaIndicadores::ENVIADO],
            PoaIndicadores::APROBADO,
            false
        );
    }

    /**
     * Cambio de estado validado: rol permitido, documento existente, estado de
     * origen esperado y (opcional) propiedad del programa para el coordinador.
     */
    private static function transicionar(array $rolesPermitidos, array $estadosOrigen, int $estadoDestino, bool $exigirPropio): void
    {
        exigirRol($rolesPermitidos);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /poa_indicadores/admin');
            exit();
        }

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            header('Location: /poa_indicadores/admin');
            exit();
        }

        $doc = PoaIndicadores::find($id);
        if (!$doc) {
            header('Location: /poa_indicadores/admin');
            exit();
        }

        // El coordinador solo opera sobre el documento de su propio programa.
        if ($exigirPropio) {
            exigirProgramaPropio($doc->programa_id);
        }

        // Solo se permite la transición desde un estado de origen válido.
        if (!in_array((int) $doc->estado, $estadosOrigen, true)) {
            header('Location: /poa_indicadores/admin?resultado=13');
            exit();
        }

        $doc->estado = $estadoDestino;
        // Al enviar (tras subsanar) o aprobar, la observación previa ya no aplica.
        $doc->observacion = null;
        PoaIndicadores::setUsuarioActual();
        $doc->guardarsinRedireccion();
        header('Location: /poa_indicadores/admin?resultado=2');
        exit();
    }
}
