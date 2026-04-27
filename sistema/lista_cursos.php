<?php
/**
 * LISTA_CURSOS.PHP
 * ================
 * 
 * Página de gestión de grados y secciones (cursos) del sistema escolar.
 * 
 * FUNCIONALIDADES:
 * - DataTable con listado de cursos
 * - Botón para generar cursos predeterminados (1° a 6° grado, secciones A-D)
 * - Validación de período activo antes de crear cursos
 * - Validación de límite de 24 cursos predeterminados
 * - Columnas: #, Grado y Sección, Turno, Cupo, Profesor, Estatus, Acciones
 * - Acciones: Editar, Inhabilitar/Activar curso
 * 
 * MODAL INCLUIDO:
 * - modal_curso.php (crear/editar curso)
 * 
 * SCRIPT:
 * - functions-curso.js (gestión de cursos)
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
require_once 'includes/Modals/modal_curso.php';

// Obtener todos los periodos escolares
require_once 'includes/config.php';
$sqlPeriodos = "SELECT periodo_id, CONCAT(anio_inicio, ' - ', anio_fin) as periodo FROM periodo_escolar WHERE estatus != 0 ORDER BY anio_inicio DESC";
$queryPeriodos = $pdo->prepare($sqlPeriodos);
$queryPeriodos->execute();
$periodos = $queryPeriodos->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="app-content">
  <div class="app-title">
    <div>
      <h1>
        <i class="fa-solid fa-address-card"></i> Nuevo Grado/Sección
        <?php
        // Verificar si se debe deshabilitar el botón
        $disableCreateBtn = false;
        $btnTitle = "";

        // 1. Verificar periodo activo
        $sqlActiveP = "SELECT periodo_id FROM periodo_escolar WHERE estatus = 1 ORDER BY periodo_id DESC LIMIT 1";
        $qActiveP = $pdo->prepare($sqlActiveP);
        $qActiveP->execute();
        $activePeriod = $qActiveP->fetch(PDO::FETCH_ASSOC);

        if (!$activePeriod) {
          $disableCreateBtn = true;
          $btnTitle = "No hay período escolar activo";
        } else {
          // 2. Verificar si ya existen los 24 cursos predeterminados (1-6, A-D)
          $pId = $activePeriod['periodo_id'];
          $sqlCheckDefaults = "SELECT COUNT(*) as total FROM curso c
                                 INNER JOIN grados g ON c.grados_id = g.id_grado
                                 INNER JOIN seccion s ON c.seccion_id = s.id_seccion
                                 WHERE c.periodo_id = ? AND c.estatusC != 0 
                                 AND g.grado IN (1,2,3,4,5,6) 
                                 AND s.seccion IN ('A','B','C','D')";
          $qCheck = $pdo->prepare($sqlCheckDefaults);
          $qCheck->execute([$pId]);
          $countDefaults = $qCheck->fetch(PDO::FETCH_ASSOC);

          if ($countDefaults['total'] >= 24) {
            $disableCreateBtn = true;
            $btnTitle = "Ya existen todos los grados y secciones predeterminados";
          }
        }
        ?>
        <button class="btn btn-warning" type="button" onclick="generarCursosPredeterminados()" <?php echo $disableCreateBtn ? 'disabled' : ''; ?> title="<?php echo $btnTitle; ?>">Crear Grados/Secciones
          Predeterminados</button>
        <!-- <h1>
          
          <select id="selectPeriodo" class="form-control" style="display: inline-block; width: auto; margin-left: 10px;">
            <option value="">Todos los periodos</option>
            <?php foreach ($periodos as $periodo): ?>
              <option value="<?php echo $periodo['periodo_id']; ?>"><?php echo $periodo['periodo']; ?></option>
            <?php endforeach; ?>
          </select>
          
          <button class="btn btn-success" type="button" onclick="generarPDFInscripciones()">
            <i class="fas fa-file-pdf"></i> Generar PDF
          </button>
        </h1> -->
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="index.php"><i class="fa fa-home fa-lg"></i></a></li>
      <li class="breadcrumb-item"><a href="#">Grados/Secciones</a></li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="tableCursos">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Grado y Sección</th>
                  <th>Turno</th>
                  <th>Cupo</th>
                  <th>Profesor</th>
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

<script>
  // Función para generar PDF de inscripciones
  function generarPDFInscripciones() {
    const selectPeriodo = document.getElementById('selectPeriodo');
    const periodoId = selectPeriodo.value;

    let url = 'Reportes/lista_graysec.php';

    if (periodoId !== '') {
      url += '?periodo_id=' + periodoId;
    }

    window.open(url, '_blank');
  }

  // Evento para filtrar la tabla cuando cambie el periodo seleccionado
  document.addEventListener('DOMContentLoaded', function () {
    const selectPeriodo = document.getElementById('selectPeriodo');

    if (selectPeriodo) {
      selectPeriodo.addEventListener('change', function () {
        // Recargar la tabla con el nuevo filtro
        if (typeof tableCursos !== 'undefined') {
          tableCursos.ajax.reload(function () {
            // Reasignar eventos después de recargar
            setTimeout(function () {
              if (typeof delCurso === 'function') delCurso();
              if (typeof editCurso === 'function') editCurso();
            }, 100);
          });
        }
      });
    }
  });
</script>

<?php require_once 'includes/footer.php'; ?>