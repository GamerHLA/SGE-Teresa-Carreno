<?php
/**
 * NAV.PHP
 * =======
 * 
 * Navegación lateral (sidebar) del sistema escolar.
 * Incluido en todas las páginas a través de header.php.
 * 
 * COMPONENTES:
 * - Información del usuario actual (rol y nombre)
 * - Avatar del usuario
 * - Menú lateral con opciones según rol:
 *   * Usuarios (solo administradores)
 *   * Representantes (todos)
 *   * Alumnos (todos)
 *   * Profesores (solo administradores)
 *   * Períodos Escolares (solo administradores)
 *   * Grados y Secciones (solo administradores)
 *   * Inscripciones (administradores y asistentes)
 *   * Reportes (todos)
 *   * Observaciones/Motivos (todos)
 *   * Salir (todos)
 * 
 * CONTROL DE ACCESO:
 * - $_SESSION['rol'] == 1: Administrador (acceso completo)
 * - $_SESSION['rol'] == 2: Asistente (acceso limitado)
 * 
 * DEPENDENCIAS:
 * - Sesión activa con variables $_SESSION['rol'], $_SESSION['rol_name'], $_SESSION['nombre']
 */
?>
<!-- Sidebar menu-->
<div class="app-sidebar__overlay" data-toggle="sidebar"></div>
<aside class="app-sidebar">
  <div class="app-sidebar__user"><img class="app-sidebar__user-avatar" src="./images/usuarioB1.png"
      alt="usuario imagen">
    <div>
      <p class="app-sidebar__user-name"><?php echo $_SESSION['rol_name'] ?></p>
      <p class="app-sidebar__user-designation"><?php echo $_SESSION['nombre']; ?></p>
    </div>
  </div>
  <ul class="app-menu">
    <?php if ($_SESSION['rol'] == 1) { ?>
      <li class="treeview">
        <a class="app-menu__item" href="lista_usuarios.php">
          <i class="app-menu__icon fa fa-user-circle" aria-hidden="true" style="margin-right: -2px;"></i>
          <span class="app-menu__label">Usuarios</span>
        </a>
      </li>
    <?php } ?>
    <li class="treeview">
      <a class="app-menu__item" href="lista_representantes.php">
        <i class="app-menu__icon fa fa-user-tie" aria-hidden="true" style="margin-right: -2px;"></i>
        <span class="app-menu__label">Representantes</span>
      </a>
    </li>
    <li class="treeview">
      <a class="app-menu__item" href="lista_alumnos.php">
        <i class="app-menu__icon fa fa-user-graduate" aria-hidden="true" style="margin-right: -2px;"></i>
        <span class="app-menu__label">Alumnos</span>
      </a>
    </li>
    <!-- <li class="treeview">
      <a class="app-menu__item" href="lista_info_alumnos.php">
        <i class="app-menu__icon fa-solid fa-circle-info"></i>
        <span class="app-menu__label">Información de alumnos</span>
      </a>
    </li> -->
    <?php if ($_SESSION['rol'] == 1) { ?>
    <li class="treeview">
      <a class="app-menu__item" href="lista_profesores.php">
        <i class="app-menu__icon fas fa-chalkboard-teacher" aria-hidden="true" style="margin-right: -2px;"></i>
        <span class="app-menu__label">Profesores</span>
      </a>
    </li>
    <?php } ?>
    <li>
      <?php if ($_SESSION['rol'] == 1) { ?>
        <a class="app-menu__item" href="lista_periodo.php">
          <i class="fa fa-clipboard" aria-hidden="true" style="margin-right: 12px;"></i>
          <span class="app-menu__label">Períodos Escolares</span>
        </a>
      </li>
    <?php } ?>
    <li>
      <?php if ($_SESSION['rol'] == 1) { ?>
        <a class="app-menu__item" href="lista_cursos.php">
          <i class="fa fa-users" aria-hidden="true" style="margin-right: 5px;"></i>
          <span class="app-menu__label">Grados y Secciones</span>
        </a>
      </li>
    <?php } ?>

    <?php if ($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2) { ?>
      <li>
        <a class="app-menu__item" href="lista_inscripciones.php">
          <i class="app-menu__icon fas fa-check-circle" aria-hidden="true" style="margin-right: -4px;"></i>
          <span class="app-menu__label">Inscripciones</span>
        </a>
      </li>
    <?php } ?>

    <li>
      <a class="app-menu__item" href="reportes.php">
        <i class="app-menu__icon fa fa-file-pdf" aria-hidden="true" style="margin-right: -5px;"></i>
        <span class="app-menu__label">Reportes</span>
      </a>
    </li>
    <li class="treeview">
      <a class="app-menu__item" href="lista_observaciones.php">
        <i class="fa fa-info" aria-hidden="true" style="margin-right: 10px;"></i>
        <span class="app-menu__label">Observaciones/Motivos</span>
      </a>
    </li>
    <li>
      <a class="app-menu__item" href="logout.php">
        <i class="app-menu__icon fa fa-sign-out"></i>
        <span class="app-menu__label">Salir</span>
      </a>
    </li>
  </ul>
</aside>