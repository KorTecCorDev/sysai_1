<?php

namespace Model;

class Actividad extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'actividad';
    protected static $columnasDB = ['id', 'producto_id', 'codigo', 'nombre', 'descripcion', 'fecha'];

    public $id;
    public $producto_id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->producto_id = $args['producto_id'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->producto_id) {
            self::$errores[] = 'Debes seleccionar un producto válido';
        }
        // El código ya no lo ingresa el usuario: se autogenera (ver siguienteCodigo()).
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido para esta actividad';
        }
        return self::$errores;
    }

    /** Código jerárquico autogenerado dentro del producto: "<cod_producto>.<n>" (p. ej. "1.1.2"). */
    public static function siguienteCodigo(int $productoId): string
    {
        return static::siguienteCodigoJerarquico('producto', $productoId, 'producto_id', 1);
    }
    public function agregarIdtoObjeto(int $id, string $key): object
    {
        $objeto = $this;
        $objeto->$key = $id;
        return $objeto;
    }
}
