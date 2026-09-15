<?php

namespace Model;

class Producto extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'producto';
    protected static $columnasDB = ['id', 'resultado_id', 'codigo', 'nombre', 'descripcion', 'fecha'];

    public $id;
    public $resultado_id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $fecha;

    //Funciones 
    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->resultado_id = $args['resultado_id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->resultado_id) {
            self::$errores[] = 'Debe de seleccionar un resultado válido';
        }
        // El código ya no lo ingresa el usuario: se autogenera (ver siguienteCodigo()).
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido para el resultado';
        }
        return self::$errores;
    }

    /** Código jerárquico autogenerado dentro del resultado: "<cod_resultado>.<n>" (p. ej. "1.2"). */
    public static function siguienteCodigo(int $resultadoId): string
    {
        return static::siguienteCodigoJerarquico('resultado', $resultadoId, 'resultado_id', 1);
    }
    public function agregarIdtoObjeto(int $id, string $key): object
    {
        $objeto = $this;
        $objeto->$key = $id;
        return $objeto;
    }
}
