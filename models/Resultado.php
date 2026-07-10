<?php

namespace Model;

class Resultado extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'resultado';
    protected static $columnasDB = ['id', 'programa_id', 'codigo', 'nombre', 'descripcion', 'fecha'];


    public $id;
    public $programa_id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $fecha;




    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->programa_id = $args['programa_id'] ?? '';
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->programa_id) {
            self::$errores[] = 'Debe de seleccionar un programa válido';
        }
        // El código ya no lo ingresa el usuario: se autogenera (ver siguienteCodigo()).
        if (!$this->nombre) {
            self::$errores[] = 'Debes añadir un nombre válido para el resultado';
        }
        return self::$errores;
    }

    /**
     * Siguiente código jerárquico autogenerado dentro del programa: "1", "2", ...
     * (raíz del árbol POA; Producto será "1.1", Actividad "1.1.1", etc.).
     * Estable con huecos: MAX(nº)+1 entre los resultados del mismo programa.
     */
    public static function siguienteCodigo(int $programaId): string
    {
        $n = 0;
        if ($stmt = self::$db->prepare(
            "SELECT COALESCE(MAX(CAST(codigo AS UNSIGNED)), 0) AS m
             FROM " . static::$tabla . " WHERE programa_id = ?"
        )) {
            $stmt->bind_param('i', $programaId);
            $stmt->execute();
            $res = $stmt->get_result();
            $n = (int) ($res->fetch_assoc()['m'] ?? 0);
            $stmt->close();
        }
        return (string) ($n + 1);
    }
    public function agregarProvisional(string $cadena)
    {
        self::$aux[] = $cadena;
        return self::$aux;
    }
    public function quitarProvisional(int $pos)
    {
        array_splice(self::$aux, $pos, 1);
        return self::$aux;
    }
    public function agregarIdtoObjeto(int $id, string $key): object
    {
        $objeto = $this;
        $objeto->$key = $id;
        return $objeto;
    }
}
