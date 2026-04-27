<?php
session_start();
if (empty($_SESSION['active'])) {
  header("Location: ../");
}
require_once 'includes/session.php';
require_once 'includes/header.php';
require_once 'includes/Modals/modal_info_alumno.php';
?>
<main class="app-content">
  <div class="app-title">
    <div>
      <h1>
        <i class="app-menu__icon fa-solid fa-circle-info"></i> Información de los Alumnos
      </h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="lista_info_alumnos.php">Lista De Información de Alumnos</a></li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tableInfoAlumnos">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nombre y Apellido</th>
                  <th>Información Médica Adicional</th>
                  <th>Enfermedades o Discapacidad</th>
                  <th>Talla para uniforme</th>
                  <th>Actividad extra que realiza</th>
                  <th>Observaciones del Alumno</th>

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
<script src="js/functions_info_alumnos.js?v=<?= time(); ?>"></script>