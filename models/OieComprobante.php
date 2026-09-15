<?php

namespace Model;

class OieComprobante extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'oie_comprobante';
    protected static $columnasDB = ['id', 'oie_tipo_comprobante_id', 'serie', 'numero', 'descripcion', 'ruc', 'razon_social', 'monto', 'fecha_original', 'tc_usd', 'tc_eur', 'tipo_cambio_usd_id', 'tipo_cambio_eur_id', 'fecha'];
    // TC congelado (migr. 029): NULL real = "pendiente de tipo de cambio", nunca 0.
    protected static $columnasNull = ['tc_usd', 'tc_eur', 'tipo_cambio_usd_id', 'tipo_cambio_eur_id'];

    public $id;
    public $oie_tipo_comprobante_id;
    public $serie;
    public $numero;
    public $descripcion;
    public $ruc;
    public $razon_social;
    public $monto;
    public $fecha_original;
    // TC congelado a la fecha del comprobante: COMPRA si el OIE es ingreso, VENTA si
    // es egreso (§2.5). Valor copiado, no FK (§2.6).
    public $tc_usd;
    public $tc_eur;
    public $tipo_cambio_usd_id;   // solo rastro de procedencia
    public $tipo_cambio_eur_id;
    public $fecha;


    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->oie_tipo_comprobante_id = $args['oie_tipo_comprobante_id'] ?? '';
        $this->serie = $args['serie'] ?? '';
        $this->numero = $args['numero'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->ruc = $args['ruc'] ?? '';
        $this->razon_social = $args['razon_social'] ?? '';
        $this->monto = $args['monto'] ?? '';
        $this->fecha_original = $args['fecha_original'] ?? '';
        $this->tc_usd = $args['tc_usd'] ?? null;
        $this->tc_eur = $args['tc_eur'] ?? null;
        $this->tipo_cambio_usd_id = $args['tipo_cambio_usd_id'] ?? null;
        $this->tipo_cambio_eur_id = $args['tipo_cambio_eur_id'] ?? null;
        $this->fecha = date('Y-m-d H:i:s');
    }

    /**
     * Congela el TC a la fecha del comprobante (migr. 029, §2.2/§2.5): COMPRA para
     * ingresos (oie_tipo_id=1), VENTA para egresos (oie_tipo_id=2). Sin cobertura
     * queda NULL ("pendiente de TC") — el registro no se bloquea; los reportes
     * muestran "—" y cuentan los faltantes.
     */
    public function congelarTipoCambio(int $oieTipoId): void
    {
        $fecha = (string) $this->fecha_original;
        $usd = $fecha !== '' ? TipoCambio::vigente('USD', $fecha) : null;
        $eur = $fecha !== '' ? TipoCambio::vigente('EUR', $fecha) : null;
        $campo = ($oieTipoId === 1) ? 'compra' : 'venta';
        $this->tc_usd = $usd ? (float) $usd->{$campo} : null;
        $this->tipo_cambio_usd_id = $usd ? (int) $usd->id : null;
        $this->tc_eur = $eur ? (float) $eur->{$campo} : null;
        $this->tipo_cambio_eur_id = $eur ? (int) $eur->id : null;
    }

    //Validamos
    public function validar()
    {
        // Normalización única de dinero (plan de montos, Fase 0). El ingreso (donativo)
        // no tiene techo de negocio: solo el tope de cordura MONTO_MAXIMO. El egreso
        // además está acotado por el sobre (OtrosIngresosEgresos::validarTopeSobre).
        $this->monto = montoNumerico($this->monto);
        if (!$this->oie_tipo_comprobante_id) {
            self::$errores[] = "Debe seleccionar un tipo de comprobante válido";
        }

        if (!$this->serie) {
            self::$errores[] = "Debe ingresar la serie del comprobante";
        }

        if (!$this->numero) {
            self::$errores[] = "Debe ingresar el número del comprobante";
        }

        if (!$this->descripcion) {
            self::$errores[] = "Debe de ingresar la descripción del comprobante";
        }

        if (!$this->ruc) {
            self::$errores[] = "Debe ingresar el RUC";
        }

        if (!$this->razon_social) {
            self::$errores[] = "Debe ingresar la razón social";
        }

        if ($this->monto === null || $this->monto <= 0) {
            self::$errores[] = "Debe ingresar el monto (solo números, mayor a 0)";
        } elseif ($this->monto > MONTO_MAXIMO) {
            self::$errores[] = "El monto excede el tope permitido (S/. "
                . number_format(MONTO_MAXIMO, 2, '.', ',') . ")";
        }

        if (!$this->fecha_original) {
            self::$errores[] = "Debe ingresar la fecha del comprobante";
        }

        return self::$errores;
    }
}
