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
            $poa = new Poa($_POST['poa']);

            //Colocamos el hasheo para los password
            $usuario->password = password_hash($usuario->password, PASSWORD_DEFAULT);

            //Validamos
            $errores = $persona->validar();
            $errores = $usuario->validar();
            if ($_POST['poa']) {
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
                if ($poa) {
                    //Guardando en la tabla poa
                    $poa->usuario_id = $usuario->devolverIdLastInsercion();
                    $resultado = $poa->guardarsinRedireccion();
                    header("Location: /usuario/admin?resultado=1");
                    exit();
                }
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
        //Debugueando y probando
        //debuguear($_GET);
        //Fin de sección
        $id = validarORedireccionar('/usuario/admin');
        $usuario = Usuario::find($id);
        //Seleccionamos el poa por usuario vinculado (si existe)
        $poa = Poa::findxatributo('usuario_id', $id) ?? null;
        //debuguear($usuario);
        // Este usuario es un coodinador?
        $coordinador = $usuario->comprobarCoordinador($id);

        //Si es coordinador...
        if ($coordinador != 0) {
            //Encontramos el programa_id vinculado al coordinador
            $poaprograma = new Poa();
            $programa_id = $poaprograma->findProgramaxUsuario($id);
            //Seleccionamos el objeto dentro del array
            $objprograma = array_shift($programa_id);

            //Asignamos a una sola variable el id del programa del coordinador
            $p_id = $objprograma->programa_id;

            //Encontramos el objeto Programa con el id
            $objtprograma = Programa::find($p_id);

            //Añadimos el objeto al array del select de programas disponibles
            $programas[] = $objtprograma;
        }
        $idpersona = $usuario->persona_id;
        $persona = Persona::find($idpersona);
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

        //Errores
        $errores = Persona::getErrores();
        $errores = Usuario::getErrores();


        if ($_SERVER["REQUEST_METHOD"] === 'POST') {
            //Asignando los atributos
            $argsusuario = $_POST['usuario'];
            $argspersona = $_POST['persona'];
            $usuario->sincronizar($argsusuario);
            $usuario->password = password_hash($argsusuario->password, PASSWORD_DEFAULT);
            $persona->sincronizar($argspersona);
            //Asignamos los atributos para el objeto poa
            if ($_POST['poa']) {
                $argspoa = $_POST['poa'];
                $objprograma->sincronizar($argspoa);
            }
            //Validación
            $errores = $persona->validar();
            $errores = $usuario->validar();
            //Antes de insertar los datos deberemos de validar que el array de errores esté vacío
            if (empty($errores)) {
                //Guardando en la base de datos
                //Si existiera un poa vnculado al usuario, se eliminará
                if (isset($poa)) {
                    $resultado = $objprograma->guardarsinRedireccion();
                }
                //Guardamos los registros en las tablas persona y usuario
                $resultado = $persona->guardarsinRedireccion();
                $resultado = $usuario->guardar();
            }
        }
        $router->render('usuario/actualizar', [
            'persona' => $persona,
            'programas' => $programas,
            'usuario' => $usuario,
            'errores' => $errores,
            'cmbstatus' => $cmbstatus,
            'cargos' => $cargos

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
                        if (isset($poa)) {
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
