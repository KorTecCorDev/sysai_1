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
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['login'])) {
        header('Location: /login');
        exit;
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

//Escapa / Sanitizar el HTML (null-safe, comillas y UTF-8 para contexto de atributos)
function s($html): string
{
    return htmlspecialchars((string) ($html ?? ''), ENT_QUOTES, 'UTF-8');
}

// ----------------------------------------------------------------------------
// Protección CSRF
// ----------------------------------------------------------------------------

/** Devuelve (creándolo si hace falta) el token CSRF de la sesión. */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Campo oculto con el token CSRF, para incrustar en los formularios POST. */
function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . s(csrf_token()) . '">';
}

/** Verifica el token CSRF enviado por POST (comparación en tiempo constante). */
function verificar_csrf(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $token = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ----------------------------------------------------------------------------
// Autorización por rol y alcance (A1 IDOR / A2 mass assignment)
//   cargo_id: 1 = Administrador, 2 = Contador, 3 = Coordinador
// ----------------------------------------------------------------------------

function cargoActual(): ?int
{
    return isset($_SESSION['cargo_id']) ? (int) $_SESSION['cargo_id'] : null;
}

function esAdmin(): bool        { return cargoActual() === 1; }
function esContador(): bool     { return cargoActual() === 2; }
function esCoordinador(): bool  { return cargoActual() === 3; }

/** POA/Programa al que está acotado el coordinador (de la sesión). */
function poaIdCoordinador(): ?int      { return isset($_SESSION['poa_id']) ? (int) $_SESSION['poa_id'] : null; }
function programaIdCoordinador(): ?int { return isset($_SESSION['programa_id']) ? (int) $_SESSION['programa_id'] : null; }

/**
 * Exige que el cargo actual esté dentro de la lista permitida; si no, corta con 403.
 * Defensa en profundidad además de la separación por carga de rutas.
 */
function exigirRol(array $cargosPermitidos): void
{
    if (!in_array(cargoActual(), $cargosPermitidos, true)) {
        http_response_code(403);
        exit('Acceso denegado: no tiene permisos para esta acción.');
    }
}

/**
 * Para coordinadores: exige que el recurso pertenezca a SU POA.
 * Admin/Contador no están acotados (pasan). Corta con 403 si hay violación.
 */
function exigirPoaPropio($poaIdRecurso): void
{
    if (esCoordinador() && (int) $poaIdRecurso !== poaIdCoordinador()) {
        http_response_code(403);
        exit('Acceso denegado: el registro no pertenece a su programa.');
    }
}

/**
 * Resuelve el programa_id de una actividad recorriendo la cadena
 * actividad → producto → resultado → programa. Devuelve null si no se resuelve.
 */
function programaIdPorActividad($actividadId): ?int
{
    $actividadId = (int) $actividadId;
    if ($actividadId <= 0) {
        return null;
    }
    $act = \Model\Actividad::find($actividadId);
    if (!$act || !isset($act->producto_id)) {
        return null;
    }
    $prod = \Model\Producto::find($act->producto_id);
    if (!$prod || !isset($prod->resultado_id)) {
        return null;
    }
    $res = \Model\Resultado::find($prod->resultado_id);
    if (!$res || !isset($res->programa_id)) {
        return null;
    }
    return (int) $res->programa_id;
}

/**
 * Para coordinadores: exige que la actividad (y el recurso ligado a ella:
 * rendición, rubro, etc.) pertenezca a SU programa. Admin/Contador pasan.
 * Corta con 403 si la actividad es de otro programa o no se resuelve.
 */
function exigirProgramaPropioPorActividad($actividadId): void
{
    if (!esCoordinador()) {
        return;
    }
    $programaRecurso = programaIdPorActividad($actividadId);
    if ($programaRecurso === null || $programaRecurso !== programaIdCoordinador()) {
        http_response_code(403);
        exit('Acceso denegado: el registro no pertenece a su programa.');
    }
}

/**
 * Para coordinadores: exige que el programa indicado sea EL SUYO (resultado, que
 * cuelga directo de programa). Admin/Contador pasan. Corta con 403 si no coincide.
 */
function exigirProgramaPropio($programaId): void
{
    if (esCoordinador() && (int) $programaId !== programaIdCoordinador()) {
        http_response_code(403);
        exit('Acceso denegado: el registro no pertenece a su programa.');
    }
}

/** programa_id de un resultado (resultado → programa). null si no se resuelve. */
function programaIdPorResultado($resultadoId): ?int
{
    $res = \Model\Resultado::find((int) $resultadoId);
    return ($res && isset($res->programa_id)) ? (int) $res->programa_id : null;
}

/** programa_id de un producto (producto → resultado → programa). null si no se resuelve. */
function programaIdPorProducto($productoId): ?int
{
    $prod = \Model\Producto::find((int) $productoId);
    if (!$prod || !isset($prod->resultado_id)) {
        return null;
    }
    return programaIdPorResultado($prod->resultado_id);
}

/** Coordinadores: exige que el resultado pertenezca a su programa. Admin/Contador pasan. */
function exigirProgramaPropioPorResultado($resultadoId): void
{
    if (!esCoordinador()) {
        return;
    }
    $programaRecurso = programaIdPorResultado($resultadoId);
    if ($programaRecurso === null || $programaRecurso !== programaIdCoordinador()) {
        http_response_code(403);
        exit('Acceso denegado: el registro no pertenece a su programa.');
    }
}

/** Coordinadores: exige que el producto pertenezca a su programa. Admin/Contador pasan. */
function exigirProgramaPropioPorProducto($productoId): void
{
    if (!esCoordinador()) {
        return;
    }
    $programaRecurso = programaIdPorProducto($productoId);
    if ($programaRecurso === null || $programaRecurso !== programaIdCoordinador()) {
        http_response_code(403);
        exit('Acceso denegado: el registro no pertenece a su programa.');
    }
}

//Validar tipo de Contenido
function validarTipoContenido($tipo)
{
    $tipos = ['categoria_rubro', 'programa', 'fuente_financiamiento', 'usuario', 'persona', 'detalle_financiamiento', 'resultado', 'producto', 'actividad', 'rubro', 'ingreso_egreso', 'tipocambiodolar', 'tipocambiodolar'];
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
        //ERROR USUARIO ENCONTRADO - LOGIN
        case 6:
            $mensaje = 'Se modificó el estado el POA del programa correctamente';
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
        exit;
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
        exit();
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
        exit();
    }
}

function validarORedireccionarPost(string $url)
{
    //Validar que sea un ID válido
    $id = $_POST["id"];

    $id = filter_var($id, FILTER_VALIDATE_INT);

    if (!$id) {
        header("Location: $url");
        exit;
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
        exit;
    }
    return $id;
}

function generarCodigoAleatorioSimple($longitud = 8)
{
    // Token criptográficamente seguro (reemplaza str_shuffle, que NO es CSPRNG).
    // Devuelve $longitud caracteres hexadecimales.
    $bytes = random_bytes((int) ceil($longitud / 2));
    return substr(bin2hex($bytes), 0, $longitud);
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

/**
 * Envía el código (token) de recuperación de contraseña.
 *  - Con SMTP configurado en includes/config/mail.php → envía el email real.
 *  - Sin SMTP (entorno de desarrollo) → registra el token en includes/logs/mail.log
 *    y lo trata como "enviado", para poder continuar el flujo sin servidor de correo.
 *
 * @return bool true si se envió (o se registró en modo dev), false si falló el SMTP.
 */
function enviarTokenRecuperacion(string $email, string $nombre, string $token): bool
{
    $cfg = require __DIR__ . '/config/mail.php';

    // Modo desarrollo: sin credenciales SMTP no se puede enviar de verdad.
    if (empty($cfg['username']) || empty($cfg['password'])) {
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $linea = sprintf(
            "[%s] [DEV - correo NO enviado] PARA: %s | NOMBRE: %s | TOKEN: %s%s",
            date('Y-m-d H:i:s'),
            $email,
            $nombre,
            $token,
            PHP_EOL
        );
        @file_put_contents($logDir . '/mail.log', $linea, FILE_APPEND | LOCK_EX);
        error_log("[DEV] Token de recuperación para {$email}: {$token}");
        return true;
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->SMTPSecure = $cfg['secure'];
        $mail->Port       = (int) $cfg['port'];
        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($email, $nombre);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Respuesta a Solicitud de cambio de Contraseña';
        $mail->Body =
            '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">'
            . '<h2 style="color:#42A5F5">Cambio de Contraseña</h2>'
            . '<p>Hola, ' . htmlspecialchars($nombre) . '.</p>'
            . '<p>Usa el siguiente código para completar el proceso:</p>'
            . '<p style="font-size:24px;font-weight:bold;color:#42A5F5">' . htmlspecialchars($token) . '</p>'
            . '<p>Si no solicitaste este cambio, ignora este correo electrónico.</p>'
            . '<hr><small>Cronos Soluciones</small></div>';
        $mail->AltBody = "Tu código de recuperación es: {$token}";
        return $mail->send();
    } catch (\Throwable $e) {
        error_log('Error al enviar correo de recuperación: ' . $e->getMessage());
        return false;
    }
}

//Función para arrays asociativos
function validarPropiedadArray(array $array, string $propiedad, string $subpropiedad): bool
{
    // Verifica si la propiedad existe en el array y su valor no es nulo ni vacío
    if ($array[$propiedad][$subpropiedad] == 0) {
        return false;
    }
    return true;

    //&&array_key_exists($propiedad, $array)  && $array[$propiedad] != 0;
}

