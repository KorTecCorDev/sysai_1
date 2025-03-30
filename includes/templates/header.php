<?php
    if (!isset($_SESSION)) {
        session_start();
    }
    $auth=$_SESSION['login'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysArcoIris</title>
    <!-- FavIcon -->
    <link rel="icon" type="image/x-icon" href="../public/build/img/logo.svg">
    <!-- Styles CSS -->
    <link rel="stylesheet" href="/build/css/app.css">
</head>
<body>
    <header class="header <?php echo $inicio ? 'inicio' : ''; ?>">
        <div class="contenedor contenido-header">
            <div class="barra">
                <a href="/">
                    <img src="/build/img/logo.svg" alt="Logotipo_de_Arco_Iris">
                </a>
            </div> <!-- .barra -->
        </div>
    </header>
    <!-- Sidebar -->
    <div class="wrapper">
    <aside id="sidebar">
      <div class="d-flex">
        <button class="toggle-btn" id="toggle-btn" type="button">
          <i class="bi bi-grid-fill"></i>
        </button>
        <div class="sidebar-logo">
          <a href="#">SysAI</a>
          <span>Te da la bienvenida Usuario</span>
        </div>
        
      </div>
      <ul class="sidebar-nav">
        <!-- Perfil -->
        <li class="sidebar-item">
          <a href="#" class="sidebar-link">
            <i class="bi bi-person-circle"></i>
            <span>Perfil</span>
          </a>
        </li>
        <!-- Fuentes de financiamiento -->
        <li class="sidebar-item">
          <a href="#" class="sidebar-link">
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
          <ul id="programas" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
              <li class="sidebar-item">
                <a href="#" class="sidebar-link">
                  <i class="bi bi-file-earmark-text"></i>
                  Plan operativo anual POA
                </a>
              </li>
              <li class="sidebar-item">
                <a href="#" class="sidebar-link">
                  <i class="bi bi-bookmark-check"></i>
                  Rubros
                </a>
              </li>
              <li class="sidebar-item">
                <a href="#" class="sidebar-link">
                  <i class="bi bi-tags"></i>
                  Categoría de Rubros
                </a>
              </li>
          </ul>
         </li>
        
        <!-- Rendicion de cuentas -->
        <li class="sidebar-item">
          <a href="#" class="sidebar-link">
            <i class="bi bi-files"></i>
            <span>Rendición de cuentas</span>
          </a>
        </li>
        <!-- Ingresos-Egresos -->
        <li class="sidebar-item">
          <a href="#" class="sidebar-link">
            <i class="bi bi-journal-plus"></i>
            <span>Ingresos-Egresos</span>
          </a>
        </li>
        <!-- Usuarios -->
        <li class="sidebar-item">
          <a href="#" class="sidebar-link">
            <i class="bi bi-people"></i>
            <span>Usuarios</span>
          </a>
        </li>
        <!-- Contabilidad -->
        <li class="sidebar-item">
            <a href="#" class="sidebar-link has-dropdown collapsed" data-bs-toggle="collapse" data-bs-target="#contabilidad" aria-expanded="false" aria-controls="contabilidad">
              <i class="bi bi-file-earmark-ppt"></i>
                <span>Contabilidad</span>
            </a>
          <ul id="contabilidad" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
            <li class="sidebar-item">
              <a href="#" class="sidebar-link">
                <i class="bi bi-file-earmark-bar-graph"></i>
                Saldos contables
              </a>
            </li>
          </ul>
        </li>
      </ul>
      <div class="sidebar-footer">
        <a href="#" class="sidebar-link">
          <i class="bi bi-box-arrow-left"></i>
          <span>Cerrar sesión</span>
        </a>
      </div>
    </aside>
    <div class="main p-3">
      <div class="text-center">
        <h1>
          Sidebar SysAI 
        </h1>
      </div>
    </div>
  </div>
  <!-- Fin Sidebar -->