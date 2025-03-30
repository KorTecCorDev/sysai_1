<?php
define('TEMPLATES_URL', __DIR__ . '/templates');
define('FUNCIONES_URL', __DIR__ . 'funciones.php');
define('CARPETA_IMAGENES', $_SERVER['DOCUMENT_ROOT'] . '/imagenes/');
function incluirTemplate(string $nombre, bool $inicio = false)
{
    include TEMPLATES_URL . '/' . $nombre . '.php';
}

function estaAutenticado()
{
    session_start();
    if (!$_SESSION['login']) {
        header('Location: /');
    }
}

function debuguear($variable)
{
    echo '<pre>';
    var_dump($variable);
    echo '</pre>';
    exit;
}

function debuguearHTML($variable)
{
    echo "<script>console.log('PHP dice: " . addslashes($variable) . "');</script>";
    exit;
}

//Escapa / Sanitizar el HTML
function s($html): string
{
    $s = htmlspecialchars($html);
    return $s;
}

//Validar tipo de Contenido
function validarTipoContenido($tipo)
{
    $tipos = ['categoria_rubro', 'programa', 'fuente_financiamiento', 'usuario', 'persona', 'detalle_financiamiento', 'resultado', 'producto', 'actividad', 'rubro', 'ingreso_egreso'];
    return in_array($tipo, $tipos);
}

//Muestra los mensajes
function mostrarNotificacion($codigo)
{
    $mensaje = '';
    switch ($codigo) {
            //CREAR CORRECTO
        case 1:
            $mensaje = 'Creado correctamente';
            break;
            //ACTUALIZAR CORRECTO
        case 2:
            $mensaje = 'Actualizado correctamente';
            break;
            //ELIMINAR CORRECTO
        case 3:
            $mensaje = 'Eliminado correctamente';
            break;
            //RELACIÓN PROGRAMA - FUENTE_FINANCIAMIENTO CORRECTO
        case 4:
            $mensaje = 'Relación actualizada correctamente';
            break;
            //ERROR USUARIO ENCONTRADO - LOGIN
        case 5:
            $mensaje = 'No existe un usuario, verifique el correo electrónico';
            break;
        default:
            $mensaje = false;
            break;
    }
    return $mensaje;
}

function validarORedireccionar(string $url)
{
    //Validar que sea un ID válido
    $id = $_GET["id"];

    $id = filter_var($id, FILTER_VALIDATE_INT);

    if (!$id) {
        header("Location: $url");
    }
    return $id;
}

function validarORedireccionarDosParametros(string $url, string $param1, string $param2)
{
    //Validamos el ingreso de los 2 parametros
    $prmt1 = $_GET[$param1] ?? null;
    $prmt2 = $_GET[$param2] ?? null;

    $prmt1 = filter_var($prmt1, FILTER_VALIDATE_INT) ?? null;
    $prmt2 = filter_var($prmt2, FILTER_VALIDATE_INT) ?? null;
    if ($prmt1 && $prmt2) {
        $resultado = [$prmt1, $prmt2];
        return $resultado;
    } else if ($prmt1) {
        return $prmt1;
    } elseif ($prmt2) {
        return $prmt2;
    } else {
        header("Location: $url");
    }
}
function validarORedireccionarDosParametrosPost(string $url, string $param1, string $param2)
{
    //Validamos el ingreso de los 2 parametros
    $prmt1 = $_POST[$param1] ?? null;
    $prmt2 = $_POST[$param2] ?? null;

    $prmt1 = filter_var($prmt1, FILTER_VALIDATE_INT) ?? null;
    $prmt2 = filter_var($prmt2, FILTER_VALIDATE_INT) ?? null;
    if ($prmt1 && $prmt2) {

        $resultado = [$prmt1, $prmt2];
        return $resultado;
    } else if ($prmt1) {
        return $prmt1;
    } elseif ($prmt2) {
        return $prmt2;
    } else {
        header("Location: $url");
    }
}

function validarORedireccionarPost(string $url)
{
    //Validar que sea un ID válido
    $id = $_POST["id"];

    $id = filter_var($id, FILTER_VALIDATE_INT);

    if (!$id) {
        header("Location: $url");
    }
    return $id;
}

function validarORedireccionarconTabla(string $url, string $tb)
{
    //Validar que sea un ID válido
    $id = isset($_GET[$tb . "_id"]);
    if ($id) {
        $id = $_GET[$tb . "_id"];
    }
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if (!$id) {
        header("Location: " . $url);
    }
    return $id;
}

function generarCodigoAleatorioSimple($longitud = 8)
{
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codigo = substr(str_shuffle($caracteres), 0, $longitud);
    return $codigo;
}

function redireccionar(string $url)
{
    header("Location: $url");
}

function validarId($tb)
{
    //Validar que sea un ID válido
    $id = isset($_GET[$tb . "_id"]);
    if ($id) {
        $id = $_GET[$tb . "_id"];
        $id = filter_var($id, FILTER_VALIDATE_INT);
    }
    return $id;
}
