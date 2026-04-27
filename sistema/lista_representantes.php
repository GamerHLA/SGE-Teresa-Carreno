<?php
/**
 * LISTA_REPRESENTANTES.PHP
 * ========================
 * 
 * Página de gestión y visualización de representantes del sistema escolar.
 * 
 * FUNCIONALIDADES:
 * - DataTable con listado de representantes
 * - Botón para crear nuevo representante
 * - Columnas: #, Cédula, Nombre, Apellido, Sexo, Dirección, Teléfono, Correo, Estatus, Acciones
 * - Acciones: Editar, Inhabilitar/Activar, Ver alumnos asociados
 * 
 * MODAL INCLUIDO:
 * - modal_representantes.php (crear/editar representante)
 * 
 * SCRIPT:
 * - functions-representantes.js (gestión de representantes)
 * 
 * DEPENDENCIAS:
 * - Sesión activa
 */
session_start();
if (empty($_SESSION['active'])) {
  header("Location: ../");
}
require_once 'includes/session.php';
require_once 'includes/header.php';
require_once 'includes/Modals/modal_representantes.php';
?>
<main class="app-content">
  <div class="app-title">
    <div>
      <h1>
        <i class="app-menu__icon fa fa-user"></i> Añadir Representante
        <button class="btn btn-primary" type="button" onclick="openModalRepresentantes()">Nuevo</button>
      </h1>
      <!-- <h1>
         
        <button class="btn btn-success" type="button"
          onclick="window.open('Reportes/lista_representante.php', '_blank')">Activos</button>
          <button class="btn btn-danger" type="button"
          onclick="window.open('Reportes/lista_representanteIN.php', '_blank')">Inactivos</button>

          
      </h1> -->
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="#">Representantes</a></li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tableRepresentantes">
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