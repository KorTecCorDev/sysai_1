<?php

namespace Controllers;

use MVC\Router;

use Model\CategoriaRubro;
use Model\SubCategoriaRubro;

class CategoriaRubroController
{
    public static function index(Router $router)
    {
        $categoria_rubro = CategoriaRubro::all();
        //Mostrando el mensaje condicional
        $resultado = $_GET['resultado'] ?? null;

        $router->render('categoria_rubro/admin', [
            'categoria_rubro' => $categoria_rubro,
            'resultado' => $resultado
        ]);
    }

    public static function crear(Router $router)
    {
        $categoria_rubro = new CategoriaRubro();
        $subcategorias_rubro = SubCategoriaRubro::all();
        //Array con mensajes de error
        $errores = CategoriaRubro::getErrores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Creamos una nueva instancia
            $categoria_rubro = new CategoriaRubro($_POST['categoria_rubro']);
            
            //Validamos
            $errores = $categoria_rubro->validar();
            //Antes de insertar los datos deberemos de validar que el array de errores esté vacío
            if (empty($errores)) {
                //Guardando en la base de datos
                $resultado = $categoria_rubro->guardar();
            }
        }
        $router->render('categoria_rubro/crear', [
            'categoria_rubro' => $categoria_rubro,
            'subcategorias_rubro' => $subcategorias_rubro,
            'errores' => $errores
        ]);
    }

    public static function actualizar(Router $router)
    {
        $id = validarORedireccionar('/admin');
        $categoria_rubro = CategoriaRubro::find($id);
        $subcategorias_rubro = SubCategoriaRubro::all();
        $errores = CategoriaRubro::getErrores();

        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            //Asignando los atributos
            $args = $_POST['categoria_rubro'];
            $categoria_rubro->sincronizar($args);
            //Validación
            $errores = $categoria_rubro->validar();
            if (empty($errores)) {
                $categoria_rubro->guardar();
            }
        }
        $router->render('categoria_rubro/actualizar',[
            'categoria_rubro' => $categoria_rubro,
            'subcategorias_rubro' => $subcategorias_rubro,
            'errores'=> $errores
        ]);
    }
    public static function eliminar(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            //Las validaciones de datos nos permitirán ejecutar la instrucción, solamente con el dato requerido
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if ($id) {
                
                $tipo=$_POST['tipo'];
                
                if (validarTipoContenido($tipo)) {
                    $categoria_rubro = CategoriaRubro::find($id);
                    $categoria_rubro->eliminar();
                }  
            }
        }
    }
}
