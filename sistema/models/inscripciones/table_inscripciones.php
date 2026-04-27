<?php

require_once '../../includes/config.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $periodo_id = isset($_GET['periodo_id']) && !empty($_GET['periodo_id']) ? intval($_GET['periodo_id']) : 0;
    $wherePeriodo = "";

    if ($periodo_id == 0) {
        // Si no se especifica periodo, buscar el activo
        $sqlPeriodo = "SELECT periodo_id FROM periodo_escolar WHERE estatus = 1 LIMIT 1";
        $queryPeriodo = $pdo->prepare($sqlPeriodo);
        $queryPeriodo->execute();
        $periodoActivo = $queryPeriodo->fetch(PDO::FETCH_ASSOC);

        if ($periodoActivo) {
            $periodo_id = $periodoActivo['periodo_id'];
        }
    }

    if ($periodo_id > 0) {
        $wherePeriodo = " AND pe.periodo_id = $periodo_id ";
    }

    // Consulta SQL mejorada con todos los campos necesarios
    $sql = "SELECT 
                i.inscripcion_id,
                i.alumno_id,
                i.curso_id,
                i.turno_id,
                i.estatusI,
                c.estatusC,
                pe.estatus as estatusPeriodo,
                a.nombre as alumno_nombre,
                a.apellido as alumno_apellido,
                CONCAT(a.nombre, ' ', a.apellido) as nombre,
                a.cedula as cedula_alumno,
                n.codigo as nacionalidad,
                g.grado,
                s.seccion,
                pe.periodo_id,
                CONCAT(pe.anio_inicio, ' - ', pe.anio_fin) as periodo_completo,
                t.tipo_turno as turno
            FROM inscripcion as i 
            INNER JOIN alumnos as a ON i.alumno_id = a.alumno_id 
            LEFT JOIN nacionalidades as n ON a.id_nacionalidades = n.id
            INNER JOIN curso as c ON i.curso_id = c.curso_id 
            INNER JOIN grados as g ON c.grados_id = g.id_grado
            INNER JOIN seccion as s ON c.seccion_id = s.id_seccion
            INNER JOIN periodo_escolar as pe ON c.periodo_id = pe.periodo_id 
            INNER JOIN turno as t ON i.turno_id = t.turno_id 
            WHERE i.estatusI != 0 
                AND pe.estatus = 1
                $wherePeriodo
            ORDER BY i.inscripcion_id DESC";

    $query = $pdo->prepare($sql);
    $query->execute();
    $data = $query->fetchAll(PDO::FETCH_ASSOC);

    for ($i = 0; $i < count($data); $i++) {
        // Guardar código de nacionalidad antes de formatear
        $codigoNacionalidad = $data[$i]['nacionalidad'] ?? '';

        // Formatear nacionalidad
        if ($codigoNacionalidad) {
            $data[$i]['nacionalidad'] = '<span class="badge badge-info">' . $codigoNacionalidad . '</span>';
        } else {
            $data[$i]['nacionalidad'] = '<span class="badge badge-secondary">N/A</span>';
        }

        // Formatear cédula con nacionalidad
        $cedulaCompleta = $codigoNacionalidad ? $codigoNacionalidad . '-' . $data[$i]['cedula_alumno'] : $data[$i]['cedula_alumno'];
        $data[$i]['cedula_alumno'] = $cedulaCompleta;

        // Separar nombre y apellido para las columnas individuales
        $data[$i]['nombre_alumno'] = $data[$i]['alumno_nombre'];
        $data[$i]['apellido_alumno'] = $data[$i]['alumno_apellido'];

        // Asegurar que grado y sección estén presentes
        $data[$i]['grado'] = $data[$i]['grado'] ? $data[$i]['grado'] . '°' : 'N/A';
        $data[$i]['seccion'] = $data[$i]['seccion'] ? $data[$i]['seccion'] : 'N/A';

        // Asegurar que turno esté presente
        $data[$i]['turno'] = $data[$i]['turno'] ? $data[$i]['turno'] : 'N/A';

        // Formatear estatus
        // Si el curso o el periodo están inactivos, la inscripción se muestra inactiva
        $isActivo = $data[$i]['estatusI'] == 1 && $data[$i]['estatusC'] == 1 && $data[$i]['estatusPeriodo'] == 1;

        if ($isActivo) {
            $data[$i]['estatusI'] = '<span class="badge badge-success">Activo</span>';
        } else {
            $data[$i]['estatusI'] = '<span class="badge badge-danger">Inactivo</span>';
        }

        if ($isActivo) {
            $data[$i]['options'] = '<div class="text-center"><button class="btn btn-dark btn-sm btnEditInscripcion" rl="' . $data[$i]['inscripcion_id'] . '" title="Editar"><i class="fas fa-pencil-alt"></i></button></div>';
        } else {
            $data[$i]['options'] = '<div class="text-center"><button class="btn btn-secondary btn-sm" disabled title="Inscripción Inactiva"><i class="fas fa-pencil-alt"></i></button></div>';
        }
    }
  
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // En caso de error, devolver array vacío para evitar romper DataTables
    error_log("Error en table_inscripciones.php: " . $e->getMessage());
    echo json_encode(array(), JSON_UNESCAPED_UNICODE);
}
die();