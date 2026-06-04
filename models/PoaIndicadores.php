<?php

namespace Model;

class PoaIndicadores extends ActiveRecord
{
    protected static $tabla = 'poa_indicadores';
    protected static $columnasDB = ['id', 'programa_id', 'usuario_id', 'anio', 'estado', 'observacion', 'fecha'];

    // Estados del documento (mismo flujo que el POA Presupuestal).
    const BORRADOR  = 0;
    const ENVIADO   = 1;
    const OBSERVADO = 2;
    const APROBADO  = 3;

    public $id;
    public $programa_id;
    public $usuario_id;
    public $anio;
    public $estado;
    public $observacion;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->programa_id = $args['programa_id'] ?? null;
        $this->usuario_id = $args['usuario_id'] ?? null;
        $this->anio = $args['anio'] ?? date('Y');
        $this->estado = $args['estado'] ?? self::BORRADOR;
        $this->observacion = $args['observacion'] ?? null;
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->programa_id) {
            self::$errores[] = 'El documento requiere un programa válido';
        }
        if (!$this->usuario_id) {
            self::$errores[] = 'El documento requiere un usuario válido';
        }
        if (!$this->anio) {
            self::$errores[] = 'El documento requiere un año válido';
        }
        return self::$errores;
    }

    // Documento de un programa para un año (uno solo por programa/año). null si no existe.
    public static function porProgramaAnio($programaId, $anio)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE programa_id = ? AND anio = ? LIMIT 1";
        $resultado = self::consultarPreparado($query, 'is', [(int) $programaId, (string) $anio]);
        return array_shift($resultado);
    }

    // ¿El documento admite edición de la jerarquía/indicadores? Sí en Borrador u Observado.
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
}
