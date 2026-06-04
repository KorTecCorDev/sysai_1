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
}
