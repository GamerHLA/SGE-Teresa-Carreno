<?php
/**
 * LISTA_USUARIOS.PHP
 * ==================
 * 
 * Página de gestión de usuarios del sistema escolar.
 * 
 * FUNCIONALIDADES:
 * - DataTable con listado de usuarios del sistema
 * - Botón para crear nuevo usuario
 * - Columnas: #, Nombre, Usuario, Rol, Estatus, Acciones
 * - Acciones: Editar, Activar/Inhabilitar usuario
 * - Gestión de roles (Administrador/Asistente)
 * 
 * MODAL INCLUIDO:
 * - modal.php (crear/editar usuario)
 * 
 * SCRIPT:
 * - functions-usuarios.js (gestión de usuarios)
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
require_once 'includes/Modals/modal.php';
?>
<main class="app-content">
  <div class="app-title">
    <div>
      <h1>
        <i class="fa-solid fa-circle-user"></i> Lista de Usuarios
        <button class="btn btn-primary" type="button" onclick="openModal()">Nuevo</button>
      </h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="lista_usuarios.php">Lista de usuarios</a></li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tableUsuarios">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nombre</th>
                  <th>Usuario</th>
                  <th>Rol</th>
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