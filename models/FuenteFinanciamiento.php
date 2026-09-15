<?php

namespace Model;

class FuenteFinanciamiento extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'fuente_financiamiento';
    protected static $columnasDB = ['id', 'codigo', 'nombre', 'descripcion', 'presupuesto', 'fecha'];

    public $id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $presupuesto;
    public $fecha;


    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->presupuesto = $args['presupuesto'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        // Normalización única de dinero (plan de montos, Fase 0): tolera "S/", comas de
        // miles y espacios; lo no numérico se vuelve null => error visible, nunca un
        // clampeo silencioso de MariaDB (antes "1,500,000" se guardaba como 1.00).
        $this->presupuesto = montoNumerico($this->presupuesto);
        // El código ya no lo ingresa el usuario: se autogenera (ver siguienteCodigo()).
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido';
        }
        if ($this->presupuesto === null || $this->presupuesto <= 0) {
            self::$errores[] = 'Debes añadir un monto de presupuesto válido (solo números, mayor a 0)';
        } elseif ($this->presupuesto > MONTO_MAXIMO) {
            self::$errores[] = 'El presupuesto excede el tope permitido (S/. '
                . number_format(MONTO_MAXIMO, 2, '.', ',') . ')';
        }
        return self::$errores;
    }

    /** Código correlativo autogenerado: FF001, FF002, ... */
    public static function siguienteCodigo(): string
    {
        return static::siguienteCodigoCorrelativo('FF');
    }
}
