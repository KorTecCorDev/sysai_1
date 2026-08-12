<body class="auth">
    <main class="auth-shell auth-shell--single">
        <section class="auth-form auth-form--single">
            <a class="auth-back" href="/chgpsswd"><i class="bi bi-arrow-left" aria-hidden="true"></i> Solicitar otro código</a>

            <header class="auth-form__head">
                <div class="auth-form__icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></div>
                <h2 class="auth-form__title">Verificación de código</h2>
                <p class="auth-form__subtitle">Ingresa el código que enviamos a tu correo electrónico.</p>
            </header>

            <ol class="auth-steps" aria-label="Progreso">
                <li class="is-done"><span>1</span> Correo</li>
                <li class="is-active"><span>2</span> Código</li>
                <li><span>3</span> Contraseña</li>
            </ol>

            <?php include __DIR__ . '/partials/_auth_alertas.php'; ?>

            <form method="POST" action="/token_verify" class="auth-fields" novalidate><?php echo csrf_input(); ?>
                <div class="auth-field">
                    <label for="reset_token" class="form-label">Código de verificación</label>
                    <div class="auth-input">
                        <i class="bi bi-key" aria-hidden="true"></i>
                        <input type="text" class="form-control auth-code" name="reset_token" id="reset_token"
                               placeholder="Ej. A1B2C3D4E5" autocomplete="one-time-code"
                               inputmode="latin" spellcheck="false" required autofocus>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Verificar</button>
            </form>

            <p class="auth-note">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                ¿No lo recibiste? Revisa tu carpeta de spam o solicita uno nuevo.
            </p>
        </section>
    </main>

    <footer class="auth-copyright">
        <span>Desarrollado por Cronos Soluciones · Todos los derechos reservados · <?php echo s(date('Y')); ?></span>
    </footer>
