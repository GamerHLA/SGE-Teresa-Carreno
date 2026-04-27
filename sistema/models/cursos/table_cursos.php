<?php
require_once '../../includes/config.php';

$sql = "SELECT 
            c.curso_id,
            g.grado,
            s.seccion,
            c.cupo,
            c.estatusC,
            CONCAT(g.grado, '° - Sección ', s.seccion) as grado_seccion,
            t.turno_id,
            t.tipo_turno as turno,
            p.profesor_id, 
            CONCAT(p.nombre, ' ', p.apellido) as profesor_nombre, 
            p.estatus as profesor_estatus,
            pe.periodo_id,
            CONCAT(pe.anio_inicio, ' - ', pe.anio_fin) as periodo
        FROM curso as c 
        INNER JOIN grados as g ON c.grados_id = g.id_grado
        INNER JOIN seccion as s ON c.seccion_id = s.id_seccion
        LEFT JOIN profesor as p ON c.profesor_id = p.profesor_id 
        INNER JOIN turno as t ON c.turno_id = t.turno_id 
        INNER JOIN periodo_escolar as pe ON c.periodo_id = pe.periodo_id
        WHERE c.estatusC != 0 AND pe.estatus = 1";

// Subconsulta para contar inscritos reales (estatusI = 1)
$sql = "SELECT 
            c.curso_id,
            g.grado,
            s.seccion,
            c.cupo as total_cupo,
            c.estatusC,
            CONCAT(g.grado, '° - Sección ', s.seccion) as grado_seccion,
            t.turno_id,
            t.tipo_turno as turno,
            p.profesor_id, 
            CONCAT(p.nombre, ' ', p.apellido) as profesor_nombre, 
            p.estatus as profesor_estatus,
            pe.periodo_id,
            CONCAT(pe.anio_inicio, ' - ', pe.anio_fin) as periodo,
            (SELECT COUNT(*) FROM inscripcion i WHERE i.curso_id = c.curso_id AND i.estatusI = 1) as inscritos
        FROM curso as c 
        INNER JOIN grados as g ON c.grados_id = g.id_grado
        INNER JOIN seccion as s ON c.seccion_id = s.id_seccion
        LEFT JOIN profesor as p ON c.profesor_id = p.profesor_id 
        INNER JOIN turno as t ON c.turno_id = t.turno_id 
        INNER JOIN periodo_escolar as pe ON c.periodo_id = pe.periodo_id
        WHERE c.estatusC != 0 AND pe.estatus = 1";

// Filtrar por periodo si se proporciona (puede venir por GET o POST)
$periodo_id = null;
if (isset($_GET['periodo_id']) && $_GET['periodo_id'] != '') {
    $periodo_id = intval($_GET['periodo_id']);
} elseif (isset($_POST['periodo_id']) && $_POST['periodo_id'] != '') {
    $periodo_id = intval($_POST['periodo_id']);
}

if ($periodo_id) {
    $sql .= " AND pe.periodo_id = :periodo_id";
}

// Agregar ordenamiento por Grado y Sección
$sql .= " ORDER BY g.grado ASC, s.seccion ASC";

if ($periodo_id) {
    $query = $pdo->prepare($sql);
    $query->bindParam(':periodo_id', $periodo_id, PDO::PARAM_INT);
    $query->execute();
} else {
    $query = $pdo->prepare($sql);
    $query->execute();
}

$data = $query->fetchAll(PDO::FETCH_ASSOC);

for ($i = 0; $i < count($data); $i++) {
    // Formatear estado del curso
    // Ocultar profesor si no está activo o vacío
    $professorIsInvalid = false;
    if (empty($data[$i]['profesor_nombre'])) {
        $data[$i]['profesor_nombre'] = '<span class="badge badge-warning">Debe asignar profesor</span>';
        $professorIsInvalid = true;
    } elseif ($data[$i]['profesor_estatus'] != 1) {
        $data[$i]['profesor_nombre'] .= ' <br> <strong>(Profesor Anterior)</strong> <br> <span class="badge badge-warning">Debe asignar profesor</span>';
        $professorIsInvalid = true;
    }

    // Formatear estado del curso (Override if professor is invalid)
    if ($professorIsInvalid) {
        $data[$i]['estatusC'] = '<span class="badge badge-danger">Inactivo</span>';
    } elseif ($data[$i]['estatusC'] == 1) {
        $data[$i]['estatusC'] = '<span class="badge badge-success">Activo</span>';
    } else {
        $data[$i]['estatusC'] = '<span class="badge badge-danger">Inactivo</span>';
    }

    // Formatear turno (primera letra mayúscula)
    $data[$i]['turno'] = ucfirst($data[$i]['turno']);

    // Formatear cupo
    $total = intval($data[$i]['total_cupo']);
    $inscritos = intval($data[$i]['inscritos']);
    $disponible = $total - $inscritos;

    if ($total <= 0) {
        $data[$i]['cupo'] = 'Total: <span class="text-danger">0</span>';
    } else {
        $data[$i]['cupo'] = "Total: <b>$total</b> / Disp: <b>$disponible</b>";
    }

    // Define buttons based on status
    $btnEdit = '<button class="btn btn-dark btn-sm btnEditCurso" rl="' . $data[$i]['curso_id'] . '" title="Editar">
            <i class="fas fa-pencil-alt"></i>
        </button>';
    $btnActivate = '<button class="btn btn-success btn-sm btnActivateCurso" rl="' . $data[$i]['curso_id'] . '" title="Activar"><i class="fas fa-check"></i></button>';
    $btnInactivate = '<button class="btn btn-danger btn-sm btnDelCurso" rl="' . $data[$i]['curso_id'] . '" title="Inhabilitar">
            <i class="fas fa-ban" aria-hidden="true"></i>
        </button>';

    // Build options based on active status
    if ($data[$i]['estatusC'] == '<span class="badge badge-success">Activo</span>') {
        $data[$i]['options'] = '<div class="text-center">'
            . $btnEdit
            . ' ' . $btnInactivate
            . '</div>';
    } else {
        $data[$i]['options'] = '<div class="text-center">'
            . $btnEdit
            . ' ' . $btnActivate
            . '</div>';
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
die();