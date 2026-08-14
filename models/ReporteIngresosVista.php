<?php

namespace Model;

/**
 * Vista `reporte_ingresos` (migr. 035): un OIE de tipo INGRESO por fila.
 *
 * Estructuralmente idéntica a ReporteEgresosVista — alimentan el mismo builder
 * y solo difieren en el `oie_tipo_id` que filtra la vista SQL.
 *
 * `programa_*` es el programa DEL MOVIMIENTO y viene por LEFT JOIN: NULL
 * significa "ingreso al remanente de la fuente" (ingreso híbrido), que es un
 * dato del negocio y no una fila incompleta.
 */
class ReporteIngresosVista extends ActiveRecord
{
    protected static $tabla = 'reporte_ingresos';
    protected static $columnasDB = [
        'otros_ingresos_egresos_id',
        'otros_ingresos_egresos_fecha',
        'otros_ingresos_egresos_codigo',
        'otros_ingresos_egresos_descripcion',
        'oie_tipo_comprobante_id',
        'oie_tipo_comprobante_codigo',
        'otros_ingresos_egresos_oie_tipo_id',
        'fuente_financiamiento_id',
        'fuente_financiamiento_codigo',
        'oie_comprobante_fecha_original',
        'oie_comprobante_ruc',
        'oie_comprobante_razon_social',
        'oie_comprobante_serie',
        'oie_comprobante_numero',
        'oie_comprobante_descripcion',
        'oie_comprobante_monto',
        'oie_comprobante_monto_usd',
        'oie_comprobante_monto_eur',
        'programa_id',
        'programa_codigo',
        'programa_nombre',
        'fuente_financiamiento_nombre',
        'oie_tipo_comprobante_nombre',
    ];

    public $otros_ingresos_egresos_id;
    public $otros_ingresos_egresos_fecha;          // fecha de REGISTRO en el sistema
    public $otros_ingresos_egresos_codigo;
    public $otros_ingresos_egresos_descripcion;
    public $oie_tipo_comprobante_id;
    public $oie_tipo_comprobante_codigo;
    public $otros_ingresos_egresos_oie_tipo_id;
    public $fuente_financiamiento_id;
    public $fuente_financiamiento_codigo;
    public $oie_comprobante_fecha_original;        // fecha de OPERACIÓN — la que filtra y se imprime
    public $oie_comprobante_ruc;
    public $oie_comprobante_razon_social;
    public $oie_comprobante_serie;
    public $oie_comprobante_numero;
    public $oie_comprobante_descripcion;
    public $oie_comprobante_monto;
    // Conversión con TC congelado por fila (migr. 030). NULL = sin cobertura ("—").
    public $oie_comprobante_monto_usd;
    public $oie_comprobante_monto_eur;
    public $programa_id;
    public $programa_codigo;
    public $programa_nombre;
    public $fuente_financiamiento_nombre;
    public $oie_tipo_comprobante_nombre;

    /**
     * Ingresos cuya FECHA DE OPERACIÓN cae en el rango (decisión D4).
     *
     * No se filtra por `otros_ingresos_egresos_fecha` —la de registro— porque un
     * comprobante de marzo cargado en agosto pertenece a marzo: es el mismo
     * criterio que congela el tipo de cambio (migr. 029) y el que pide NIC 21.
     */
    public static function enRango(string $desde, string $hasta): array
    {
        $query = "SELECT * FROM " . static::$tabla . "
                  WHERE oie_comprobante_fecha_original BETWEEN ? AND ?
                  ORDER BY fuente_financiamiento_codigo, oie_comprobante_fecha_original, otros_ingresos_egresos_codigo";
        return self::consultarPreparado($query, 'ss', [$desde, $hasta]);
    }
}
