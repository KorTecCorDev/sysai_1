<?php

namespace Controllers;

use MVC\Router;

use Model\Programa;
use Model\DetalleFinanciamiento;
use Model\FuenteFinanciamiento;
use Model\TransferenciaInstitucional;
use Model\SaldoFuenteFinanciamientoVista;

class DetalleFinanciamientoController
{
    public static function index(Router $router)
    {
        $resultado = $_GET['resultado'] ?? null;
        $router->render('dfinanciamiento/crear', [
            'resultado' => $resultado
        ]);
    }

    /**
     * Vincular fuentes a un programa (sobres) y capturar la transferencia al
     * programa Institucional (migr. 033). Tres acciones por POST sobre las cards:
     *   1) transferencia[...]           → actualizar/eliminar la transferencia del par
     *   2) detalle_financiamiento con vínculo existente → "Quitar" el sobre
     *   3) detalle_financiamiento sin vínculo → crear el sobre (+ transferencia opcional)
     */
    public static function crear(Router $router)
    {
        $resuls = [];
        $resultado = $_GET['resultado'] ?? null;
        $prgma_id = $_GET['programa_id'] ?? null;
        $detallefinanciamiento = new DetalleFinanciamiento();
        $programas = Programa::all();
        $fuentesfinanciamiento = FuenteFinanciamiento::all();
        $errores = DetalleFinanciamiento::getErrores();

        if ($prgma_id) {
            // El Institucional no es destino válido: sobre derivado (Σ transferencias).
            $programaActual = Programa::find($prgma_id);
            if ($programaActual && $programaActual->esInstitucional()) {
                header("Location: /dfinanciamiento/crear?resultado=26");
                exit();
            }

            $resuls = DetalleFinanciamiento::findwithtableforanea($prgma_id);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                // ---- Acción 1: actualizar/eliminar la transferencia al Institucional ----
                if (isset($_POST['transferencia'])) {
                    $errores = self::procesarTransferencia((int) $prgma_id, $_POST['transferencia']);
                    if (empty($errores)) {
                        header("Location: /dfinanciamiento/crear?programa_id={$prgma_id}&resultado=2");
                        exit();
                    }
                } else {
                    $ffId = (int) ($_POST['detalle_financiamiento']['fuente_financiamiento_id'] ?? 0);
                    $vinculo = $ffId ? DetalleFinanciamiento::porPar((int) $prgma_id, $ffId) : null;

                    // ---- Acción 2: "Quitar" el vínculo (sobre) existente ----
                    if ($vinculo && validarTipoContenido($_POST['detalle_financiamiento']['tipo'] ?? '')) {
                        // La transferencia del par cae junto con el sobre: guard de
                        // reducción primero (el Institucional pudo ya gastar ese dinero).
                        $transferencia = TransferenciaInstitucional::porPar($ffId, (int) $prgma_id);
                        if ($transferencia && !self::reduccionTransferenciaPermitida($ffId, (float) $transferencia->monto)) {
                            header("Location: /dfinanciamiento/crear?programa_id={$prgma_id}&resultado=27");
                            exit();
                        }
                        DetalleFinanciamiento::setUsuarioActual();
                        $vinculo->eliminarsinRedireccion();
                        if ($transferencia) {
                            $transferencia->eliminarsinRedireccion();
                            TransferenciaInstitucional::sincronizarSobreInstitucional($ffId);
                        }
                        header("Location: /dfinanciamiento/crear?resultado=4");
                        exit();
                    }

                    // ---- Acción 3: crear el vínculo (sobre) + transferencia opcional ----
                    if (!$vinculo) {
                        $detallefinanciamiento = new DetalleFinanciamiento($_POST['detalle_financiamiento']);
                        $errores = $detallefinanciamiento->validar();

                        // Transferencia opcional al Institucional (monto fijo).
                        $montoTransfer = 0.0;
                        $transferInput = trim((string) ($_POST['transferencia_institucional']['monto'] ?? ''));
                        if ($transferInput !== '') {
                            $montoTransfer = montoNumerico($transferInput);
                            if ($montoTransfer === null || $montoTransfer < 0) {
                                $errores[] = 'La transferencia al programa Institucional debe ser un monto válido (solo números)';
                                $montoTransfer = 0.0;
                            } elseif ($montoTransfer > MONTO_MAXIMO) {
                                $errores[] = 'La transferencia al Institucional excede el tope permitido (S/. '
                                    . number_format(MONTO_MAXIMO, 2, '.', ',') . ')';
                            }
                        }

                        // Σ sobres de la fuente (incluido sobre nuevo + transferencia,
                        // que se materializa como sobre del Institucional) ≤ capacidad.
                        if (empty($errores)) {
                            DetalleFinanciamiento::validarLimiteAsignacion(
                                (int) $detallefinanciamiento->fuente_financiamiento_id,
                                (float) $detallefinanciamiento->monto_asignado + $montoTransfer
                            );
                            $errores = DetalleFinanciamiento::getErrores();
                        }

                        if (empty($errores)) {
                            $vali = DetalleFinanciamiento::setUsuarioActual();
                            if ($vali) {
                                $detallefinanciamiento->guardarsinRedireccion();
                                if ($montoTransfer > 0) {
                                    self::upsertTransferencia(
                                        (int) $detallefinanciamiento->fuente_financiamiento_id,
                                        (int) $prgma_id,
                                        $montoTransfer
                                    );
                                }
                                header("Location: /dfinanciamiento/crear?resultado=1");
                                exit();
                            }
                            $errores[] = "Error al asignar el usuario actual.";
                        }
                    }
                }
            }
        }

        // Desglose por fuente (presupuesto y comprometido = Σ sobres) para las cards.
        $desglose = SaldoFuenteFinanciamientoVista::desglosePorFuente();
        // Mapa fuente_id => monto_asignado de los sobres YA vinculados a este programa.
        $vinculos = [];
        foreach ($resuls as $r) {
            $vinculos[(int) $r->fuente_financiamiento_id] = (float) $r->monto_asignado;
        }
        // Mapa fuente_id => monto transferido al Institucional por este programa.
        $transferencias = [];
        if ($prgma_id) {
            foreach (TransferenciaInstitucional::porOrigen((int) $prgma_id) as $t) {
                $transferencias[(int) $t->fuente_financiamiento_id] = (float) $t->monto;
            }
        }
        // Programa seleccionado, resuelto aquí (no depender del leftover del foreach de la vista).
        $programaSeleccionado = null;
        if ($prgma_id) {
            foreach ($programas as $p) {
                if ((string) $p->id === (string) $prgma_id) {
                    $programaSeleccionado = $p;
                    break;
                }
            }
        }

        $router->render('dfinanciamiento/crear', [
            'programas' => $programas,
            'fuentesfinanciamiento' => $fuentesfinanciamiento,
            'detallefinanciamiento' => $detallefinanciamiento,
            'errores' => $errores,
            'resuls' => $resuls,
            'resultado' => $resultado,
            'desglose' => $desglose,
            'vinculos' => $vinculos,
            'transferencias' => $transferencias,
            'programaSeleccionado' => $programaSeleccionado
        ]);
    }

    /**
     * Actualiza (o elimina con monto 0) la transferencia al Institucional del par
     * (fuente, programa origen). Guardas: aumento ≤ capacidad asignable; reducción
     * nunca por debajo de lo ya comprometido por el Institucional en esa fuente.
     * Devuelve la lista de errores (vacía = éxito, con redirect del llamador).
     */
    private static function procesarTransferencia(int $programaId, array $input): array
    {
        $errores = [];
        $ffId = (int) ($input['fuente_financiamiento_id'] ?? 0);
        if (!$ffId || !DetalleFinanciamiento::existeVinculo($programaId, $ffId)) {
            return ['La fuente no está vinculada a este programa.'];
        }

        $monto = montoNumerico(trim((string) ($input['monto'] ?? '')));
        if ($monto === null || $monto < 0) {
            return ['La transferencia debe ser un monto válido (solo números, 0 para quitarla)'];
        }
        if ($monto > MONTO_MAXIMO) {
            return ['La transferencia excede el tope permitido (S/. ' . number_format(MONTO_MAXIMO, 2, '.', ',') . ')'];
        }

        $actual = TransferenciaInstitucional::porPar($ffId, $programaId);
        $montoActual = $actual ? (float) $actual->monto : 0.0;
        $delta = $monto - $montoActual;

        if ($delta > 0) {
            // Aumento: el delta debe caber en la capacidad asignable de la fuente.
            if (!DetalleFinanciamiento::validarLimiteAsignacion($ffId, $delta)) {
                return DetalleFinanciamiento::getErrores();
            }
        } elseif ($delta < 0 && !self::reduccionTransferenciaPermitida($ffId, -$delta)) {
            header("Location: /dfinanciamiento/crear?programa_id={$programaId}&resultado=27");
            exit();
        }

        TransferenciaInstitucional::setUsuarioActual();
        if ($monto <= 0) {
            if ($actual) {
                $actual->eliminarsinRedireccion();
            }
        } else {
            self::upsertTransferencia($ffId, $programaId, $monto);
        }
        TransferenciaInstitucional::sincronizarSobreInstitucional($ffId);
        return $errores;
    }

    /**
     * ¿El sobre del Institucional en la fuente soporta una reducción de $montoReduccion?
     * (asignado − reducción) + ingresos − egresos − rendiciones ≥ 0. Si el Institucional
     * no existe o no tiene sobre en la fuente, la reducción es trivialmente válida.
     */
    private static function reduccionTransferenciaPermitida(int $ffId, float $montoReduccion): bool
    {
        $inst = Programa::institucional();
        if (!$inst) {
            return true;
        }
        $saldo = DetalleFinanciamiento::saldoSobre((int) $inst->id, $ffId);
        return ($saldo['disponible'] - $montoReduccion) >= -0.001;
    }

    /** Crea o actualiza la transferencia del par y sincroniza el sobre del Institucional. */
    private static function upsertTransferencia(int $ffId, int $programaOrigenId, float $monto): void
    {
        $transferencia = TransferenciaInstitucional::porPar($ffId, $programaOrigenId);
        if ($transferencia) {
            $transferencia->monto = $monto;
            $transferencia->actualizarsinRedireccion();
        } else {
            $transferencia = new TransferenciaInstitucional([
                'fuente_financiamiento_id' => $ffId,
                'programa_origen_id'       => $programaOrigenId,
                'monto'                    => $monto,
            ]);
            $transferencia->crearsinRedireccion();
        }
        TransferenciaInstitucional::sincronizarSobreInstitucional($ffId);
    }
}
