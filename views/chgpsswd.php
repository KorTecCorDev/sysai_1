<body class="auth">
    <main class="auth-shell auth-shell--single">
        <section class="auth-form auth-form--single">
            <a class="auth-back" href="/login"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al inicio de sesión</a>

            <header class="auth-form__head">
                <div class="auth-form__icon"><i class="bi bi-envelope-at" aria-hidden="true"></i></div>
                <h2 class="auth-form__title">Establecer o cambiar contraseña</h2>
                <p class="auth-form__subtitle">Ingresa tu correo y te enviaremos un código de verificación.</p>
            </header>

            <ol class="auth-steps" aria-label="Progreso">
                <li class="is-active"><span>1</span> Correo</li>
                <li><span>2</span> Código</li>
                <li><span>3</span> Contraseña</li>
            </ol>

            <?php include __DIR__ . '/partials/_auth_alertas.php'; ?>

            <form method="POST" action="/chgpsswd" class="auth-fields" novalidate><?php echo csrf_input(); ?>
                <div class="auth-field">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <div class="auth-input">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <input type="email" class="form-control" name="email" id="email"
                               placeholder="tucorreo@arcoiris.pe" autocomplete="username" required autofocus>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Enviar código</button>
            </form>

            <p class="auth-note">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                Si el correo está registrado, recibirás un código con vigencia de 30 minutos.
            </p>
        </section>
    </main>

    <footer class="auth-copyright">
        <span>Desarrollado por Cronos Soluciones · Todos los derechos reservados · <?php echo s(date('Y')); ?></span>
    </footer>
