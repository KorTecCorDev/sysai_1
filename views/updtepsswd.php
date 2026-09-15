<body class="auth">
    <main class="auth-shell auth-shell--single">
        <section class="auth-form auth-form--single">
            <header class="auth-form__head">
                <div class="auth-form__icon"><i class="bi bi-key-fill" aria-hidden="true"></i></div>
                <h2 class="auth-form__title">Nueva contraseña</h2>
                <p class="auth-form__subtitle">Define la contraseña con la que ingresarás al sistema.</p>
            </header>

            <ol class="auth-steps" aria-label="Progreso">
                <li class="is-done"><span>1</span> Correo</li>
                <li class="is-done"><span>2</span> Código</li>
                <li class="is-active"><span>3</span> Contraseña</li>
            </ol>

            <?php include __DIR__ . '/partials/_auth_alertas.php'; ?>

            <form method="POST" action="/updtepsswd" class="auth-fields" novalidate><?php echo csrf_input(); ?>
                <div class="auth-field">
                    <label for="password" class="form-label">Nueva contraseña</label>
                    <div class="auth-input password-space">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" class="form-control" name="password" id="password"
                               placeholder="Mínimo 8 caracteres" autocomplete="new-password" minlength="8" required autofocus>
                        <button type="button" id="togglePassword" class="auth-eye"
                                aria-label="Mostrar contraseña"><i class="bi bi-eye-slash"></i></button>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="password_confirm" class="form-label">Confirmar contraseña</label>
                    <div class="auth-input">
                        <i class="bi bi-lock-fill" aria-hidden="true"></i>
                        <input type="password" class="form-control" name="password_confirm" id="password_confirm"
                               placeholder="Repite la contraseña" autocomplete="new-password" minlength="8" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">Guardar y continuar</button>
            </form>

            <p class="auth-note">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                Usa al menos 8 caracteres. Al guardar volverás al inicio de sesión.
            </p>
        </section>
    </main>

    <footer class="auth-copyright">
        <span>Desarrollado por Cronos Soluciones · Todos los derechos reservados · <?php echo s(date('Y')); ?></span>
    </footer>
