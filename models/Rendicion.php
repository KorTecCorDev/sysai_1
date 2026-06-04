<?php

namespace Model;

class Rendicion extends ActiveRecord
{
    // Declarando variables
    // Nota: `estado` y `poa_rendicion_id` existen en la tabla (migr. 006) pero se omiten
    // aquí a propósito: en INSERT toman su DEFAULT (estado=0, poa_rendicion_id=NULL) y su
    // gestión es parte del flujo del POA Rendición (item 6).
    protected static $tabla = 'rendicion';
    protected static $columnasDB = ['id', 'rubro_id', 'tipo_comprobante_id', 'ff_id', 'codigo', 'serie', 'numero', 'detalle', 'descripcion', 'ruc', 'razon_social', 'monto', 'fecha_original', 'fecha'];

    public $id;
    public $rubro_id;
    public $tipo_comprobante_id;
    public $ff_id;
    public $codigo;
    public $serie;
    public $numero;
    public $detalle;
    public $descripcion;
    public $ruc;
    public $razon_social;
    public $monto;
    public $fecha_original;
    public $fecha;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->rubro_id = $args['rubro_id'] ?? 0;
        $this->tipo_comprobante_id = $args['tipo_comprobante_id'] ?? 0;
        $this->ff_id = $args['ff_id'] ?? 0;
        $this->codigo = $args['codigo'] ?? '';
        $this->serie = $args['serie'] ?? '';
        $this->numero = $args['numero'] ?? '';
        $this->detalle = $args['detalle'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->ruc = $args['ruc'] ?? '';
        $this->razon_social = $args['razon_social'] ?? '';
        $this->monto = $args['monto'] ?? 0.0;
        $this->fecha_original = $args['fecha_original'] ?? '';
        $this->fecha = date('Y/m/d H:i:s');
    }

    public function validar()
    {
        if (!$this->rubro_id) {
            self::$errores[] = 'Debes de seleccionar un rubro válido';
        }
        if (!$this->tipo_comprobante_id) {
            self::$errores[] = 'Debes seleccionar un tipo de comprobante válido';
        }
        if (!$this->ff_id) {
            self::$errores[] = 'Debes seleccionar una fuente de financiamiento válida';
        }
        if (!$this->codigo) {
            self::$errores[] = 'Debes de ingresar un código válido';
        }
        //Validamos que el codigo sea único
        if ($this->existeDato($this, ['codigo'])) {
            self::$errores[] = 'El código ingresado ya existe para otro comprobante';
        }

        if (!$this->serie) {
            self::$errores[] = 'Debes de ingresar una serie de comprobante válida';
        }
        if (!$this->numero) {
            self::$errores[] = 'Debes de ingresar un número de comprobante válido';
        }
        if (!$this->detalle) {
            self::$errores[] = 'Debes de ingresar un detalle de comprobante válido';
        }
        if (!$this->descripcion) {
            self::$errores[] = 'Debes de ingresar un comentario de comprobante válido';
        }
        if (!$this->monto) {
            self::$errores[] = 'Debes de ingresar un monto de comprobante válido';
        }
        if (!$this->fecha_original) {
            self::$errores[] = 'Debes de ingresar una fecha de emisión de comprobante válida';
        }
        return self::$errores;
    }

    // Σ de los montos de las rendiciones imputadas a un rubro. Permite excluir una
    // rendición (en edición) para no contarla dos veces. Lectura escalar directa
    // (consultarPreparado descarta columnas fuera de $columnasDB vía crearObjeto).
    public static function totalImputadoAlRubro($rubroId, $excluirId = null): float
    {
        $query = "SELECT COALESCE(SUM(monto), 0) AS total FROM " . static::$tabla
               . " WHERE rubro_id = ?" . ($excluirId ? " AND id <> ?" : "");
        $stmt = self::$db->prepare($query);
        if ($stmt === false) {
            return 0.0;
        }
        $rid = (int) $rubroId;
        if ($excluirId) {
            $eid = (int) $excluirId;
            $stmt->bind_param('ii', $rid, $eid);
        } else {
            $stmt->bind_param('i', $rid);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $fila = ($res instanceof \mysqli_result) ? $res->fetch_assoc() : null;
        $stmt->close();
        return (float) ($fila['total'] ?? 0);
    }

    // Valida el límite del rubro: Σ rendiciones (incluida la actual) ≤ monto del rubro.
    // Agrega el error a self::$errores si se excede. Devuelve true si está dentro del límite.
    public function validarLimiteRubro($rubroMonto): bool
    {
        $acumulado = self::totalImputadoAlRubro($this->rubro_id, $this->id);
        $nuevoTotal = $acumulado + (float) $this->monto;
        if ($nuevoTotal > (float) $rubroMonto + 0.001) {
            $disponible = max(0, (float) $rubroMonto - $acumulado);
            self::$errores[] = 'El monto excede el saldo del rubro. Disponible: S/. '
                . number_format($disponible, 2, '.', ',')
                . ' (monto del rubro S/. ' . number_format((float) $rubroMonto, 2, '.', ',') . ').';
            return false;
        }
        return true;
    }
}
