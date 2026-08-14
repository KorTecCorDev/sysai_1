<body class="auth">
    <main class="auth-shell">
        <div class="auth-card">

            <!-- Panel de marca (institucional) -->
            <aside class="auth-brand" aria-hidden="true">
                <div class="auth-brand__mark">
                    <img src="/build/img/arca_isotipo.png" alt="">
                </div>
                <div class="auth-brand__pitch">
                    <h1 class="auth-brand__title">Arca</h1>
                    <p class="auth-brand__lead">Gestión presupuestal y rendición de cuentas · Arco Iris.</p>
                </div>
            </aside>

            <!-- Panel del formulario -->
            <section class="auth-form">
                <header class="auth-form__head">
                    <img class="auth-form__logo" src="/build/img/arca_isotipo.png" alt="Arca">
                    <h2 class="auth-form__title">Bienvenido</h2>
                    <p class="auth-form__subtitle">Inicia sesión para continuar</p>
                </header>

                <?php if (($_GET['resultado'] ?? '') === 'cambio') : ?>
                    <div class="auth-alert auth-alert--ok" role="status">
                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                        <div class="auth-alert__body"><p>Tu contraseña se actualizó. Ya puedes iniciar sesión.</p></div>
                    </div>
                <?php endif; ?>

                <?php include __DIR__ . '/partials/_auth_alertas.php'; ?>

                <form method="POST" action="/login" class="auth-fields" novalidate><?php echo csrf_input(); ?>
                    <div class="auth-field">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <div class="auth-input">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <input class="form-control" name="email" type="email" id="email"
                                   placeholder="tucorreo@arcoiris.pe" autocomplete="username" required autofocus>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="form-label" for="password">Contraseña</label>
                        <div class="auth-input password-space">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input class="form-control" name="password" type="password" id="password"
                                   placeholder="Ingresa tu contraseña" autocomplete="current-password" required>
                            <button type="button" id="togglePassword" class="auth-eye"
                                    aria-label="Mostrar contraseña"><i class="bi bi-eye-slash"></i></button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Iniciar sesión
                    </button>
                </form>

                <div class="auth-form__foot">
                    <a href="/chgpsswd">¿Primera vez o olvidaste tu contraseña?</a>
                </div>
            </section>
        </div>
    </main>

    <footer class="auth-copyright">
        <span>Desarrollado por Cronos Soluciones · Todos los derechos reservados · <?php echo s(date('Y')); ?></span>
    </footer>
