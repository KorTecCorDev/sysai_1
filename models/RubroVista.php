<?php

namespace Model;

class RubroVista extends ActiveRecord
{
    //Declarando variables
    protected static $tabla = 'rubro_admin_vista';
    protected static $columnasDB = ['id', 'actividad_id', 'categoria_rubro', 'subcategoria_rubro', 'tipo_rubro', 'codigo', 'nombre', 'descripcion', 'monto'];

    public $id;
    public $actividad_id;
    public $categoria_rubro;
    public $subcategoria_rubro;
    public $tipo_rubro;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $monto;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->actividad_id = $args['actividad_id'] ?? null;
        $this->categoria_rubro = $args['categoria_rubro'] ?? null;
        $this->tipo_rubro = $args['tipo_rubro'] ?? null;
        $this->codigo = $args['codigo'] ?? '';
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->monto = $args['monto'] ?? '';
    }
}
