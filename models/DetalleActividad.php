<?php

namespace Model;

class DetalleActividad extends ActiveRecord
{
    // Indicadores del POA Indicadores a nivel de actividad. 1 fila por actividad.
    protected static $tabla = 'detalle_actividad';
    protected static $columnasDB = ['id', 'actividad_id', 'indicador_medido', 'medio_verificacion', 'supuesto', 'responsable', 'fecha'];

    public $id;
    public $actividad_id;
    public $indicador_medido;
    public $medio_verificacion;
    public $supuesto;
    public $responsable;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->actividad_id = $args['actividad_id'] ?? null;
        $this->indicador_medido = $args['indicador_medido'] ?? null;
        $this->medio_verificacion = $args['medio_verificacion'] ?? '';
        $this->supuesto = $args['supuesto'] ?? '';
        $this->responsable = $args['responsable'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->actividad_id) {
            self::$errores[] = 'El indicador requiere una actividad válida';
        }
        if ($this->indicador_medido === '' || $this->indicador_medido === null) {
            self::$errores[] = 'Debe ingresar la meta del indicador (indicador medido)';
        }
        if (!$this->responsable) {
            self::$errores[] = 'Debe indicar el responsable';
        }
        return self::$errores;
    }

    // Indicador (único) de una actividad. null si aún no se capturó.
    public static function porActividad($actividadId)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE actividad_id = ? LIMIT 1";
        $resultado = self::consultarPreparado($query, 'i', [(int) $actividadId]);
        return array_shift($resultado);
    }

    public function agregarIdtoObjeto(int $id, string $key): object
    {
        $objeto = $this;
        $objeto->$key = $id;
        return $objeto;
    }
}
