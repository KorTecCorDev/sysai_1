<?php

namespace Model;

class IngresoEgresoAdminVista extends ActiveRecord
{
    // Declarando variables (vista otros_ingresos_egresos_admin_vista, migr. 022:
    // LEFT JOIN a programa/fuente para incluir ingresos al total de la fuente)
    protected static $tabla = 'otros_ingresos_egresos_admin_vista';
    protected static $columnasDB = ['id', 'codigo', 'descripcion', 'oie_tipo_id', 'tipo', 'programa_id', 'programa_nombre', 'fuente_codigo', 'fuente_nombre', 'tipo_comprobante_codigo', 'comprobante_monto', 'comprobante_fecha'];

    public $id;
    public $codigo;
    public $descripcion;
    public $oie_tipo_id;
    public $tipo;
    public $programa_id;
    public $programa_nombre;
    public $fuente_codigo;
    public $fuente_nombre;
    public $tipo_comprobante_codigo;
    public $comprobante_monto;
    public $comprobante_fecha;
}
