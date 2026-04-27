<?php
/**
 * LISTA_INHABILITADOS.PHP
 * =======================
 * 
 * Dashboard de elementos inhabilitados del sistema escolar.
 * Solo accesible para administradores (rol == 1).
 * 
 * FUNCIONALIDADES:
 * - Visualización de todos los elementos inhabilitados del sistema
 * - Categorías mostradas:
 *   * Vacunas inhabilitadas
 *   * Enfermedades inhabilitadas
 *   * Discapacidades inhabilitadas
 *   * Parentescos inhabilitados
 *   * Motivos de retiro inhabilitados
 *   * Motivos de asistencia inhabilitados
 *   * Niveles de estudio y especialidades inhabilitadas
 * - Botón de reactivación para cada elemento
 * - Organización por tarjetas (tiles) con iconos diferenciados
 * 
 * SCRIPT:
 * - functions-inhabilitados.js (gestión de reactivaciones)
 * 
 * DEPENDENCIAS:
 * - Sesión activa con rol de administrador
 */
session_start();
if (empty($_SESSION['active']) || $_SESSION['rol'] != 1) {
  header("Location: ../");
  exit();
}
require_once 'includes/session.php';
require_once 'includes/header.php';
?>
<main class="app-content">
  <div class="app-title">
    <div>
      <h1>
        <i class="fas fa-ban"></i> Lista de Agregados Inhabilitados
      </h1>
      <p>Vista general de elementos desactivados en los Agregados</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="lista_observaciones.php">Observaciones/Motivos</a></li>
      <li class="breadcrumb-item">Lista de Inhabilitados</li>
    </ul>
  </div>

  <div class="row">
    <!-- Vacunas -->
    <div class="col-md-6 mb-4">
      <div class="tile">
        <h3 class="tile-title text-danger"><i class="fas fa-syringe"></i> Vacunas</h3>
        <div class="tile-body">
          <div id="container-vacunas" class="list-group">
            <div class="text-center p-3 text-muted">Cargando...</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Enfermedades -->
    <div class="col-md-6 mb-4">
      <div class="tile">
        <h3 class="tile-title text-danger"><i class="fas fa-virus"></i> Enfermedades</h3>
        <div class="tile-body">
          <div id="container-enfermedades" class="list-group">
            <div class="text-center p-3 text-muted">Cargando...</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Discapacidades -->
    <div class="col-md-6 mb-4">
      <div class="tile">
        <h3 class="tile-title text-danger"><i class="fas fa-wheelchair"></i> Discapacidades</h3>
        <div class="tile-body">
          <div id="container-discapacidades" class="list-group">
            <div class="text-center p-3 text-muted">Cargando...</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Parentesco -->
    <div class="col-md-6 mb-4">
      <div class="tile">
        <h3 class="tile-title text-danger"><i class="fas fa-users"></i> Parentesco</h3>
        <div class="tile-body">
          <div id="container-parentesco" class="list-group">
            <div class="text-center p-3 text-muted">Cargando...</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Motivos de Retiro -->
    <div class="col-md-6 mb-4">
      <div class="tile">
        <h3 class="tile-title text-danger"><i class="fas fa-sign-out-alt"></i> Motivos de Retiro</h3>
        <div class="tile-body">
          <div id="container-motivos-retiro" class="list-group">
            <div class="text-center p-3 text-muted">Cargando...</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Motivos de Asistencia -->
    <div class="col-md-6 mb-4">
      <div class="tile">
        <h3 class="tile-title text-danger"><i class="fas fa-clipboard-check"></i> Motivos de Asistencia</h3>
        <div class="tile-body">
          <div id="container-motivos-asistencia" class="list-group">
            <div class="text-center p-3 text-muted">Cargando...</div>
          </div>
        </div>
      </div>
    </div>


    <!-- Niveles de Estudio y Especialidades -->
    <div class="col-md-12 mb-4">
      <div class="tile">
        <h3 class="tile-title text-danger"><i class="fas fa-graduation-cap"></i> Niveles de Estudio / Especialidades</h3>
        <div class="tile-body">
          <div class="row" id="container-estudios">
            <div class="col-12 text-center p-3 text-muted">Cargando...</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once 'includes/footer.php'; ?>
<script src="js/functions-inhabilitados.js"></script>
