<?php
/**
 * HEADER.PHP
 * ==========
 * 
 * Encabezado HTML principal del sistema escolar.
 * Incluido en todas las páginas del sistema.
 * 
 * COMPONENTES:
 * - Meta tags y configuración HTML5
 * - Favicon del sistema
 * - Enlaces a hojas de estilo (CSS principal, FontAwesome, Select2)
 * - Estilos personalizados para el encabezado
 * - Barra de navegación superior (header)
 * - Logo del sistema
 * - Menú de usuario con opciones:
 *   * Inicio
 *   * Observaciones
 *   * Usuarios (solo administradores)
 *   * Salir
 * - Inclusión de navegación lateral (nav.php)
 * 
 * DEPENDENCIAS:
 * - nav.php (navegación lateral)
 * - Sesión activa con variable $_SESSION['rol']
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="description" content="Sistema de cursos">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#ffffffff">
  <link rel="icon" type="image/png" href="../images/logo.png">
  <title>SISTEMA ESCOLAR</title>
  <!-- Main CSS-->
  <link rel="stylesheet" type="text/css" href="css/style.css">
  <link rel="stylesheet" type="text/css" href="css/main.css">
  <!-- Font-icon css -->
  <link rel="stylesheet" type="text/css" href="../css/vendor/fontawesome/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <!-- Estilos personalizados para el encabezado -->
  <style>
    .app-header__logo {
      background-color: #060627ff;
      font-family: Calibri, sans-serif !important;
      padding: 0 5px;
      border-radius: 0px;
      color: white;
      font-weight: bold;
      margin-left: 0px;
      /* Espacio entre el botón y el logo */
    }

    .header-content {
      display: flex;
      align-items: center;
    }
  </style>
</head>

<body class="app sidebar-mini">
  <!-- Navbar-->
  <header class="app-header">
    <div class="header-content">
      <!-- Sidebar toggle button--><a class="app-sidebar__toggle" href="#" data-toggle="sidebar"
        aria-label="Hide Sidebar"><i class="fas fa-bars"></i></a>
      <!-- Logo después del botón -->
      <a class="app-header__logo" href="./index.php">Sistema Escolar</a>
    </div>

    <!-- Navbar Right Menu-->
    <ul class="app-nav">
      <!-- User Menu-->
      <li class="dropdown"><a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Open Profile Menu"><i
            class="fa fa-user fa-lg"></i></a>
        <ul class="dropdown-menu settings-menu dropdown-menu-right">
          <li><a class="dropdown-item" href="./index.php"><i class="fa fa-th-list" aria-hidden="true"></i> Inicio</a>
          </li>

          <li><a class="dropdown-item" href="lista_observaciones.php"><i class="fa fa-info" aria-hidden="true"></i> Observaciones</a></li>

          <?php if ($_SESSION['rol'] == 1) { ?>
            <li><a class="dropdown-item" href="Lista_usuarios.php"><i class="fa fa-user fa-lg"></i> Usuarios</a></li>
          <?php } ?>
          
          <li><a class="dropdown-item" href="logout.php"><i class="fa fa-arrow-left" aria-hidden="true"></i>
              Salir</a></li>
        </ul>
      </li>
    </ul>
  </header>
  <?php require_once 'nav.php'; ?>