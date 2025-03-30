<body class="page-change-password d-flex flex-column min-vh-100">
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
    <main class="container-fluid flex-grow-1">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-md-8 col-lg-6 col-xl-4 py-4 px-3 bg-white shadow-sm rounded login-container">
                <h1 class="titulo-login text-center">Cambio de Contraseña</h1>
                <p class="texto-login text-center">
                    Ingrese su email y recibirá un correo electrónico con un código de verificación.
                </p>
                <form method="POST" class="form" action="/chgpsswd">
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" name="email" id="email" placeholder="Tu correo aquí" require>
                    </div>
                    <button type="submit" class="btn btn-login w-100">Confirmar</button>
                </form>
                <div class="text-center mt-3">
                    <p class="texto-advertencia">
                        Recuerde que el cambio de contraseña debe ser aprobado por un administrador.
                    </p>
                </div>
            </div>
        </div>
    </main>
    <footer class="text-center py-3 footer">
        <div>
            <?php $fecha = date('Y'); ?>
            <h6>Desarrollado por Cronos Soluciones - Todos los derechos reservados - <?php echo $fecha; ?></h6>
        </div>
    </footer>