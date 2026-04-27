<?php
/**
 * LISTA_INSCRIPCIONES.PHP
 * =======================
 * 
 * Página de verificación y visualización de inscripciones del sistema escolar.
 * 
 * FUNCIONALIDADES:
 * - DataTable con listado de inscripciones
 * - Columnas: #, Cédula, Nombre del Alumno, Apellido, Grado, Sección, Turno, Período Escolar, Estatus, Acciones
 * - Acciones: Editar inscripción, Eliminar inscripción
 * - Visualización de inscripciones por período escolar
 * 
 * MODAL INCLUIDO:
 * - modal_inscripcion.php (editar inscripción)
 * 
 * SCRIPT:
 * - functions-inscripcion.js (gestión de inscripciones)
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
require_once 'includes/Modals/modal_inscripcion.php';
?>

<main class="app-content">
  <div class="app-title">
    <div>
      <h1><i class="fas fa-check-circle"></i>Verificar Inscritos</h1>

      <h1>


      </h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="lista_inscripciones.php">Lista de Inscripciones</a></li>
    </ul>

  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tableInscripciones">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Cédula</th>
                  <th>Nombre del Alumno</th>
                  <th>Apellido del Alumno</th>
                  <th>Grado</th>
                  <th>Sección</th>
                  <th>Turno</th>
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