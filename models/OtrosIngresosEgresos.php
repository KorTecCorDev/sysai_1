<?php

namespace Model;

class OtrosIngresosEgresos extends ActiveRecord
{
    //Tabla y columnas
    protected static $tabla = 'otros_ingresos_egresos';
    protected static $columnasDB = ['id', 'programa_id', 'oie_comprobante_id', 'oie_tipo_id', 'ff_id', 'codigo', 'descripcion', 'fecha'];

    // programa_id NULL = ingreso al total de la fuente (remanente sin asignar, migr. 020).
    protected static $columnasNull = ['programa_id'];

    //Variables
    public $id;
    public $programa_id;
    public $oie_comprobante_id;
    public $oie_tipo_id;
    public $ff_id;
    public $codigo;
    public $descripcion;
    public $fecha;

    //Constructor
    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->programa_id = ($args['programa_id'] ?? '') !== '' ? $args['programa_id'] : null;
        $this->oie_comprobante_id = $args['oie_comprobante_id'] ?? '';
        $this->oie_tipo_id = $args['oie_tipo_id'] ?? '';
        $this->ff_id = $args['ff_id'] ?? '';
        $this->codigo = $args['codigo'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->fecha = date('Y-m-d H:i:s');
    }

    //Validamos
    public function validar()
    {
        if (!in_array((int) $this->oie_tipo_id, [1, 2], true)) {
            self::$errores[] = 'Debes seleccionar si es un Ingreso o un Egreso';
        }
        if (!$this->ff_id) {
            self::$errores[] = 'Debes seleccionar una fuente de financiamiento válida';
        }
        if (!$this->codigo) {
            self::$errores[] = 'Debes de ingresar un código válido';
        }
        //Validamos que el codigo sea único
        if ($this->existeDato($this, ['codigo'])) {
            self::$errores[] = 'El código ingresado ya existe para otro ingreso o egreso';
        }
        if (!$this->descripcion) {
            self::$errores[] = 'Debes de ingresar una descripción válida';
        }
        // El egreso siempre descuenta del sobre (programa, fuente) → programa obligatorio.
        // El ingreso puede ir al total de la fuente (programa_id NULL) o a un programa.
        if ((int) $this->oie_tipo_id === 2 && !$this->programa_id) {
            self::$errores[] = 'Un egreso debe indicar el programa cuyo sobre se descuenta';
        }
        // Si va dirigido a un programa, el sobre (programa, fuente) debe existir.
        if ($this->programa_id && $this->ff_id
            && !DetalleFinanciamiento::existeVinculo((int) $this->programa_id, (int) $this->ff_id)) {
            self::$errores[] = 'La fuente seleccionada no está vinculada al programa (no existe el sobre)';
        }
        return self::$errores;
    }

    /**
     * Tope por sobre: el egreso no puede exceder el saldo disponible del sobre
     * (programa, fuente). El monto vive en oie_comprobante, por eso se recibe como
     * parámetro. En edición se excluye el propio OIE para no contarlo dos veces.
     * Los ingresos no tienen tope. Agrega el error a self::$errores.
     */
    public function validarTopeSobre(float $monto): bool
    {
        if ((int) $this->oie_tipo_id !== 2 || !$this->programa_id || !$this->ff_id) {
            return true;
        }
        $sobre = DetalleFinanciamiento::saldoSobre(
            (int) $this->programa_id,
            (int) $this->ff_id,
            null,
            $this->id ? (int) $this->id : null
        );
        if ($monto > $sobre['disponible'] + 0.001) {
            self::$errores[] = 'El monto del egreso excede el saldo disponible del sobre '
                . '(sub-presupuesto de la fuente para este programa). Disponible: S/. '
                . number_format(max(0, $sobre['disponible']), 2, '.', ',') . '.';
            return false;
        }
        return true;
    }
}
