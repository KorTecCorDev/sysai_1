<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Metadatos -->
    <meta charset="UTF-8">
    <meta name="author" content="CronosSoluciones">
    <meta name="description" content="SysAi - Sistema de reportes contables para la ONG Arco Iris">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título -->
    <title>SysAI</title>
    <!-- Icon -->
    <link rel="icon" type="image/x-icon" href="/build/img/arco_iris_logo_pestania.svg">
    <!-- Bootstrap -->
    <link href="/build/css/bootstrap.min.css" rel="stylesheet">
    <!-- Styles CSS -->
    <link rel="stylesheet" href="/build/css/app.css">
    <!-- Bootstrap-Icons -->
    <link rel="stylesheet" href="/build/css/bootstrap-icons.min.css">
    <title>Error 404 - Página no encontrada</title>
    <style>
        body {
            background-color: #fef8e6;
            font-family: Arial, sans-serif;
        }

        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            color: #555;
        }

        .error-title {
            font-size: 6rem;
            font-weight: bold;
            color: #f39c12;
            animation: bounce 1.5s infinite ease-in-out;
        }

        .error-message {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .back-button {
            background-color: #3498db;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: #2980b9;
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }
    </style>
</head>

<body>
    <div class="error-container">
        <h1 class="error-title">404</h1>
        <p class="error-message">¡Oops! Parece que esta página no es correcta.</p>
        <a href="/" class="btn back-button">Volver al inicio</a>
    </div>
    <script src="/build/js/bootstrap.bundle.min.js"></script>
    <script src="/build/js/bundle.min.js"></script>
</body>

</html>