<?php

namespace Model;

/**
 * Vista `reporte_rendiciones` (migr. 035): una rendición por fila, con su
 * rubro, su programa y su estado.
 *
 * La vista NO filtra por estado a propósito: el reporte oficial muestra solo
 * APROBADAS (decisión D3) y ese filtro vive aquí, en `aprobadasEnRango()`, para
 * que la misma vista pueda alimentar mañana un reporte de pendientes sin
 * duplicar el SQL.
 */
class ReporteRendicionesVista extends ActiveRecord
{
    protected static $tabla = 'reporte_rendiciones';
    protected static $columnasDB = [
        'rendicion_id',
        'rendicion_fecha',
        'rendicion_codigo',
        'rendicion_descripcion',
        'rendicion_tipo_comprobante_id',
        'tipo_comprobante_codigo',
        'fuente_financiamiento_id',
        'fuente_financiamiento_codigo',
        'rendicion_fecha_original',
        'rendicion_ruc',
        'rendicion_razon_social',
        'rendicion_serie',
        'rendicion_numero',
        'rendicion_detalle',
        'rendicion_comprobante_monto',
        'rendicion_monto_usd',
        'rendicion_monto_eur',
        'rendicion_estado',
        'rubro_id',
        'rubro_codigo',
        'rubro_nombre',
        'programa_id',
        'programa_codigo',
        'programa_nombre',
        'fuente_financiamiento_nombre',
        'tipo_comprobante_descripcion',
    ];

    public $rendicion_id;
    public $rendicion_fecha;                 // fecha de REGISTRO en el sistema
    public $rendicion_codigo;
    public $rendicion_descripcion;
    public $rendicion_tipo_comprobante_id;
    public $tipo_comprobante_codigo;
    public $fuente_financiamiento_id;
    public $fuente_financiamiento_codigo;
    public $rendicion_fecha_original;        // fecha de OPERACIÓN — la que filtra y se imprime
    public $rendicion_ruc;
    public $rendicion_razon_social;
    public $rendicion_serie;
    public $rendicion_numero;
    public $rendicion_detalle;
    public $rendicion_comprobante_monto;
    // Conversión con TC congelado por fila (migr. 030). NULL = sin cobertura ("—").
    public $rendicion_monto_usd;
    public $rendicion_monto_eur;
    public $rendicion_estado;                // 0 = Pendiente, 1 = Aprobada
    public $rubro_id;
    public $rubro_codigo;
    public $rubro_nombre;
    public $programa_id;
    public $programa_codigo;
    public $programa_nombre;
    public $fuente_financiamiento_nombre;
    public $tipo_comprobante_descripcion;

    /**
     * Rendiciones APROBADAS cuya FECHA DE OPERACIÓN cae en el rango.
     *
     * Una pendiente no descuenta el presupuesto contable y todavía puede ser
     * observada o modificada: mezclarla con las aprobadas en un documento de
     * rendición de cuentas daba un total que no correspondía a nada. Además
     * `reporte_poa_rendicion` (migr. 034) ya cuenta solo aprobadas — así los dos
     * Excel del sistema dejan de contradecirse.
     */
    public static function aprobadasEnRango(string $desde, string $hasta): array
    {
        $query = "SELECT * FROM " . static::$tabla . "
                  WHERE rendicion_estado = " . Rendicion::APROBADA . "
                    AND rendicion_fecha_original BETWEEN ? AND ?
                  ORDER BY fuente_financiamiento_codigo, rendicion_fecha_original, rendicion_codigo";
        return self::consultarPreparado($query, 'ss', [$desde, $hasta]);
    }
}
