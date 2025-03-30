<?php
//Encabezado Layout general
if (!isset($_SESSION)) {
  session_start();
  $auth = $_SESSION['login'] ?? false;
}
//Otorgamos el valor de los nombres a la variable persona la inicio del LOAD de la página
$auth = $_SESSION['login'] ?? false;
$persona = $_SESSION['datos'] ?? false;
$cargo = $_SESSION['cargo'] ?? false;
?>
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
    <!-- FavIcon -->
    <link rel="icon" type="image/x-icon" href="/build/img/arco_iris_logo_pestania.svg">
    <!-- Bootstrap -->
    <link href="/build/css/bootstrap.min.css" rel="stylesheet">
    <!-- Styles CSS -->
    <link rel="stylesheet" href="/build/css/app.css">
    <!-- Bootstrap-Icons -->
    <link rel="stylesheet" href="/build/css/bootstrap-icons.min.css">

</head>

<body>
  <!-- Sidebar   -->
  <div class="container-fluid p-0">
    <div class="row">
      <div class="wrapper d-flex">
        <aside id="sidebar">
          <div class="d-flex sidebar-header">
            <div class="boton">
              <button class="toggle-btn" id="toggle-btn" type="button">
                <img src="/build/img/logo_last.png" alt="Icon">
              </button>
            </div>
            <div class="sidebar-logo">
              <p>Bienvenido, <span><?php echo $persona; ?></span></p>
              <p>Tu rol: <span><?php echo $cargo; ?></span></p>
            </div>
          </div>
          <ul class="sidebar-nav">
            <!-- Fuentes de financiamiento -->
            <li class="sidebar-item">
              <a href="/fuente_financiamiento/admin" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Fuentes de Financiamiento">
                <i class="bi bi-piggy-bank"></i>
                <span>Fuentes de financiamiento</span>
              </a>
            </li>
            <!-- Programas -->
            <li class="sidebar-item">
              <a href="#" class="sidebar-link has-dropdown collapsed" data-bs-toggle="collapse" data-bs-target="#programas" aria-expanded="false" aria-controls="programas">
                <i class="bi bi-file-earmark-ppt"></i>
                <span>Programas</span>
              </a>
              <ul id="programas" class="padre sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                <li class="sidebar-item">
                  <a href="/programa/admin" class="sidebar-link">
                    <i class="bi bi-card-checklist"></i>
                    <span>Programas</span>
                  </a>
                </li>
                <li class="sidebar-item">
                  <a href="/dfinanciamiento/crear" class="sidebar-link">
                    <i class="bi bi-intersect"></i>
                    <span>Relación F. Financiamiento</span>
                  </a>
                </li>
                <li class="sidebar-item">
                  <a href="/resultado/admin" class="sidebar-link">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Plan operativo anual POA</span>
                  </a>
                </li>
                <li class="sidebar-item">
                  <a href="/categoria_rubro/admin" class="sidebar-link">
                    <i class="bi bi-tags"></i>
                    <span>Categoría de Rubros</span>
                  </a>
                </li>
              </ul>
            </li>

            <!-- Rendicion de cuentas -->
            <li class="sidebar-item">
              <a href="/reporte/poarendicion" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Rendición de cuentas">
                <i class="bi bi-briefcase"></i>
                <span>Rendición de cuentas</span>
              </a>
            </li>
            <!-- Ingresos-Egresos -->
            <li class="sidebar-item">
              <a href="/ingreso_egreso/admin" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Ingresos/Egresos">
                <i class="bi bi-journal-plus"></i>
                <span>Ingresos-Egresos</span>
              </a>
            </li>
            <!-- Usuarios -->
            <li class="sidebar-item">
              <a href="/usuario/admin" class="sidebar-link" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Usuarios">
                <i class="bi bi-people"></i>
                <span>Usuarios</span>
              </a>
            </li>
            <!-- Contabilidad -->
            <li class="sidebar-item">
              <a href="#" class="sidebar-link has-dropdown collapsed" data-bs-toggle="collapse" data-bs-target="#contabilidad" aria-expanded="false" aria-controls="contabilidad">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>Contabilidad</span>
              </a>
              <ul id="contabilidad" class="padre sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                <li class="sidebar-item">
                  <a href="/saldos_contables/saldos" class="sidebar-link">
                    <i class="bi bi-clipboard-data"></i>
                    <span>Saldos contables</span>
                  </a>
                </li>
              </ul>
            </li>
            <!-- Tipo de cambio -->
            <li class="sidebar-item">
              <a href="#" class="sidebar-link has-dropdown collapsed" data-bs-toggle="collapse" data-bs-target="#tcambios" aria-expanded="false" aria-controls="tcambios">
                <i class="bi bi-currency-exchange"></i>
                <span>Tipo de Cambio</span>
              </a>
              <ul id="tcambios" class="padre sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                <li class="sidebar-item">
                  <a href="/tcambio/dolar/admin" class="sidebar-link">
                    <i class="bi bi-currency-dollar"></i>
                    <span>Dólares</span>
                  </a>
                </li>
                <li class="sidebar-item">
                  <a href="/tcambio/euro/admin" class="sidebar-link">
                    <i class="bi bi-currency-euro"></i>
                    <span>Euros</span>
                  </a>
                </li>
              </ul>
            </li>
            <!-- Reportes -->
            <li class="sidebar-item">
              <a href="#" class="sidebar-link has-dropdown collapsed" data-bs-toggle="collapse" data-bs-target="#reportes" aria-expanded="false" aria-controls="reportes">
                <i class="bi bi-journal-text"></i>
                <span>Reportes</span>
              </a>
              <ul id="reporte_poa" class="padre sidebar-dropdown list-unstyled collapse" data-bs-parent="# sidebar">
                <li class="sidebar-item">
                  <a href="/reporte/poa" class="sidebar-link">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Reporte POA</span>
                  </a>
                </li>
                <li class="sidebar-item">
                  <a href="/reporte/ingresos" class="sidebar-link">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Reporte Ingresos</span>
                  </a>
                </li>
                <li class="sidebar-item">
                  <a href="/reporte/rendiciones" class="sidebar-link">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Reporte Egresos</span>
                  </a>
                </li>
              </ul>
            </li>
          </ul>
          <div class="sidebar-footer">
            <a href="/logout" class="sidebar-link">
              <i class="bi bi-box-arrow-left"></i>
              <span>Cerrar sesión</span>
            </a>
          </div>
        </aside>
        <div class="container mt-3 ">
          <!-- Sección del contenido -->
          <?php echo $contenido; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="/build/js/bootstrap.bundle.min.js"></script>
<script src="/build/js/bundle.min.js"></script>
</body>

</html>