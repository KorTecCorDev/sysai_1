<?php

namespace Model;

class Poa extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'poa';
    protected static $columnasDB = ['id', 'programa_id', 'usuario_id', 'anio', 'presupuesto', 'estado', 'observacion', 'fecha'];

    // Estados del documento POA Presupuestal (mismo flujo que el POA Indicadores).
    const BORRADOR  = 0;
    const ENVIADO   = 1;
    const OBSERVADO = 2;
    const APROBADO  = 3;

    public $id;
    public $programa_id;
    public $usuario_id;
    public $anio;
    public $presupuesto;
    public $estado;
    public $observacion;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->programa_id = $args['programa_id'] ?? '';
        $this->usuario_id = $args['usuario_id'] ?? '';
        $this->anio = $args['anio'] ?? date('Y');
        $this->presupuesto = $args['presupuesto'] ?? 0.00;
        $this->estado = $args['estado'] ?? self::BORRADOR;
        $this->observacion = $args['observacion'] ?? null;
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->programa_id) {
            self::$errores[] = 'Debe de seleccionar un programa válido';
        }
        return self::$errores;
    }

    public function findProgramaxUsuario(int $usuario_id)
    {
        $query = "SELECT * FROM poa WHERE usuario_id = {$usuario_id}";
        $resultado = self::consultarSQL($query);
        return $resultado;
    }

    // Documento de un programa para un año (uno solo por programa/año). null si no existe.
    public static function porProgramaAnio($programaId, $anio)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE programa_id = ? AND anio = ? LIMIT 1";
        $resultado = self::consultarPreparado($query, 'is', [(int) $programaId, (string) $anio]);
        return array_shift($resultado);
    }

    // ¿El documento admite edición de rubros/presupuesto? Sí en Borrador u Observado.
    public function esEditable(): bool
    {
        return in_array((int) $this->estado, [self::BORRADOR, self::OBSERVADO], true);
    }

    // Etiqueta legible del estado.
    public static function etiquetaEstado($estado): string
    {
        switch ((int) $estado) {
            case self::BORRADOR:  return 'Borrador';
            case self::ENVIADO:   return 'Enviado';
            case self::OBSERVADO: return 'Observado';
            case self::APROBADO:  return 'Aprobado';
            default:              return 'Desconocido';
        }
    }

    // Presupuesto del POA = Σ de los montos de los rubros del programa (en S/ base).
    // La jerarquía rubro → actividad → producto → resultado → programa da la imputación.
    public static function presupuestoCalculado($programaId): float
    {
        // Lectura directa del escalar: consultarPreparado() pasa cada fila por
        // crearObjeto(), que descarta columnas fuera de $columnasDB (como el alias
        // de la agregación), así que aquí se lee el SUM con mysqli directo.
        $query = "SELECT COALESCE(SUM(r.monto), 0) AS total
                  FROM rubro r
                  JOIN actividad a ON a.id = r.actividad_id
                  JOIN producto p ON p.id = a.producto_id
                  JOIN resultado re ON re.id = p.resultado_id
                  WHERE re.programa_id = ?";
        $stmt = self::$db->prepare($query);
        if ($stmt === false) {
            return 0.0;
        }
        $pid = (int) $programaId;
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $res = $stmt->get_result();
        $fila = ($res instanceof \mysqli_result) ? $res->fetch_assoc() : null;
        $stmt->close();
        return (float) ($fila['total'] ?? 0);
    }

    // Tope del POA = Σ de los sobres del programa (detalle_financiamiento.monto_asignado).
    // Es un tope AGREGADO, no por fuente: rubro no tiene ff_id, así que un rubro no sabe
    // de qué sobre sale (decisión 2026-07-15). Escalar leído con mysqli directo (ver
    // presupuestoCalculado sobre por qué no sirve consultarPreparado aquí).
    public static function topeSobres(int $programaId): float
    {
        $query = "SELECT COALESCE(SUM(monto_asignado), 0) AS total
                  FROM detalle_financiamiento
                  WHERE programa_id = ?";
        $stmt = self::$db->prepare($query);
        if ($stmt === false) {
            return 0.0;
        }
        $stmt->bind_param('i', $programaId);
        $stmt->execute();
        $res = $stmt->get_result();
        $fila = ($res instanceof \mysqli_result) ? $res->fetch_assoc() : null;
        $stmt->close();
        return (float) ($fila['total'] ?? 0);
    }

    /**
     * Tope del POA por sobres: Σ rubro.monto ≤ Σ monto_asignado del programa.
     * Se valida al ENVIAR y también al APROBAR (los sobres pueden bajar entre el
     * envío y la aprobación). Con Σ sobres = 0 el tope es 0 y todo lo excede, por
     * eso el mensaje distingue "aún no tienes sobres" de "excediste el tope".
     * Agrega el error a self::$errores y devuelve el desglose para la UI.
     *
     * @return array{ok:bool, rubros:float, sobres:float, margen:float}
     */
    public static function validarTopeSobres(int $programaId): array
    {
        $rubros = self::presupuestoCalculado($programaId);
        $sobres = self::topeSobres($programaId);
        $ok = $rubros <= $sobres + 0.001;
        if (!$ok) {
            if ($sobres <= 0) {
                self::$errores[] = 'El programa aún no tiene sobres asignados: el Contador debe '
                    . 'asignar presupuesto (fuente↔programa) antes de enviar o aprobar el POA.';
            } else {
                self::$errores[] = 'El POA (S/. ' . number_format($rubros, 2, '.', ',')
                    . ') supera la suma de los sobres del programa (S/. ' . number_format($sobres, 2, '.', ',')
                    . '). Excede por S/. ' . number_format($rubros - $sobres, 2, '.', ',') . '.';
            }
        }
        return ['ok' => $ok, 'rubros' => $rubros, 'sobres' => $sobres, 'margen' => $sobres - $rubros];
    }
}
