<?php
/**
 * LISTA_ALUMNOS.PHP
 * =================
 * 
 * Página de gestión y visualización de alumnos del sistema escolar.
 * Incluye dos vistas: lista general e información adicional médica.
 * 
 * FUNCIONALIDADES:
 * - Vista de lista de alumnos con DataTable
 * - Vista de información adicional médica de alumnos
 * - Botón para crear nuevo alumno
 * - Toggle entre ambas vistas con cambio de tema visual
 * - Verificación de director activo para ciertas funciones
 * - Breadcrumb dinámico según vista activa
 * - Ajuste automático de columnas al cambiar de vista
 * 
 * VISTAS:
 * 1. Lista de Alumnos:
 *    - Cédula, Nombre, Apellido, Sexo, Edad, Fecha Nac., Representante, Dirección, Estatus, Acciones
 * 2. Información Adicional:
 *    - Información médica, Enfermedades/Discapacidad, Talla uniforme, Actividades extra, Observaciones
 * 
 * MODALS INCLUIDOS:
 * - modal_alumnos.php (crear/editar alumno)
 * - modal_inscripcion.php (inscribir alumno)
 * - modal_info_alumno.php (información adicional)
 * 
 * SCRIPTS:
 * - functions-alumnos.js (gestión de alumnos)
 * - functions_info_alumnos.js (información adicional)
 * 
 * DEPENDENCIAS:
 * - includes/config.php (conexión BD)
 * - Sesión activa
 */
session_start();
if (empty($_SESSION['active'])) {
  header("Location: ../");
}
require_once 'includes/session.php';
require_once 'includes/header.php';
require_once 'includes/config.php';
require_once 'includes/Modals/modal_alumnos.php';
require_once 'includes/Modals/modal_inscripcion.php';
require_once 'includes/Modals/modal_info_alumno.php';

// Verificar si existe un director activo
$sqlDirector = "SELECT COUNT(*) as total FROM profesor WHERE es_director = 1";
$queryDirector = $pdo->prepare($sqlDirector);
$queryDirector->execute();
$hasDirector = $queryDirector->fetch(PDO::FETCH_ASSOC)['total'] > 0;
?>
<script>
    const hasDirector = <?php echo $hasDirector ? 'true' : 'false'; ?>;
</script>
<style>
    /* Custom Theme for Información Adicional Mode */
    .info-adicional-mode.app-content {
      background-color: #f3e5f5 !important; /* Light Purple background */
      transition: background-color 0.3s ease;
    }
    
    .info-adicional-mode .tile {
      border-top: 3px solid #000000ff !important; /* Purple accent border */
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
    }
    
    .info-adicional-mode .app-title {
      background: white !important; /* White background for header */
      color: black !important;
      border-bottom: 2px solid #0388a07e !important; /* Optional: keep a hint of the theme color at the bottom */
    }
    
    .info-adicional-mode .app-title h1 {
      color: black !important;
      text-shadow: none;
    }
    
    /* Specific table header styling for the info table */
    #seccionInfoAlumnos #tableInfoAlumnos thead tr {
      background-color: #0388a07e !important; /* Darker Purple for table header */
      color: black !important;
    }
    
    #seccionInfoAlumnos #tableInfoAlumnos thead th {
      border-bottom: 2px solid #000000ff !important;
      color: black !important;
    }

    /* Change button colors in this mode if desired */
    .info-adicional-mode .btn-info {
        background-color: #00897b !important; /* Teal for the toggle button */
        border-color: #00796b !important;
    }
</style>
<main class="app-content">
  <div class="app-title">
    <div>
      <h1>
        <i class="fa-solid fa-circle-info" id="iconoSeccion"></i> <span id="tituloSeccion">Crear y Ver Información de los Alumnos</span>
      </h1>
      <br>
      <p>
        <button class="btn btn-primary" type="button" onclick="openModalAlumno()" id="btnNuevoAlumno">Crear Alumno</button>
        <button class="btn btn-info" type="button" onclick="toggleVista()" id="btnToggleVista">
          <i class="fa-solid fa-info-circle"></i> Ver Información Adicional
        </button>
      </p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="lista_alumnos.php" id="breadcrumbLink">Lista De Alumnos</a></li>
    </ul>
  </div>

  <!-- SECCIÓN: LISTA DE ALUMNOS -->
  <div class="row" id="seccionListaAlumnos">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tableAlumnos">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Cédula</th>
                  <th>Nombre</th>
                  <th>Apellido</th>
                  <th>Sexo</th>
                  <th>Edad</th>
                  <th>Fecha_de_Nac.</th>
                  <th>Representante</th>
                  <th>Dirección</th>
                  <th>Estatus</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
          </div>
          </td>
          </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  </div>

  <!-- SECCIÓN: INFORMACIÓN ADICIONAL DE ALUMNOS -->
  <div class="row" id="seccionInfoAlumnos" style="display: none;">
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

<script>
// Función para alternar entre vistas
function toggleVista() {
  const seccionAlumnos = document.getElementById('seccionListaAlumnos');
  const seccionInfo = document.getElementById('seccionInfoAlumnos');
  const btnToggle = document.getElementById('btnToggleVista');
  const btnNuevo = document.getElementById('btnNuevoAlumno');
  const titulo = document.getElementById('tituloSeccion');
  const breadcrumb = document.getElementById('breadcrumbLink');
  const icono = document.getElementById('iconoSeccion');
  
  if (seccionAlumnos.style.display === 'none') {
    // Mostrar lista de alumnos
    seccionAlumnos.style.display = 'block';
    seccionInfo.style.display = 'none';
    btnToggle.innerHTML = '<i class="fa-solid fa-info-circle"></i> Ver Información Adicional';
    btnNuevo.style.display = 'inline-block';
    titulo.textContent = 'Lista de Alumnos';
    breadcrumb.textContent = 'Lista De Alumnos';
    icono.className = 'fa-solid fa-graduation-cap';
    
    // Ajustar tabla de alumnos con redibujado completo
    setTimeout(function() {
      if ($.fn.DataTable.isDataTable('#tableAlumnos')) {
        var table = $('#tableAlumnos').DataTable();
        table.columns.adjust().draw(false);
      }
    }, 150);
  } else {
    // Mostrar información adicional
    seccionAlumnos.style.display = 'none';
    seccionInfo.style.display = 'block';
    btnToggle.innerHTML = '<i class="fa-solid fa-list"></i> Ver Lista de Alumnos';
    btnNuevo.style.display = 'none';
    titulo.textContent = 'Información Adicional de Alumnos';
    breadcrumb.textContent = 'Información Adicional';
    icono.className = 'fa-solid fa-circle-info';
    
    // Inicializar tabla si no existe, luego ajustar
    setTimeout(function() {
      if (!$.fn.DataTable.isDataTable('#tableInfoAlumnos')) {
        // Inicializar la tabla por primera vez
        if (typeof window.initializeInfoTable === 'function') {
          window.initializeInfoTable();
        }
      } else {
        // Tabla ya existe, solo ajustar
        var table = $('#tableInfoAlumnos').DataTable();
        table.columns.adjust().draw(false);
      }
    }, 150);
  }
  
  // Toggle Custom Theme Class using classList
  const appContent = document.querySelector('.app-content');
  if (seccionAlumnos.style.display === 'none') {
      appContent.classList.add('info-adicional-mode');
  } else {
      appContent.classList.remove('info-adicional-mode');
  }
}
</script>

<?php require_once 'includes/footer.php'; ?>
<script src="js/functions-alumnos.js?v=<?= time(); ?>"></script>
<script src="js/functions_info_alumnos.js?v=<?= time(); ?>"></script>