<?php
/**
 * LISTA_OBSERVACIONES.PHP
 * =======================
 * 
 * Página de visualización de observaciones y motivos de alumnos.
 * 
 * FUNCIONALIDADES:
 * - DataTable con listado de observaciones/motivos
 * - Columnas: #, Nombre y Apellido, Tipo, Observación/Motivo, Fecha y Hora
 * - Tipos: Inhabilitación, Reactivación, Retiro, Motivo de Retiro, Justificativo
 * - Ordenamiento por fecha descendente (más recientes primero)
 * - Botón para acceder a lista de agregados inhabilitados (solo administradores)
 * 
 * SCRIPT:
 * - functions-observaciones.js (visualización de observaciones)
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
?>
<main class="app-content">
  <div class="app-title">
    <div>
      <h1>
        <i class="fa-regular fa-file-lines"></i> Observaciones/Motivos
        <?php if ($_SESSION['rol'] == 1) { ?>
          <a href="lista_inhabilitados.php" class="btn btn-danger ml-2">Lista de agregados deshabilitados</a>
        <?php } ?>
      </h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="lista_observaciones.php">Observaciones/Motivos</a></li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tableObservaciones">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nombre y Apellido</th>
                  <th>Tipo</th>
                  <th>Observación/Motivo</th>
                  <th>Fecha y Hora</th>
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
<script src="js/functions-observaciones.js"></script>
