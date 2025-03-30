<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resumen Financiero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <style>
        .card {
            border-radius: 15px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }
        .card:hover {
            transform: scale(1.05);
        }
        .icon {
            font-size: 2rem;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="row">
        <!-- Ingresos Totales -->
        <div class="col-md-4">
            <div class="card text-white bg-success p-3" data-aos="fade-up">
                <div class="card-body text-center">
                    <i class="bi bi-arrow-up-circle icon"></i>
                    <h4 class="card-title">Ingresos Totales</h4>
                    <h2 id="ingresosTotales">$0.00</h2>
                </div>
            </div>
        </div>

        <!-- Egresos Totales -->
        <div class="col-md-4">
            <div class="card text-white bg-danger p-3" data-aos="fade-up" data-aos-delay="200">
                <div class="card-body text-center">
                    <i class="bi bi-arrow-down-circle icon"></i>
                    <h4 class="card-title">Egresos Totales</h4>
                    <h2 id="egresosTotales">$0.00</h2>
                </div>
            </div>
        </div>

        <!-- Saldo Total -->
        <div class="col-md-4">
            <div class="card text-white bg-primary p-3" data-aos="fade-up" data-aos-delay="400">
                <div class="card-body text-center">
                    <i class="bi bi-cash-stack icon"></i>
                    <h4 class="card-title">Saldo Total</h4>
                    <h2 id="saldoTotal">$0.00</h2>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
    AOS.init(); // Inicializar animaciones de entrada

    // Simulación de datos (reemplazar con valores reales desde el backend)
    let ingresos = 873645.88;
    let egresos = 2543.43;
    let saldo = ingresos - egresos;

    // Función para animar los números
    function animarContador(elemento, valorFinal, duracion) {
        let inicio = 0;
        let incremento = valorFinal / (duracion / 30); // Control de velocidad
        let contador = setInterval(() => {
            inicio += incremento;
            if (inicio >= valorFinal) {
                inicio = valorFinal;
                clearInterval(contador);
            }
            elemento.innerText = "S./" + inicio.toFixed(2);
        }, 30);
    }

    // Ejecutar la animación cuando la página cargue completamente
    document.addEventListener("DOMContentLoaded", function() {
        animarContador(document.getElementById("ingresosTotales"), ingresos, 100);
        animarContador(document.getElementById("egresosTotales"), egresos, 100);
        animarContador(document.getElementById("saldoTotal"), saldo, 100);
    });
</script>

</body>
</html>
