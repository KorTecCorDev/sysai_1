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
        //Creamos las nuevas instancias para los objetos a usar posteriormente
        $persona = new Persona();
        $usuario = new Usuario();
        $objpoa = new Poa();
        //Instancias de errores
        //Array con mensajes de error
        $errores = Persona::getErrores();
        $errores = Usuario::getErrores();
        $errores = Poa::getErrores();


        //En el caso de cargos, vamos a capturar todos los registros de la tabla
        $cargos = Cargo::all();
        //Array con los programas que no tienen un coordinador vinculado
        $programas_vista = ProgramasinCoordinadorVista::all();
        // Si todos los programas tienen coordinador?
        if (empty($programas_vista)) {
            //Variable que se envía para deshabilitar el option de coordinador en tipo de usuario
            $cmbstatus = true;
            //Creando array vacío de programas
            $programas = [];
        } else {
            foreach ($programas_vista as $programa_vista) {
                //Capturamos el id del objeto programa que toque en el momento del bucle
                $pid = $programa_vista->programa_id;
                //Encontramos los objetos de programas según los programa_id en cada resultado
                $programas[] = Programa::find($pid);
            }
            $cmbstatus = false;
        }


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Creamos una nueva instancia
            $persona = new Persona($_POST['persona']);
            $usuario = new Usuario($_POST['usuario']);
            //Si existe un programa ingresado en el SELECT, es un coordinador!
            //Usaré una función que me permita identificar si la propiedad 'poa' tenga como valor un array asociativo
            $poavalida = validarPropiedadArray($_POST, 'poa', 'programa_id');

            if ($poavalida) {
                $objpoa = new Poa($_POST['poa']);
            }
            //Colocamos el hasheo para los password
            $usuario->password = password_hash($usuario->password, PASSWORD_DEFAULT);

            //Validamos
            $errores = $persona->validar();
            $errores = $usuario->validar();
            if ($poavalida) {
                $errores = $objpoa->validar();
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
                if ($poavalida) {
                    // Guardando en la tabla poa
                    $objpoa->usuario_id = $usuario->devolverIdLastInsercion();
                    $resultado = $objpoa->guardarsinRedireccion();
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
            'objpoa' => $objpoa,
            'errores' => $errores
        ]);
    }

    public static function actualizar(Router $router)
    {
        //Captamos el id del usuario contenido en el GET
        $id = validarORedireccionar('/usuario/admin');
        //Capatamos el objeto según el id de usuario
        $usuario = Usuario::find($id);
        //Captamos el id del registro en la tabla persona del usuario
        $idpersona = $usuario->persona_id;
        //Captamos el objeto de la clase Persona según el id de persona
        $persona = Persona::find($idpersona);
        //Creamos un objeto con un constructor default para cargar los datos de poa posteriormente si hubiera
        $objpoa = new Poa();
        //Creamos el array que contendrá a todos los cargos disponibles para los usuarios
        $cargos = Cargo::all();
        //Array con los poas que están vinculados al usuario
        $poa = [];
        //Mandamos el valor false para que por defecto se oculte el select de programas sin coordinador
        $cmbstatus = false;
        //Array con los programas que no tienen un coordinador vinculado
        $programas_sincoordi = ProgramasinCoordinadorVista::all();
        // Si todos los programas tienen coordinador?
        if (empty($programas_sincoordi)) {
            //El array programas solo debe de contener el programa del usuario coordinador
            $programas = [];
        } else {
            foreach ($programas_sincoordi as $prograsc) {
                //Capturamos el id del objeto programa que toque en el momento del bucle
                $pid = $prograsc->programa_id;
                //Encontramos los objetos de programas según los programa_id en cada resultado
                $programas[] = Programa::find($pid);
            }
            $cmbstatus = false;
        }

        $valor = $usuario->comprobarCoordinador();
        if ($valor === 1) {

            //Capturamos el objeto poa vinculado al usuario
            $objpoa = Poa::findxatributouno('usuario_id', $id);
            //Capturamos el valor del programa_id en la property del objeto poa
            $programa_id = $objpoa->programa_id;
            //Debemos de añadir el programa que está vinculado al usuario
            $programas[] = Programa::find($programa_id);
            //Al ser un coordinador, se habilitará automáticamente el select de programas sin coordinador
            $cmbstatus = true;
        }
        //Recogemos errores
        $errores = Persona::getErrores();
        $errores = Usuario::getErrores();
        $errores = Poa::getErrores();


        //Si es post
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Asigamos los valores enviados por el post en las variables de argumentos momentáneos
            $argspersona = $_POST['persona'];
            $argsusuario = $_POST['usuario'];
            //El caso de enviar una key poa, verificamos si el dato enviado es diferente a cero, si lo es, lo asignamos a la variable, si no, lo dejamos como null
            $argspoa = ($_POST['poa']['programa_id'] != 0) ? $_POST['poa'] : null;
            //Sincronizamos los objetos
            $persona->sincronizar($argspersona);
            $usuario->sincronizar($argsusuario);

            //Validamos errores
            $errores = $persona->validar();
            $errores = $usuario->validar();

            //Si el array errores esta vacío...
            if (empty($errores)) {
                //Si existe un poa, sincronizamos el objeto
                if (isset($argspoa)) {
                    //Enviamos el código de usuario a la base de datos antes de actualizar el poa
                    $valiusuario = Usuario::setUsuarioActual();
                    //Si es true...
                    //Guardando en la base de datos
                    if ($valiusuario) {
                        //Guardamos los cambios en los objetos persona y usuario
                        $usuario->guardarsinRedireccion();
                    } else {
                        $errores[] = "Error al asignar el usuario actual.";
                    }
                    //Extraemos el objeto del array y lo sincronizamos con el objeto creado
                    $objpoa->sincronizar($argspoa);
                    //Añadimos el usuario_id al objeto poa
                    $objpoa->usuario_id = $usuario->id;
                    //Guardamos el poa
                    //Validamos antes de guardar
                    $errores_poa = $objpoa->validar();
                    //Solamente si está sin errores, guardamos
                    if (empty($errores_poa)) {
                        //Enviamos el código de usuario a la base de datos antes de actualizar el poa
                        $vali = Poa::setUsuarioActual();
                        //Si es true...
                        //Guardando en la base de datos
                        if ($vali) {
                            //Guardamos el poa
                            $resultado = $objpoa->guardarsinRedireccion();
                        } else {
                            $errores[] = "Error al asignar el usuario actual.";
                        }
                    } else {
                        //combinamos los errores con los errores de persona y usuario
                        $errores = array_merge($errores, $errores_poa);
                    }
                    //Redirigimos hacie /usuario/admin con mensaje de actualización exitosa
                    header("Location: /usuario/admin?resultado=2");
                    exit();
                }
                //Si es un coordinador y no existe un $_POST['poa'] significa que se ha eliminado el poa
                else if ($valor === 1) {
                    //Enviamos el código de usuario a la base de datos antes de actualizar el poa
                    $valiusuario = Usuario::setUsuarioActual();
                    //Si es true...
                    //Guardando en la base de datos
                    if ($valiusuario) {
                        //Guardamos los cambios en los objetos persona y usuario
                        $persona->guardarsinRedireccion();
                        $usuario->guardarsinRedireccion();
                    } else {
                        $errores[] = "Error al asignar el usuario actual.";
                    }

                    //Enviamos el código de usuario a la base de datos antes de actualizar el poa
                    $vali = Poa::setUsuarioActual();
                    //Si es true...
                    //Guardando en la base de datos
                    if ($vali) {
                        //Eliminamos el poa
                        $objpoa->eliminarsinRedireccion();
                    } else {
                        $errores[] = "Error al asignar el usuario actual.";
                    }
                    //Redirigimos hacie /usuario/admin con mensaje de actualización exitosa
                    header("Location: /usuario/admin?resultado=2");
                    exit();
                } else {
                    //Enviamos el código de usuario a la base de datos antes de actualizar el poa
                    $valiusuario = Usuario::setUsuarioActual();
                    //Si es true...
                    //Guardando en la base de datos
                    if ($valiusuario) {
                        //Guardamos los cambios en los objetos persona y usuario
                        $persona->guardarsinRedireccion();
                    } else {
                        $errores[] = "Error al asignar el usuario actual.";
                    }
                    //Redirigimos hacie /usuario/admin con mensaje de actualización exitosa
                    header("Location: /usuario/admin?resultado=2");
                    exit();
                }
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
                            //Enviamos el código de usuario a la base de datos antes de actualizar el poa
                            $vali = Poa::setUsuarioActual();
                            //Si es true...
                            //Guardando en la base de datos
                            if ($vali) {
                                //Eliminamos el poa
                                $objpoa->eliminarsinRedireccion();
                            } else {
                                $errores[] = "Error al asignar el usuario actual.";
                            }
                        }
                        //Enviamos el código de usuario a la base de datos antes de actualizar el poa
                        $valiusuario = Usuario::setUsuarioActual();
                        //Si es true...
                        //Guardando en la base de datos
                        if ($valiusuario) {
                            //Guardamos los cambios en los objetos persona y usuario
                            $usuario->eliminarsinRedireccion();
                            $persona->eliminarsinRedireccion();
                        } else {
                            $errores[] = "Error al asignar el usuario actual.";
                        }
                        //La redirección de la URL en este caso va fuera de la función, para evitar que se redirija mal
                        header("Location: /usuario/admin?resultado=3");
                        exit();
                    }
                }
            }
        }
    }
}
