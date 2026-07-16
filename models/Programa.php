<?php

namespace Model;

class Programa extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'programa';
    protected static $columnasDB = ['id', 'nombre', 'codigo', 'descripcion', 'fecha','tipo_programa_id','es_institucional'];

    public $id;
    public $nombre;
    public $codigo;
    public $descripcion;
    public $fecha;
    public $tipo_programa_id;
    public $es_institucional;

    /** Cache por request del programa Institucional (ver institucional()). */
    private static $institucionalCache = null;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->codigo = $args['codigo'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
        $this->tipo_programa_id = $args['tipo_programa_id'] ?? '';
        // El flag NUNCA viene de formularios (solo migr. 033 / seeds lo fijan);
        // el default 0 protege el alta normal y los controladores lo excluyen del POST.
        $this->es_institucional = $args['es_institucional'] ?? 0;
    }

    public function validar()
    {
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido';
        }
        // El código ya no lo ingresa el usuario: se autogenera (ver siguienteCodigo()).
        if (!$this->tipo_programa_id) {
            self::$errores[] = 'Debes seleccionar un tipo de programa válido';
        }
        return self::$errores;
    }

    /**
     * Siguiente código correlativo autogenerado: PRG-001, PRG-002, ...
     * Estable con huecos: usa MAX(nº)+1 (borrar un programa no reutiliza el hueco).
     * El UNIQUE global de `codigo` es la red de seguridad ante colisiones.
     */
    public static function siguienteCodigo(): string
    {
        return static::siguienteCodigoCorrelativo('PRG');
    }

    /**
     * El programa Institucional (migr. 033): gastos administrativos de la organización.
     * Identificado por el flag es_institucional (nunca por nombre/id — frágiles).
     * Existe siempre (lo crea la migración y lo re-siembran los fixtures); si no
     * existiera devuelve null y el llamador degrada con error visible.
     */
    public static function institucional(): ?Programa
    {
        if (self::$institucionalCache instanceof Programa) {
            return self::$institucionalCache;
        }
        self::$institucionalCache = self::findxatributouno('es_institucional', 1) ?: null;
        return self::$institucionalCache;
    }

    /** ¿Este programa es el Institucional? (comparación robusta del flag) */
    public function esInstitucional(): bool
    {
        return (int) $this->es_institucional === 1;
    }
}
