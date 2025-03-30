

<?php
// Captamos los datos necesarios con la superglobal SESSION
$logusuario = $_SESSION ?? null;
// Capturamos el cargo de usuario
$cargo = intval($logusuario['cargo_id']) ?? null;
if (isset($cargo)) {
    // Creamos un switch para los 3 sidebar a insertar
    switch ($cargo) {
        case 1:
            // SIDEBAR ADMIN en caso el $cargo=1
            include __DIR__ . '/layout_admin.php';
            break;
        case 2:
            // SIDEBAR CONTADOR en caso el $cargo=2
            include __DIR__ . '/layout_contador.php';
            break;
        case 3:
            // SIDEBAR COORDINADOR en caso el $cargo=3
            include __DIR__ . '/layout_coordinador.php';
            //En caso de ser coorinador agregar el poa_id correspondiente a su SESSION
            break;
        default:
            break;
    }
}

?>
