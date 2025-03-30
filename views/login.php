
<body class="d-flex flex-column min-vh-100">
        <!-- Imprimiendo las alertas de errores en la página -->
        <?php foreach ($errores as $error) { ?>
                <div class="modal fade" data-bs-key="modal" tabindex="-1">
                        <div class="modal-dialog">
                                <div class="modal-content">
                                        <div class="modal-header">
                                                <h5 class="modal-title">Error!</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                                <p><?php echo $error; ?></p>
                                        </div>
                                        <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                        </div>
                                </div>
                        </div>
                </div>
        <?php } ?>
        <!-- Main -->
        <main class="flex-grow-1">

                <!-- División -->
                <div class="page-login">
                        <div class="login-container">
                                <div class="login-header">
                                        <div class="logo-login">
                                                <img src="/build/img/arco_iris_logo.svg" alt="Logo_AI">
                                        </div>
                                        <h2>Bienvenido a SySAI</h2>
                                        <p>Inicia sesión para continuar</p>
                                </div>
                                <form method="POST" class="form" action="/login">
                                        <!-- Campo de usuario -->
                                        <div class="mb-3">
                                                <label class="form-label" for="email">E-mail</label>
                                                <input class="form-control" name="email" type="email" placeholder="Ingresa tu correo electrónico" id="email" require>
                                        </div>
                                        <!-- Campo de contraseña -->
                                        <label for="password" class="form-label">Contraseña</label>
                                        <div class="mb-3 d-flex flex-row password-space">
                                                <input class="form-control" name="password" type="password" placeholder="Ingresa tu password" id="password" require>
                                                <button type="button" id="togglePassword"><i class="bi bi-eye-slash"></i></button>
                                        </div>
                                        <!-- Botón de inicio de sesión -->
                                        <div class="d-flex flex-row justify-content-center">
                                                <input type="submit" class="btn btn-login" value="Iniciar Sesión">
                                        </div>
                                </form>
                                <!-- Enlace de recuperación -->
                                <div class="footer text-start">
                                        <a href="/chgpsswd">¿Deseas cambiar tu contraseña?</a>
                                </div>
                        </div>
                </div>
        </main>
        <!-- Footer -->
        <footer class="text-center py-3 footer">
                <div>
                        <?php $fecha = date('Y'); ?>
                        <h6> Desarrollado por Cronos Soluciones - Todos los derechos reservados - <?php echo $fecha; ?></h6>
                </div>
        </footer>