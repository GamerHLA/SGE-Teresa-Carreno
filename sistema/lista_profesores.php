<?php
/**
 * LISTA_PROFESORES.PHP
 * ====================
 * 
 * Página de gestión y visualización de profesores del sistema escolar.
 * Solo accesible para administradores (rol == 1).
 * 
 * FUNCIONALIDADES:
 * - DataTable con listado de profesores
 * - Botón para crear nuevo profesor
 * - Columnas: #, Cédula, Nombre, Apellido, Sexo, Dirección, Teléfono, Correo, Nivel de Estudio, Estatus, Acciones
 * - Acciones: Editar, Inhabilitar/Activar profesor
 * 
 * MODAL INCLUIDO:
 * - modal_profesor.php (crear/editar profesor)
 * 
 * SCRIPT:
 * - functions-profesores.js (gestión de profesores)
 * 
 * DEPENDENCIAS:
 * - Sesión activa con rol de administrador
 */
session_start();
if (empty($_SESSION['active']) || $_SESSION['rol'] != 1) {
  header("Location: index.php");
}
require_once 'includes/session.php';
require_once 'includes/header.php';
require_once 'includes/Modals/modal_profesor.php';
?>
<main class="app-content">
  <div class="app-title">
    <div>
      <h1>
        <i class="app-menu__icon fas fa-chalkboard-teacher"></i> Añadir Profesor
        <button class="btn btn-primary" type="button" onclick="openModalProfesor()">Nuevo</button>
      </h1>
      <!-- <h1>
        <p style="margin-bottom: 20px;"></p>
        <button class="btn btn-success" type="button"
          onclick="window.open('Reportes/lista_profesor.php', '_blank')">Activos</button>
          <button class="btn btn-danger" type="button"
          onclick="window.open('Reportes/lista_profesorIN.php', '_blank')">Inactivos</button>

      </h1> -->
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="lista_profesores.php">Lista de Profesores</a></li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tableProfesores">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Cédula</th>
                  <th>Nombre</th>
                  <th>Apellido</th>
                  <th>Sexo</th>
                  <th>Dirección</th>
                  <th>Teléfono</th>
                  <th>Correo</th>
                  <th>Nivel de Estudio</th>
                  <th>Estatus</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>


<?php require_once 'includes/footer.php'; ?>