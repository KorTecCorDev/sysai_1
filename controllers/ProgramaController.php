<?php

namespace Controllers;

use MVC\Router;
use Model\Persona;

use Model\Programa;
use Model\TipoPrograma;
use Model\UsuariosCoordinadorVista;
use Model\UsuarioDisponiblePrograma;

class ProgramaController
{
    public static function index(Router $router)
    {
        $programas = Programa::all();
        //Seleccionamos los tipos de programa
        $tiposprograma = TipoPrograma::all();
        //Mostrando el mensaje condicional
        $resultado = $_GET['resultado'] ?? null;

        $router->render('programa/admin', [
            'programas' => $programas,
            'tiposprograma' => $tiposprograma,
            'resultado' => $resultado
        ]);
    }

    public static function crear(Router $router)
    {
        //Instanciamos siempre necesariamente antes del POST
        $programa = new Programa();
        //Seleccionamos los tipos de programa
        $tiposprograma = TipoPrograma::all();
        //Instanciamos un array de objetos de la vista de USUARIOS COORDINADORES DISPONIBLES para ser relacionados con los programas
        $usuariodispo = UsuarioDisponiblePrograma::all();
        //Instanciamos un array de objetos de la tabla personas para poder sacar los datos personales de los coordinadores
        $personas = Persona::all();
        //Array con mensajes de error
        $errores = Programa::getErrores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Creamos una nueva instancia
            $programa = new Programa($_POST['programa']);
            //Validamos
            $errores = $programa->validar();
            //Si no hubiera errores...
            if (empty($errores)) {
                //Guardando en la base de datos
                $resultado = $programa->guardar();
            }
        }
        $router->render('programa/crear', [
            'programa' => $programa,
            'tiposprograma' => $tiposprograma,
            'usuariodispo' => $usuariodispo,
            'personas' => $personas,
            'errores' => $errores
        ]);
    }

    public static function actualizar(Router $router)
    {
        $id = validarORedireccionar('/programa/admin');
        $programa = Programa::find($id);
        //Instanciamos un array de objetos de la tabla personas para poder sacar los datos personales de los coordinadores
        $personas = Persona::all();
        //Seleccionamos los tipos de programa
        $tiposprograma = TipoPrograma::all();
        //Instanciamos un array de objetos de la vista de USUARIOS COORDINADORES DISPONIBLES para ser relacionados con los programas
        $usuariodispo = UsuarioDisponiblePrograma::all();
        //Instanciamos un array de objetos de la vista USUARIO COORDINADORES GENERAL para ser mostrados en el combo
        $usuarioscoordinador = UsuariosCoordinadorVista::all();
        //Errores
        $errores = Programa::getErrores();

        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            //Asignamos atributos
            $args = $_POST['programa'];
            //Sincronizamos
            $programa->sincronizar($args);
            //Validamos
            $errores = $programa->validar();
            //Si no existieran errores...
            if (empty($errores)) {
                //Guardando en la base de datos
                $resultado = $programa->guardar();
            }
        }
        $router->render('programa/actualizar', [
            'programa' => $programa,
            'tiposprograma' => $tiposprograma,
            'usuarioscoordinador' => $usuarioscoordinador,
            'usuariodispo' => $usuariodispo,
            'personas' => $personas,
            'errores' => $errores
        ]);
    }
    public static function eliminar(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            //Verificamos que el dato enviado sea del tipo correcto
            $idprograma = filter_var($id, FILTER_VALIDATE_INT);
            if ($idprograma) {
                //Verificamos que el tipo dentro del POST sea el correcto
                $tipo = $_POST['tipo'];
                if (validarTipoContenido($tipo)) {
                    $programa = Programa::find($idprograma);
                    if ($programa) {
                        $programa->eliminar();
                        header("Location: /programa/admin?resultado=3");
                        exit();
                    }
                }
            }
        }
    }
}
