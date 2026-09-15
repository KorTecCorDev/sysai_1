<?php

namespace Model;

/**
 * Vista `reporte_egresos` (migr. 035): un OIE de tipo EGRESO por fila.
 *
 * Gemela de ReporteIngresosVista: mismas columnas en el mismo orden.
 *
 * ⚠️ Hasta la migr. 035 esta clase declaraba `$otros_ingresos_oie_tipo_id`
 * mientras la vista devolvía `otros_ingresos_egresos_oie_tipo_id` (faltaba
 * "egresos_"). `crearObjeto()` asigna por `property_exists`, así que el valor se
 * descartaba en silencio y la propiedad quedaba siempre NULL.
 */
class ReporteEgresosVista extends ActiveRecord
{
    protected static $tabla = 'reporte_egresos';
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

    /** Egresos cuya FECHA DE OPERACIÓN cae en el rango (decisión D4). */
    public static function enRango(string $desde, string $hasta): array
    {
        $query = "SELECT * FROM " . static::$tabla . "
                  WHERE oie_comprobante_fecha_original BETWEEN ? AND ?
                  ORDER BY fuente_financiamiento_codigo, oie_comprobante_fecha_original, otros_ingresos_egresos_codigo";
        return self::consultarPreparado($query, 'ss', [$desde, $hasta]);
    }
}
