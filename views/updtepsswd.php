<!-- Este es un comentario de prueba en la primera branch de git -->
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
                <h1 class="titulo-login text-center">Actualizando Contraseña</h1>
                <p class="texto-login text-center">
                    Ingrese su nueva contraseña
                </p>
                <form method="POST" class="form">
                    <div class="mb-3">
                        <label for="password" class="form-label">Ingrese su nueva contraseña</label>
                        <div class="password-space">
                        <input type="text" class="form-control" name="password" id="password" placeholder="Nueva contraseña" require>
                        <button type="button" id="togglePassword"><i class="bi bi-eye-slash"></i></button>
                        </div> 
                    </div>
                    <button type="submit" class="btn btn-login w-100">Confirmar</button>
                </form>
                <div class="text-center mt-3">
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
