<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="CronosSoluciones">
    <meta name="description" content="SysAi - Sistema de reportes contables para la ONG Arco Iris">
    <title>Verificando código</title>
    <!-- FavIcon -->
    <link rel="icon" type="image/x-icon" href="/build/img/arco_iris_logo_pestania.svg">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="/build/css/app.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

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
                <h1 class="titulo-login text-center">Verificación de código</h1>
                <p class="texto-login text-center">
                    Ingrese aquí el código de verificación que se le envió a su correo electrónico.
                </p>
                <form method="POST" class="form" action="/token_verify">
                    <div class="mb-3">
                        <label for="reset_token" class="form-label">Código de verificación</label>
                        <input type="text" class="form-control" name="reset_token" id="reset_token" placeholder="Código de verificación" require>
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
