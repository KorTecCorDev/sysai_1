<?php
// Partial de mensajes del módulo de autenticación (login y cambio de contraseña).
// Reemplaza los 4 modales copiados a mano por un bloque de alerta accesible y
// consistente con el tema. Espera la variable $errores (array, puede no existir).
$errores = $errores ?? [];
?>
<?php if (!empty($errores)) : ?>
    <div class="auth-alert auth-alert--error" role="alert">
        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <div class="auth-alert__body">
            <?php foreach ($errores as $error) : ?>
                <p><?php echo s($error); ?></p>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
