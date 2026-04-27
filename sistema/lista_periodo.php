<?php
/**
 * LISTA_PERIODO.PHP
 * =================
 * 
 * Página de gestión de períodos escolares del sistema.
 * 
 * FUNCIONALIDADES:
 * - DataTable con listado de períodos escolares
 * - Botón para crear nuevo período
 * - Columnas: #, Período Escolar (año inicio - año fin), Estatus, Acciones
 * - Acciones: Editar, Activar/Inhabilitar período
 * - Solo un período puede estar activo a la vez
 * 
 * MODAL INCLUIDO:
 * - modal_periodo.php (crear/editar período)
 * 
 * SCRIPT:
 * - functions-periodo.js (gestión de períodos)
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
require_once 'includes/Modals/modal_periodo.php';
?>

<main class="app-content">
  <div class="app-title">
    <div>
      <h1>
        <i class="fa-solid fa-clipboard-list"></i> Crear Período Escolar
        <button class="btn btn-primary" type="button" onclick="openModalPeriodo()">Nuevo</button>
      </h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="lista_periodo.php">Lista de Períodos escolares</a></li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tablePeriodos">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Período Escolar</th>
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