<?php

namespace Model;

class CoordinadorPrograma extends ActiveRecord
{
    protected static $tabla = 'coordinador_programa';
    protected static $columnasDB = ['id', 'usuario_id', 'programa_id', 'activo', 'fecha'];

    public $id;
    public $usuario_id;
    public $programa_id;
    public $activo;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->usuario_id = $args['usuario_id'] ?? null;
        $this->programa_id = $args['programa_id'] ?? null;
        $this->activo = $args['activo'] ?? 1;
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->usuario_id) {
            self::$errores[] = 'El vínculo requiere un usuario válido';
        }
        if (!$this->programa_id) {
            self::$errores[] = 'Debe seleccionar un programa válido';
        }
        return self::$errores;
    }

    // Vínculo ACTIVO del coordinador (o null). Regla: un coordinador = un solo
    // programa activo, así que como mucho hay una fila.
    public static function vinculoActivoPorUsuario($usuarioId)
    {
        $query = "SELECT * FROM " . static::$tabla . " WHERE usuario_id = ? AND activo = 1 LIMIT 1";
        $resultado = self::consultarPreparado($query, 'i', [(int) $usuarioId]);
        return array_shift($resultado);
    }

    // Desactiva los vínculos activos de un coordinador (al quitarle el programa
    // o antes de reasignarle otro).
    public static function desactivarPorUsuario($usuarioId): bool
    {
        return self::ejecutarPreparado(
            "UPDATE " . static::$tabla . " SET activo = 0 WHERE usuario_id = ? AND activo = 1",
            'i',
            [(int) $usuarioId]
        );
    }

    // Desactiva el vínculo activo de un programa (al reemplazar su coordinador).
    public static function desactivarPorPrograma($programaId): bool
    {
        return self::ejecutarPreparado(
            "UPDATE " . static::$tabla . " SET activo = 0 WHERE programa_id = ? AND activo = 1",
            'i',
            [(int) $programaId]
        );
    }

    /**
     * Asigna un programa a un coordinador respetando ambas invariantes:
     *  - un coordinador tiene un solo programa activo;
     *  - un programa tiene un solo coordinador activo.
     * Desactiva los vínculos activos previos (del usuario y del programa) y crea
     * el nuevo vínculo activo. Idempotente: si el vínculo activo ya existe con el
     * mismo programa, no hace nada.
     *
     * @return bool true si quedó asignado (o ya lo estaba).
     */
    public static function asignarPrograma($usuarioId, $programaId): bool
    {
        $usuarioId  = (int) $usuarioId;
        $programaId = (int) $programaId;
        if ($usuarioId <= 0 || $programaId <= 0) {
            return false;
        }

        // ¿Ya está vinculado activamente a ese mismo programa? No hacer nada.
        $actual = self::vinculoActivoPorUsuario($usuarioId);
        if ($actual && (int) $actual->programa_id === $programaId) {
            return true;
        }

        // Liberar el programa de su coordinador anterior y al coordinador de su
        // programa anterior, luego crear el nuevo vínculo activo.
        self::desactivarPorPrograma($programaId);
        self::desactivarPorUsuario($usuarioId);

        $vinculo = new self([
            'usuario_id'  => $usuarioId,
            'programa_id' => $programaId,
            'activo'      => 1,
        ]);
        return (bool) $vinculo->guardarsinRedireccion();
    }

    // Elimina (hard delete) todos los vínculos de un usuario. Necesario antes de
    // borrar el usuario por la FK coordinador_programa.usuario_id → usuario.id.
    public static function eliminarPorUsuario($usuarioId): bool
    {
        return self::ejecutarPreparado(
            "DELETE FROM " . static::$tabla . " WHERE usuario_id = ?",
            'i',
            [(int) $usuarioId]
        );
    }
}
