<?php

namespace Controllers;

use MVC\Router;

use Model\IngresoEgreso;
use Model\OieComprobante;
use Model\FuentesPoaVista;
use Model\ProgramaPoaVista;
use Model\OieTipoComprobante;
use Model\OtrosIngresosEgresos;
use Model\IngresoEgresoAdminVista;
use Model\OtrosIngresosEgresosAdminCoordinadorEgresoVista;
use Model\Poa;

class IngresoEgresoController
{

    // Admin
    public static function index(Router $router)
    {
        // Creando el array con todos los oie_tipo_comprobante ($tipocomprobantes)
        $tipocomprobantes = OieTipoComprobante::all();

        // Creando el nuevo objeto oie ($oie)
        $oie = new OtrosIngresosEgresos();
        //Es un coordinador?
        $tipousuarioid = $_SESSION['cargo_id'];
        if ($tipousuarioid == 3) {
            //Almacenamos el programa exclusivo del coordinador.
            //Programa del coordinador
            $poas = ProgramaPoaVista::findxatributo('poa_id', $_SESSION['poa_id']);
            //Otros egresos del POA del coordinador
            $oies = OtrosIngresosEgresosAdminCoordinadorEgresoVista::findxatributo('poa_id',$_SESSION['poa_id']);
        } else {
            // Creando el array con los programas que tengan POA (Vista) ($poas)
            $poas = ProgramaPoaVista::all();

            // Creando el array con todos los oie registrados ($oies)
            $oies = IngresoEgresoAdminVista::all();
        }

        // Resultado en cero en caso que no se haya realizado ninguna acción con los datos

        $resultado = 0;

        //Renderizando la vista
        $router->render('ingreso_egreso/admin', [
            'tipocomprobantes' => $tipocomprobantes,
            'oie' => $oie,
            'resultado' => $resultado,
            'poas' => $poas,
            'oies' => $oies
        ]);
    }

    // Selección de ff para crear OIE
    public static function indexff(Router $router)
    {
        //Captamos el oie_id
        $oieid = intval($_GET['id']);
        //Encontramos el objeto
        $oie = OtrosIngresosEgresos::find($oieid);
        if (!$oie) { header('Location: /ingreso_egreso/admin'); exit(); }
        // A1: el coordinador solo puede operar sobre OIE de su propio POA
        exigirPoaPropio($oie->poa_id);
        //Encontramos el poa_id
        $poaid = $oie->poa_id;
        //Creando el array con objetos de fuentes de financiamiento según el poa_id
        $ff_programas = FuentesPoaVista::findxatributo('poa_id', $poaid);

        $resultado = 0;

        //Guardando errores
        $errores = OtrosIngresosEgresos::getErrores();
        //En caso se hay enviado el formulario (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Asignamos el valor de la fuente de financiamiento al objeto OIE
            $oie->ff_id = $_POST['ff_id'] ?? null;
            //Validamos errores
            $errores = $oie->validarconFf();

            if (empty($errores)) {
                //Guardamos el registro en la tabla oie
                $resultado = $oie->guardarsinRedireccion();
                //Redireccionamos a la vista de administración
                if ($resultado) {
                    header('Location: /ingreso_egreso/admin');
                    exit();
                }
            }
        }

        //Renderizando la vista
        $router->render('ingreso_egreso/ff', [
            'ff_programas' => $ff_programas,
            'oie' => $oie,
            'errores' => $errores
        ]);
    }



    //Crear

    public static function crear(Router $router)
    {
        // Creando el array con todos los oie_tipo_comprobante ($tipocomprobantes)
        $tipocomprobantes = OieTipoComprobante::all();

        //Creamos los objetos a insertar
        $oie_comprobante = new OieComprobante();
        // Creando el nuevo objeto oie ($oie)
        $oie = new OtrosIngresosEgresos();

        //Es un coordinador?
        $tipousuarioid = $_SESSION['cargo_id'];
        if ($tipousuarioid == 3) {
            //Almacenamos el programa exclusivo del coordinador.
            //Programa del coordinador
            $poas = ProgramaPoaVista::findxatributo('poa_id', $_SESSION['poa_id']);
            //Otros egresos del POA del coordinador
            $oies = OtrosIngresosEgresosAdminCoordinadorEgresoVista::findmany($_SESSION['poa_id']);
            //Enviamos solo el campo egresos para el combo de selección tipo_oie
            
        } else {
            // Creando el array con los programas que tengan POA (Vista) ($poas)
            $poas = ProgramaPoaVista::all();

            // Creando el array con todos los oie registrados ($oies)
            $oies = OtrosIngresosEgresos::all();
        }
        // Resultado en cero en caso que no se haya realizado ninguna acción con los datos

        $resultado = 0;

        //Array de errores
        $errores = OtrosIngresosEgresos::getErrores();

        //En caso se hay enviado el formulario (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            //Comenzamos con oie_comprobante
            //SECCIÓN OIE COMPROBANTE
            //Instanciando el nuevo comprobante
            $oiecomprobante = new OieComprobante($_POST['oie_comprobante']);
            //Verificamos errores
            $errores = $oiecomprobante->validar();
            if (empty($errores)) {
                //Insertando la accion de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = OieComprobante::setUsuarioActual();
                //Si es true...
                if ($vali) {
                    //Si no hay errores, guardamos el comprobante
                    $resultado = $oiecomprobante->guardarsinRedireccion();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
            }
            // FIN SECCIÓN OIE COMPROBANTE


            //SECCIÓN OIE
            $oingresosegresos = new OtrosIngresosEgresos($_POST['oie']);
            // A2: el coordinador SOLO registra EGRESOS y dentro de SU POA (no confiar en el POST)
            if (esCoordinador()) {
                $oingresosegresos->oie_tipo_id = 2;              // 2 = Egreso
                $oingresosegresos->poa_id = poaIdCoordinador();
            }
            //Seleccionamos el id del comprobante previamente registrado
            $oiecomprobantelast = OieComprobante::findlast();
            $oiecomprobanteid = $oiecomprobantelast->id;
            //Asignamos el id del comprobante al objeto OIE
            $oingresosegresos->oie_comprobante_id = $oiecomprobanteid;
            //FIN SECCIÓN OIE
            $errores = $oingresosegresos->validar();
            if (empty($errores)) {
                //Insertando la accion de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = OieComprobante::setUsuarioActual();
                //Si es true...
                if ($vali) {
                    //Guardamos el registro en la tabla oie, pero el ff_id no tiene valor, haremos un UPDATE en la siguiente vista
                    $resultado = $oingresosegresos->guardarsinRedireccion();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
                //Encontramos el id del último OIE ingresado (recientemente)
                $oieid = OtrosIngresosEgresos::findlast();
                //Redireccionamos a la siguiente vista
                header('Location: /ingreso_egreso/ff?id=' . $oieid->id);
                exit();
            }
        }
        //Renderizando la vista
        $router->render('ingreso_egreso/crear', [
            'tipocomprobantes' => $tipocomprobantes,
            'oie' => $oie,
            'oie_comprobante' => $oie_comprobante,
            'resultado' => $resultado,
            'poas' => $poas,
            'oies' => $oies,
            'errores' => $errores
        ]);
    }
    //Actualizar

    public static function actualizar(Router $router)
    {
        //Captamos el oie_id
        $oie_id = $_GET['id'];
        // Encontramos el registro oie a actualizar
        $oie = OtrosIngresosEgresos::find($oie_id);
        if (!$oie) { header('Location: /ingreso_egreso/admin'); exit(); }
        // A1: el coordinador solo puede actualizar OIE de su propio POA
        exigirPoaPropio($oie->poa_id);

        //Encontramos el registro oie_comprobante a actualizar (usamos el oie_comprobante_id del registros oie)
        $oie_comprobante = OieComprobante::find($oie->oie_comprobante_id);

        // Creando el array con todos los oie_tipo_comprobante ($tipocomprobantes)
        $tipocomprobantes = OieTipoComprobante::all();

        // Creando el array con los programas que tengan POA (Vista) ($poas)
        $poas = ProgramaPoaVista::all();

        // Resultado en cero en caso que no se haya realizado ninguna acción con los datos
        $resultado = 0;

        //Array de errores
        $errores = OtrosIngresosEgresos::getErrores();


        //En caso se hay enviado el formulario (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Comenzamos con oie_comprobante
            //SECCIÓN OIE COMPROBANTE
            // Sincronizamos los valores del post con los objetos a actualizar
            //Comenzamos con el comprobante
            $argscomprobante = $_POST['oie_comprobante'];
            $oie_comprobante->sincronizar($argscomprobante);
            //Seguimos con el OIE
            $argsoie = $_POST['oie'];
            $oie->sincronizar($argsoie);
            // A2: impedir que el coordinador cambie el tipo (Egreso) o el POA vía el POST
            if (esCoordinador()) {
                $oie->oie_tipo_id = 2;
                $oie->poa_id = poaIdCoordinador();
            }

            //Verificamos errores
            $errores = $oie_comprobante->validar();
            $errores = $oie->validar();
            if (empty($errores)) {
                //Insertando la accion de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = OieComprobante::setUsuarioActual();
                //Si es true...
                if ($vali) {
                    //Si no hay errores, guardamos el comprobante y luego el oie
                    $resultado = $oie_comprobante->guardarsinRedireccion();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
            }
            if ($resultado) {
                //Insertando la accion de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = OieComprobante::setUsuarioActual();
                //Si es true...
                if ($vali) {
                    //Guardamos el registro en la tabla oie, pero el ff_id no tiene valor, haremos un UPDATE en la siguiente vista
                    $resultado = $oie->guardarsinRedireccion();
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
                //Encontramos el id del último OIE ingresado (recientemente)
                $oieid = $oie->id;
                //Redireccionamos a la siguiente vista
                header('Location: /ingreso_egreso/ff?id=' . $oieid);
                exit();
            }
        }
        //Renderizando la vista
        $router->render('ingreso_egreso/actualizar', [
            'tipocomprobantes' => $tipocomprobantes,
            'oie' => $oie,
            'oie_comprobante' => $oie_comprobante,
            'resultado' => $resultado,
            'poas' => $poas,
            'errores' => $errores
        ]);
    }


    //Actualizar FF

    public static function actualizarff(Router $router)
    {
        //Captamos el poa_id
        $oieid = intval($_GET['id']);
        //Encontramos el objeto
        $oie = OtrosIngresosEgresos::find($oieid);
        //Encontramos el poa_id
        $poaid = $oie->poa_id;
        //Creando el array con objetos de fuentes de financiamiento según el poa_id
        $ff_programas = FuentesPoaVista::findxatributo('poa_id', $poaid);

        $resultado = 0;

        //Guardando errores
        $errores = OtrosIngresosEgresos::getErrores();
        //En caso se hay enviado el formulario (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Asignamos el valor de la fuente de financiamiento al objeto OIE
            $oie->ff_id = $_POST['ff_id'] ?? null;
            //Validamos errores
            $errores = $oie->validarconFf();

            if (empty($errores)) {
                //Insertando la accion de audi para el usuario actual
                //Enviamos el codigo de usuario a la base de datos
                $vali = OieComprobante::setUsuarioActual();
                //Si es true...
                if ($vali) {
                } else {
                    $errores[] = "Error al asignar el usuario actual.";
                }
                //Redireccionamos a la vista de administración
                if ($resultado) {
                    header('Location: /ingreso_egreso/admin?resultado=2');
                    exit();
                }
            }
        }

        //Renderizando la vista
        $router->render('ingreso_egreso/ff_actualizar', [
            'ff_programas' => $ff_programas,
            'oie' => $oie,
            'errores' => $errores
        ]);
    }

    //Eliminar

    public static function eliminar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            //Verificamos que el dato enviado sea del tipo correcto
            $oieid = filter_var($id, FILTER_VALIDATE_INT);
            if ($oieid) {
                //Verificamos que el tipo dentro del POST sea el correcto
                $tipo = $_POST['tipo'];
                if (validarTipoContenido($tipo)) {
                    $oie = OtrosIngresosEgresos::find($oieid);
                    if ($oie) {
                        // A1: el coordinador solo puede eliminar OIE de su propio POA
                        exigirPoaPropio($oie->poa_id);
                        //Insertando la accion de audi para el usuario actual
                        //Enviamos el codigo de usuario a la base de datos
                        $vali = OieComprobante::setUsuarioActual();
                        //Si es true...
                        if ($vali) {
                            $oie->eliminar();
                        } else {
                            $errores[] = "Error al asignar el usuario actual.";
                        }
                        header("Location: /ingreso_egreso/admin");
                        exit();
                    }
                }
            }
        }
    }
}
