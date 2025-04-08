<?php

namespace Controllers;

use Model\Poa;

use MVC\Router;
use Model\Cargo;
use Model\Persona;
use Model\Usuario;
use Model\Programa;
use Model\UsuarioVista;
use Model\ProgramasinCoordinadorVista;

class UsuarioController
{
    public static function index(Router $router)
    {
        $usuarios = UsuarioVista::all();
        //Mostrando el mensaje condicional
        $resultado = $_GET['resultado'] ?? null;
        $router->render('usuario/admin', [
            'usuarios' => $usuarios,
            'resultado' => $resultado
        ]);
    }

    public static function crear(Router $router)
    {
        $persona = new Persona();
        $usuario = new Usuario();
        $cargos = Cargo::all();
        //Array con los programas que no tienen un coordinador vinculado
        $programas_vista = ProgramasinCoordinadorVista::all();
        // Si todos los programas tienen coordinador?
        if (empty($programas_vista)) {
            //Variable que se envía para deshabilitar el option de coordinador en tipo de usuario
            $cmbstatus = true;
            //Creando array vací de programas
            $programas = [];
        } else {
            foreach ($programas_vista as $programa_vista) {
                //Encontramos los objetos de programas según los programa_id en cada resultado
                $programas[] = Programa::find($programa_vista->programa_id);
            }
            $cmbstatus = false;
        }

        //Array con mensajes de error
        $errores = Persona::getErrores();
        $errores = Usuario::getErrores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Creamos una nueva instancia
            $persona = new Persona($_POST['persona']);
            $usuario = new Usuario($_POST['usuario']);
            //Si existe un programa ingresado en el SELECT, es un coordinador!
            if (isset($_POST['poa'])) {
                $poa = new Poa($_POST['poa']);
            }
            //Colocamos el hasheo para los password
            $usuario->password = password_hash($usuario->password, PASSWORD_DEFAULT);

            //Validamos
            $errores = $persona->validar();
            $errores = $usuario->validar();
            if (isset($_POST['poa'])) {
                $errores = $poa->validar();
            }
            //Antes de insertar los datos deberemos de validar que el array de errores esté vacío
            if (empty($errores)) {
                //Guardando en la base de datos
                //Guardando en la tala persona
                $resultado = $persona->guardarsinRedireccion();
                $idpersona = $persona->devolverIdLastInsercion();
                //Guardando en la tabla usuario
                $usuario->persona_id = $idpersona;
                $resultado = $usuario->guardarsinRedireccion();

                //Si existe una selección en coordinador
                if (!empty($poa)) {
                    //Guardando en la tabla poa
                    $poa->usuario_id = $usuario->devolverIdLastInsercion();
                    $resultado = $poa->guardarsinRedireccion();
                }
                header("Location: /usuario/admin?resultado=1");
                exit();
            }
        }
        $router->render('usuario/crear', [
            'persona' => $persona,
            'programas' => $programas,
            'usuario' => $usuario,
            'cargos' => $cargos,
            'cmbstatus' => $cmbstatus,
            'errores' => $errores
        ]);
    }

    public static function actualizar(Router $router)
    {
        $id = validarORedireccionar('/usuario/admin');
        $usuario = Usuario::find($id);
        $idpersona = $usuario->persona_id;
        $persona = Persona::find($idpersona);
        $objpoa = new Poa();
        $cargos = Cargo::all();
        //Array con los poas que están vinculados al usuario
        $poa = [];
        //Valor por defecto para el select de programas sin coordinador
        $cmbstatus = false;
        $programas = ProgramasinCoordinadorVista::all();
        //El usuario es un coordinador?
        $valor = $usuario->comprobarCoordinador();
        if ($valor === 1) {
            //Encontramos el array con los poas vinculados al usuario
            $poa = Poa::findxatributo('usuario_id', $id);
            //Recorremos el array y le asignamos los datos del programa
            foreach ($poa as $p) {
                //Encontramos el programa según el programa_id del objeto poa que estamos recorriendo
                $programapoa = Programa::find($p->programa_id);
                //Sacamos el objeto del array
                $objpoa->sincronizar(array_shift($poa));

                $poaprograma = new ProgramasinCoordinadorVista(["programa_id" => $programapoa->id, "programa_codigo" => $programapoa->codigo,  "programa_nombre" => $programapoa->nombre]);

                $programas[] = $poaprograma;
            }
            //Al ser un coordinador, se habilitará automáticamente el select de programas sin coordinador
            $cmbstatus = true;
        }
        //Recogemos errores
        $errores = Persona::getErrores();
        $errores = Usuario::getErrores();
        $errores = Poa::getErrores();
        //Si es post
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $argspersona = $_POST['persona'];
            $argsusuario = $_POST['usuario'];
            $argspoa = ($_POST['poa']['programa_id'] != 0) ? $_POST['poa'] : null;
            //Sincronizamos los objetos
            $persona->sincronizar($argspersona);
            $usuario->sincronizar($argsusuario);
            //Si existe un poa, sincronizamos el objeto
            if (isset($argspoa)) {
                //Guardamos los cambios en los objetos persona y usuario
                $persona->guardarsinRedireccion();
                $usuario->guardarsinRedireccion();
                //Extraemos el objeto del array y lo sincronizamos con el objeto creado
                $objpoa->sincronizar($argspoa);
                //Añadimos el usuario_id al objeto poa
                $objpoa->usuario_id = $usuario->id;
                //Guardamos el poa
                $objpoa->guardarsinRedireccion();
                //Redirigimos hacie /usuario/admin con mensaje de actualización exitosa
                header("Location: /usuario/admin?resultado=2");
                exit();
            }
            //Si es un coordinador y no existe un $_POST['poa'] significa que se ha eliminado el poa
            else if ($valor === 1) {
                //Guardamos los cambios en los objetos persona y usuario
                $persona->guardarsinRedireccion();
                $usuario->guardarsinRedireccion();
                //Eliminamos el poa
                $objpoa->eliminarsinRedireccion();
                //Redirigimos hacie /usuario/admin con mensaje de actualización exitosa
                header("Location: /usuario/admin?resultado=2");
                exit();
            } else {
                //Guardamos los cambios en los objetos persona y usuario
                $persona->guardarsinRedireccion();
                $usuario->guardarsinRedireccion();
                //Redirigimos hacie /usuario/admin con mensaje de actualización exitosa
                header("Location: /usuario/admin?resultado=2");
                exit();
            }
        }
        $router->render('usuario/actualizar', [
            'persona' => $persona,
            'programas' => $programas,
            'usuario' => $usuario,
            'cargos' => $cargos,
            'cmbstatus' => $cmbstatus,
            'errores' => $errores,
            'poa' => $poa,
            'objpoa' => $objpoa
        ]);
    }
    public static function eliminar(Router $router)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            //Las validaciones de datos nos permitirán ejecutar la instrucción, solamente con el dato requerido
            $idusuario = filter_var($id, FILTER_VALIDATE_INT);
            if ($idusuario) {
                $tipo = $_POST['tipo'];
                if (validarTipoContenido($tipo)) {
                    $usuario = Usuario::find($idusuario);
                    //Seleccionamos el persona_id
                    $idpersona = filter_var($usuario->persona_id, FILTER_VALIDATE_INT);
                    //Seleccionamos el poa_id, en caso no exista, se asignará un valor nulo
                    $poa = Poa::findxatributo('usuario_id', $idusuario) ?? null;
                    $persona = Persona::find($idpersona);
                    if ($idpersona) {
                        //Si existe un poa vinculado al usuario, se eliminará
                        if (!empty($poa)) {
                            //Seleccionamos el objeto dentro del array
                            $objpoa = array_shift($poa);
                            //Eliminamos el poa
                            $objpoa->eliminarsinRedireccion();
                        }
                        $usuario->eliminarsinRedireccion();
                        $persona->eliminar();

                        //La redirección de la URL en este caso va fuera de la función, para evitar que se redirija mal
                        header("Location: /usuario/admin?resultado=3");
                        exit();
                    }
                }
            }
        }
    }
}
