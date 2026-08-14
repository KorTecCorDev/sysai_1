<?php
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

//Escapa / Sanitizar el HTML (null-safe, comillas y UTF-8 para contexto de atributos)
function s($html): string
{
    return htmlspecialchars((string) ($html ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Tope de cordura para TODO campo de dinero (plan de montos, Fase 0): 5× el máximo
 * declarado por el negocio (S/ 10M) para dejar margen de crecimiento. Atrapa el cero
 * de más y los absurdos tipo 999999999; si un día queda corto, el fallo es ruidoso y
 * recuperable (error en pantalla, una constante que se sube).
 */
define('MONTO_MAXIMO', 50000000.00);

/**
 * Convierte un monto en soles a otra moneda con guarda contra TC ausente o cero
 * (plan de montos, Fase 4). Devuelve null si no hay tasa válida: el llamador
 * muestra "—" — nunca un DivisionByZeroError ni un cero mentiroso.
 */
function convertirMoneda($monto, $tasa): ?float
{
    $tasa = (float) ($tasa ?? 0);
    if ($tasa <= 0) {
        return null;
    }
    return round(((float) ($monto ?? 0)) / $tasa, 2);
}

/**
 * Normaliza un monto tecleado por el usuario a float (UN solo punto de sanitización
 * para todos los campos de dinero — plan de montos, Fase 0). Tolera el símbolo de
 * moneda ("S/", "S/.", "S./"), espacios, comas de miles ("1,500,000.50") y la coma
 * decimal simple ("1234,56"). Devuelve null si no es un número válido: null NUNCA
 * debe llegar a la BD — la validación del modelo lo convierte en error visible.
 * (Antes: "1,500,000.00" se guardaba como 1.00 y "S/ 450000" como 0.00, en silencio.)
 */
function montoNumerico($valor): ?float
{
    if ($valor === null) {
        return null;
    }
    if (is_int($valor) || is_float($valor)) {
        return (float) $valor;
    }
    $t = trim((string) $valor);
    if ($t === '') {
        return null;
    }
    // Símbolo de moneda al inicio: "S/", "S/.", "S./", con o sin espacio.
    $t = preg_replace('/^s\.?\/\.?\s*/i', '', $t);
    // Espacios internos (incluye NBSP) usados como separador de miles.
    $t = str_replace([' ', "\xC2\xA0"], '', $t);
    if (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $t)) {
        // Comas de miles con punto decimal opcional: "1,500,000.50".
        $t = str_replace(',', '', $t);
    } elseif (preg_match('/^\d+,\d{1,2}$/', $t)) {
        // Una sola coma como separador decimal: "1234,56".
        $t = str_replace(',', '.', $t);
    }
    if (!is_numeric($t)) {
        return null;
    }
    return (float) $t;
}

/**
 * Formatea un monto como moneda en Soles: "S/ 1,234.56".
 * Null-safe (null o cadena vacía => "S/ 0.00"). Centraliza el formato de
 * moneda para no repetir number_format() por las vistas. La salida es segura
 * para HTML (solo dígitos, comas, puntos y el símbolo), no requiere s().
 */
function soles($monto): string
{
    return 'S/ ' . number_format((float) ($monto ?? 0), 2);
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

/** programa_id de un rubro (rubro → actividad → … → programa). null si no se resuelve. */
function programaIdPorRubro($rubroId): ?int
{
    $rubro = \Model\Rubro::find((int) $rubroId);
    if (!$rubro || !isset($rubro->actividad_id)) {
        return null;
    }
    return programaIdPorActividad($rubro->actividad_id);
}

/**
 * Para coordinadores: exige que el rubro (y el recurso ligado: rendición) pertenezca
 * a SU programa. Admin/Contador pasan. Corta con 403 si es de otro programa.
 */
function exigirProgramaPropioPorRubro($rubroId): void
{
    if (!esCoordinador()) {
        return;
    }
    $programaRecurso = programaIdPorRubro($rubroId);
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

/**
 * ¿El POA Indicadores del programa (año vigente) admite edición de su jerarquía?
 * Editable en Borrador(0) u Observado(2); bloqueado en Enviado(1) o Aprobado(3).
 * Si aún no existe el documento, se considera editable (todavía en elaboración).
 */
function poaIndicadoresEditable($programaId): bool
{
    $doc = \Model\PoaIndicadores::porProgramaAnio((int) $programaId, date('Y'));
    return !$doc || (in_array((int) $doc->estado, [0, 2], true));
}

/**
 * Para coordinadores: bloquea la edición de la jerarquía cuando su POA Indicadores
 * está Enviado o Aprobado. Admin/Contador pasan (el Contador modifica como adenda).
 * En vez de un 403 crudo, redirige al listado con un flash (?resultado=14) que la
 * app traduce a un alert. La prevención principal son los botones deshabilitados en
 * la UI; esto es defensa en profundidad ante navegación/URL directa.
 */
function exigirPoaIndicadoresEditable($programaId): void
{
    if (!esCoordinador()) {
        return;
    }
    if (!poaIndicadoresEditable($programaId)) {
        header('Location: /resultado/admin?resultado=14');
        exit();
    }
}

/**
 * ¿El POA Presupuestal del programa (año vigente) admite edición de sus rubros?
 * Editable en Borrador(0) u Observado(2); bloqueado en Enviado(1) o Aprobado(3).
 * Si aún no existe el documento, se considera editable (todavía en elaboración).
 */
function poaPresupuestalEditable($programaId): bool
{
    $doc = \Model\Poa::porProgramaAnio((int) $programaId, date('Y'));
    return !$doc || (in_array((int) $doc->estado, [0, 2], true));
}

/**
 * Para coordinadores: bloquea la edición de rubros cuando su POA Presupuestal está
 * Enviado o Aprobado. Admin/Contador pasan (el Contador modifica como adenda).
 * Redirige al listado del POA con un flash (?resultado=16) en vez de un 403 crudo.
 */
function exigirPoaPresupuestalEditable($programaId): void
{
    if (!esCoordinador()) {
        return;
    }
    if (!poaPresupuestalEditable($programaId)) {
        header('Location: /poa/admin?resultado=16');
        exit();
    }
}

/** Igual que exigirPoaPresupuestalEditable pero resolviendo el programa desde la actividad. */
function exigirPoaPresupuestalEditablePorActividad($actividadId): void
{
    if (!esCoordinador()) {
        return;
    }
    $programaId = programaIdPorActividad($actividadId);
    if ($programaId === null || !poaPresupuestalEditable($programaId)) {
        header('Location: /poa/admin?resultado=16');
        exit();
    }
}

/**
 * Puerta de sobres (item 4): con Σ sobres = 0 (programa sin fuentes vinculadas) el
 * Coordinador no puede registrar nada PRESUPUESTAL — rubros, POA Presupuestal ni
 * rendiciones. NO alcanza al POA Indicadores ni a la jerarquía Resultado→Producto→
 * Actividad (no manejan dinero). Contador/Admin pasan (adenda). Redirige a
 * /poa/admin con flash persistente (?resultado=19): el coordinador debe saber que
 * le toca ESPERAR al Contador, no recortar nada.
 */
function exigirSobreAsignado($programaId): void
{
    if (!esCoordinador()) {
        return;
    }
    if ($programaId === null || \Model\Poa::topeSobres((int) $programaId) <= 0) {
        header('Location: /poa/admin?resultado=19');
        exit();
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
        //POA INDICADORES — DOCUMENTO YA EXISTENTE
        case 11:
            $mensaje = 'Ya existe un POA de Indicadores para este programa y año';
            break;
        //POA INDICADORES — TRANSICIÓN NO VÁLIDA PARA EL ESTADO ACTUAL
        case 13:
            $mensaje = 'La operación no es válida para el estado actual del documento';
            break;
        //POA INDICADORES — JERARQUÍA BLOQUEADA (DOCUMENTO ENVIADO/APROBADO)
        case 14:
            $mensaje = 'El POA de Indicadores está enviado o aprobado y no admite cambios';
            break;
        //POA INDICADORES — OBSERVACIÓN OBLIGATORIA AL DEVOLVER
        case 15:
            $mensaje = 'Debe escribir el motivo de la observación para devolver el documento';
            break;
        //POA PRESUPUESTAL — RUBROS BLOQUEADOS (DOCUMENTO ENVIADO/APROBADO)
        case 16:
            $mensaje = 'El POA Presupuestal está enviado o aprobado y no admite cambios en los rubros';
            break;
        //POA PRESUPUESTAL — DOCUMENTO YA EXISTENTE
        case 17:
            $mensaje = 'Ya existe un POA Presupuestal para este programa y año';
            break;
        //RENDICIONES BLOQUEADAS — POA PRESUPUESTAL ENVIADO/APROBADO
        case 18:
            $mensaje = 'El POA Presupuestal está enviado o aprobado: no se pueden registrar rendiciones';
            break;
        //PUERTA DE SOBRES — PROGRAMA SIN SOBRES ASIGNADOS (item 4)
        case 19:
            $mensaje = 'Tu programa aún no tiene sobres asignados. El Contador debe asignar el presupuesto antes de que puedas registrar rubros, el POA Presupuestal o rendiciones';
            break;
        //TOPE DEL POA — Σ RUBROS EXCEDE Σ SOBRES (item 4)
        case 20:
            $mensaje = 'El presupuesto del POA supera la suma de los sobres asignados al programa: no se puede enviar ni aprobar hasta ajustar los rubros o ampliar los sobres';
            break;
        //RENDICIÓN GUARDADA CON SOBREGASTO DEL RUBRO (advertencia, no bloqueo — plan de montos §2.3)
        case 21:
            $mensaje = 'Guardado correctamente. Atención: las rendiciones del rubro ya superan su monto planificado (sobregasto). El tope real sigue siendo el sobre';
            break;
        //APROBACIÓN DEL POA BLOQUEADA — RENDICIONES SIN TIPO DE CAMBIO (plan de montos, Fase 3)
        case 22:
            $mensaje = 'No se puede aprobar: hay rendiciones sin tipo de cambio para su fecha de operación. Registra los TC (USD y EUR) de las fechas indicadas y vuelve a aprobar';
            break;
        //CIERRE ANUAL REGISTRADO (item 8)
        case 23:
            $mensaje = 'Cierre anual registrado: se guardó el snapshot por fuente (inicial, comprometido y contable) en el histórico';
            break;
        //CIERRE ANUAL SIN FUENTES QUE REGISTRAR (item 8)
        case 24:
            $mensaje = 'No se registró el cierre: no hay fuentes de financiamiento con datos que guardar';
            break;
        //ELIMINACIÓN DE USUARIO FALLIDA (item 10 — antes fallaba en silencio)
        case 25:
            $mensaje = 'No se pudo eliminar el usuario: tiene registros vinculados que lo impiden. Verifica sus documentos e inténtalo de nuevo';
            break;
        //PROGRAMA INSTITUCIONAL — NO RECIBE SOBRES DIRECTOS NI SE OPERA COMO DESTINO (migr. 033)
        case 26:
            $mensaje = 'El programa Institucional no recibe sobres directos: su presupuesto se forma con las transferencias registradas al asignar los sobres de cada programa';
            break;
        //TRANSFERENCIA AL INSTITUCIONAL — REDUCCIÓN BLOQUEADA POR GASTO YA COMPROMETIDO (migr. 033)
        case 27:
            $mensaje = 'No se puede reducir o quitar la transferencia: el programa Institucional ya comprometió ese dinero (rendiciones o egresos contra su sobre en esta fuente)';
            break;
        //PROGRAMA INSTITUCIONAL — PROTEGIDO CONTRA ELIMINACIÓN (migr. 033)
        case 28:
            $mensaje = 'El programa Institucional no puede eliminarse: es parte permanente de la organización';
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
 * Registra una línea en includes/logs/mail.log (bitácora del correo saliente).
 * Es el único rastro que queda cuando el envío falla: sin esto, un SMTP caído
 * en producción es invisible (el usuario ve la misma pantalla neutra de siempre
 * y se queda esperando un código que nunca sale).
 */
function registrarMailLog(string $linea): void
{
    // Ruta configurable para poder situar la bitacora FUERA del document root en
    // produccion (p. ej. MAIL_LOG_PATH=/home/uXXXX/logs/mail.log). Servido por
    // HTTP, este archivo era descargable: ver docs/plan-secretos-y-hardening.md.
    $ruta = $_ENV['MAIL_LOG_PATH'] ?? getenv('MAIL_LOG_PATH');
    if (!is_string($ruta) || $ruta === '') {
        $ruta = __DIR__ . '/logs/mail.log';
    }
    $logDir = dirname($ruta);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    @file_put_contents(
        $ruta,
        sprintf("[%s] %s%s", date('Y-m-d H:i:s'), $linea, PHP_EOL),
        FILE_APPEND | LOCK_EX
    );
}

/**
 * Construye un PHPMailer ya configurado con el SMTP del .env.
 * Devuelve null si no hay credenciales (modo desarrollo).
 * Lo comparten el envío del token y el diagnóstico database/smtp_test.php.
 */
function construirMailer(): ?\PHPMailer\PHPMailer\PHPMailer
{
    $cfg = require __DIR__ . '/config/mail.php';

    // Resolución del transporte. 'auto' conserva el comportamiento histórico
    // (sin credenciales ⇒ modo log) para no romper entornos ya configurados.
    $transporte = strtolower(trim((string) $cfg['transport']));
    if ($transporte === 'log') {
        return null;
    }
    if ($transporte !== 'smtp' && (empty($cfg['username']) || empty($cfg['password']))) {
        return null;
    }
    if ($cfg['host'] === '') {
        return null;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $cfg['host'];
    // Sin usuario no se autentica: es el caso del catcher local de desarrollo
    // (Mailpit), que acepta todo y no reenvía nada a Internet.
    $mail->SMTPAuth   = $cfg['username'] !== '';
    $mail->Username   = $cfg['username'];
    $mail->Password   = $cfg['password'];
    // MAIL_SECURE vacío ⇒ sin cifrado. Se desactiva además el STARTTLS
    // automático de PHPMailer: contra un catcher local no hay TLS que negociar.
    $mail->SMTPSecure = $cfg['secure'] !== '' ? $cfg['secure'] : false;
    if ($cfg['secure'] === '') {
        $mail->SMTPAutoTLS = false;
    }
    $mail->Port       = (int) $cfg['port'];
    // Un SMTP que no responde no puede colgar la petición del usuario.
    $mail->Timeout    = 15;
    $mail->CharSet    = 'UTF-8';
    if (!empty($cfg['debug'])) {
        $mail->SMTPDebug   = 2; // solo para diagnóstico (MAIL_DEBUG=1 en el .env)
        $mail->Debugoutput = 'error_log';
    }

    // Gmail —y la mayoría de SMTP autenticados— reescriben o rechazan un remitente
    // distinto de la cuenta que autentica. Si el .env conserva el placeholder de
    // desarrollo, se usa la propia cuenta SMTP en vez de un dominio inexistente.
    // El usuario SMTP solo sirve como remitente si ES una direccion de correo.
    // Gmail exige que coincidan (si no, reescribe el From), pero esa regla no es
    // universal: en Mailtrap el usuario es un identificador tipo "660ee906edffcf"
    // y PHPMailer aborta con excepcion al recibirlo como From. Sin credenciales
    // (catcher local) el placeholder ya es una direccion perfectamente valida.
    $remitente = $cfg['from_email'];
    if (($remitente === '' || $remitente === 'no-reply@sysai.local')
        && filter_var($cfg['username'], FILTER_VALIDATE_EMAIL)) {
        $remitente = $cfg['username'];
    }
    if (!filter_var($remitente, FILTER_VALIDATE_EMAIL)) {
        $remitente = 'no-reply@sysai.local';
    }
    $mail->setFrom($remitente, $cfg['from_name']);

    return $mail;
}

/**
 * Envía el código (token) de recuperación de contraseña.
 *  - Con transporte SMTP configurado (includes/config/mail.php) → envía el correo.
 *  - Sin transporte → deja constancia en la bitácora (NUNCA el token) y, en
 *    desarrollo, permite continuar el flujo; en producción devuelve false.
 *
 * El token no se escribe jamás en la bitácora: es una credencial temporal.
 *
 * @return bool true si se envió (o se registró en desarrollo), false si falló.
 */
function enviarTokenRecuperacion(string $email, string $nombre, string $token): bool
{
    // Una configuracion de correo invalida no puede tumbar la peticion. PHPMailer
    // lanza excepcion, por ejemplo, ante un remitente mal formado, y esta llamada
    // quedaba FUERA del try: el usuario recibia un error fatal de PHP en
    // /chgpsswd en vez de un aviso, y el rastro se perdia.
    try {
        $mail = construirMailer();
    } catch (\Throwable $e) {
        registrarMailLog("[ERROR] Configuración de correo inválida: " . $e->getMessage());
        error_log('Configuración de correo inválida: ' . $e->getMessage());
        return false;
    }

    // Modo log: no hay transporte, no se envia nada.
    if ($mail === null) {
        // En PRODUCCION esto no puede reportarse como exito. El alta de usuarios
        // genera una contraseña aleatoria que nadie conoce, asi que el correo es
        // la UNICA via de activar una cuenta: un despliegue sin transporte deja
        // el sistema respondiendo "te enviamos un codigo" mientras nadie puede
        // entrar, y sin rastro de por que. Falla ruidosamente y devuelve false.
        $entorno = strtolower((string) ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development'));
        if ($entorno === 'production') {
            registrarMailLog("[ERROR] Sin transporte de correo en produccion: NO se envio a {$email}. Revisar MAIL_* en el .env.");
            error_log('Correo de recuperación NO enviado: no hay transporte SMTP configurado en producción.');
            return false;
        }
        // El TOKEN ya no se escribe en la bitacora. Es una credencial temporal
        // -quien lo lee toma la cuenta- y ese archivo llego a ser descargable por
        // HTTP. Para desarrollo esta Mailpit, que muestra el correo completo; el
        // token tambien queda en usuario.reset_token si hiciera falta consultarlo.
        registrarMailLog("[LOG - correo NO enviado] PARA: {$email}");
        return true;
    }

    try {
        $mail->addAddress($email, $nombre);
        $mail->isHTML(true);
        $mail->Subject = 'Código para cambiar tu contraseña — Arca';
        $mail->Body =
            '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">'
            . '<h2 style="color:#42A5F5">Cambio de contraseña</h2>'
            . '<p>Hola, ' . htmlspecialchars($nombre) . '.</p>'
            . '<p>Usa el siguiente código para completar el proceso:</p>'
            . '<p style="font-size:24px;font-weight:bold;color:#42A5F5">' . htmlspecialchars($token) . '</p>'
            . '<p>Si no solicitaste este cambio, ignora este correo electrónico.</p>'
            . '<hr><small>Arca — Organización Arco Iris</small></div>';
        $mail->AltBody = "Tu código de recuperación es: {$token}";

        if (!$mail->send()) {
            registrarMailLog("[ERROR] No se pudo enviar a {$email}: {$mail->ErrorInfo}");
            error_log('Error al enviar correo de recuperación: ' . $mail->ErrorInfo);
            return false;
        }
        registrarMailLog("[OK] Código de recuperación enviado a {$email}");
        return true;
    } catch (\Throwable $e) {
        registrarMailLog("[ERROR] Excepción enviando a {$email}: " . $e->getMessage());
        error_log('Error al enviar correo de recuperación: ' . $e->getMessage());
        return false;
    }
}

//Función para arrays asociativos (p. ej. ¿se eligió programa en $_POST['coordinador_programa']['programa_id']?)
function validarPropiedadArray(array $array, string $propiedad, string $subpropiedad): bool
{
    // true solo si la clave existe y su valor no es 0/'' (B6: sin warnings cuando la clave falta)
    return !empty($array[$propiedad][$subpropiedad]);
}

/**
 * Envía un libro de PhpSpreadsheet al navegador como descarga y TERMINA la petición.
 *
 * Sustituye (2026-08-14, Fase 0 del plan de reportes) al mecanismo anterior, que
 * escribía el .xlsx en `views/reporte/storage/reports/` y devolvía un enlace a
 * `/descargar?rprt=<archivo>`. Aquel esquema tenía dos agujeros verificados:
 *
 *   1) `/descargar` concatenaba el parámetro del cliente a la ruta sin sanear, así
 *      que `?rprt=../../../../.env` servía el .env —con la App Password de Gmail—
 *      a cualquier usuario autenticado, coordinadores incluidos. Además esquivaba
 *      el bloqueo del .htaccess, porque el archivo lo leía PHP y no Apache.
 *   2) Los .xlsx quedaban DENTRO del document root con nombres predecibles
 *      (`<tipo>_<parte-local-del-email>.xlsx`) y el .htaccess no filtra .xlsx:
 *      se descargaban por URL SIN SESIÓN, y se acumulaban sin limpieza.
 *
 * Al no tocar el disco, ambos desaparecen: no hay archivo que adivinar ni ruta que
 * recorrer, y el nombre lo compone siempre el servidor.
 *
 * @param  object $spreadsheet  \PhpOffice\PhpSpreadsheet\Spreadsheet ya construido.
 * @param  string $nombre       Nombre propuesto al navegador, sin extensión.
 * @return never
 */
function descargarXlsx($spreadsheet, string $nombre)
{
    // El nombre NUNCA viene del cliente, pero se sanea igual: es lo que viaja en la
    // cabecera Content-Disposition y no debe poder inyectar comillas ni saltos.
    $nombre = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre);
    if ($nombre === '' || $nombre === null) {
        $nombre = 'reporte';
    }

    // Router::render() envuelve la vista en un ob_start(); si queda algún búfer
    // abierto, su contenido se colaría dentro del .xlsx y lo dejaría corrupto.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombre . '.xlsx"');
    header('Cache-Control: max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    // Sin Content-Length: el escritor genera al vuelo y calcular el tamaño exigiría
    // materializar el libro en memoria o en un temporal, que es justo lo que se evita.
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

