<?php

require_once '../../includes/config.php'; // Incluye el archivo de configuración de la base de datos
error_reporting(0);

try {
    // Consulta SQL para obtener todos los alumnos con estatus diferente de 0 (no eliminados)
    // Incluye JOIN con alumno_representante y representantes para obtener nombre y parentesco
    // Y JOIN con tablas de dirección
    $sql = "SELECT 
                a.*,
                r.nombre as rep_nombre,
                r.apellido as rep_apellido,
                r.cedula as rep_cedula,
                r.estatus as rep_estatus,
                p_rel.parentesco as parentesco,
                r2.nombre as rep2_nombre,
                r2.apellido as rep2_apellido,
                r2.cedula as rep2_cedula,
                r2.estatus as rep2_estatus,
                n.codigo as rep_nacionalidad,
                n2.codigo as rep2_nacionalidad,
                p_rel2.parentesco as parentesco2,
                ar.estatus as rel_estatus,
                ar2.estatus as rel2_estatus,
                e.estado as nombre_estado,
                c.ciudad as nombre_ciudad,
                m.municipio as nombre_municipio,
                p.parroquia as nombre_parroquia,

                (SELECT COUNT(*) FROM inscripcion i WHERE i.alumno_id = a.alumno_id AND i.estatusI != 0) as is_inscrito,
                (SELECT i.inscripcion_id FROM inscripcion i INNER JOIN curso c ON i.curso_id = c.curso_id INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id WHERE i.alumno_id = a.alumno_id AND i.estatusI = 1 AND pe.estatus = 1 LIMIT 1) as active_inscripcion_id
            FROM alumnos a
            LEFT JOIN alumno_representante ar ON a.alumno_id = ar.alumno_id AND ar.es_principal = 1
            LEFT JOIN representantes r ON ar.representante_id = r.representantes_id
            LEFT JOIN nacionalidades n ON r.id_nacionalidades = n.id
            LEFT JOIN parentesco p_rel ON ar.parentesco_id = p_rel.id_parentesco
            LEFT JOIN alumno_representante ar2 ON a.alumno_id = ar2.alumno_id AND ar2.es_principal = 0
            LEFT JOIN representantes r2 ON ar2.representante_id = r2.representantes_id
            LEFT JOIN nacionalidades n2 ON r2.id_nacionalidades = n2.id
            LEFT JOIN parentesco p_rel2 ON ar2.parentesco_id = p_rel2.id_parentesco
            LEFT JOIN estados e ON a.id_estado = e.id_estado
            LEFT JOIN ciudades c ON a.id_ciudad = c.id_ciudad
            LEFT JOIN municipios m ON a.id_municipio = m.id_municipio
            LEFT JOIN parroquias p ON a.id_parroquia = p.id_parroquia
            WHERE a.estatus != 0";

    $query = $pdo->prepare($sql); // Prepara la consulta SQL para ejecución
    $query->execute(); // Ejecuta la consulta sin parámetros adicionales
    $data = $query->fetchAll(PDO::FETCH_ASSOC); // Obtiene todos los resultados como array asociativo
} catch (PDOException $e) {
    // Si la tabla no existe, ejecutar consulta sin JOIN
    if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Table") !== false) {
        error_log("Tabla alumno_representante no existe. Ejecutar: create_alumno_representante_table.sql");
        // Consulta alternativa sin JOIN
        $sql = "SELECT * FROM alumnos WHERE estatus != 0";
        $query = $pdo->prepare($sql);
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // En caso de otro error, devolver array vacío
        // error_log("Error en table_alumnos.php: " . $e->getMessage());
        $data = [];
    }
}
// Verificar si existe algún periodo escolar activo globalmente
$existePeriodoActivoGlobal = false;
try {
    $sqlPeriodoGlobal = "SELECT COUNT(*) as total FROM periodo_escolar WHERE estatus = 1";
    $queryPeriodoGlobal = $pdo->query($sqlPeriodoGlobal);
    $resPeriodoGlobal = $queryPeriodoGlobal->fetch(PDO::FETCH_ASSOC);
    $existePeriodoActivoGlobal = $resPeriodoGlobal['total'] > 0;
} catch (Exception $e) {
    // error_log("Error verificando periodo activo: " . $e->getMessage());
}

// Recorre cada registro de alumnos obtenido de la base de datos
for ($i = 0; $i < count($data); $i++) {
    // Convierte el valor numérico del estatus a etiquetas HTML con clases Bootstrap
    if ($data[$i]['estatus'] == 1) {
        $data[$i]['estatus'] = '<span class="badge badge-success">Activo</span>'; // Estilo verde para activo
    } else {
        $data[$i]['estatus'] = '<span class="badge badge-danger">Inactivo</span>'; // Estilo rojo para inactivo
    }

    // Formatear nombre completo del representante
    $repText = '';

    // Representante Principal
    $isStudentActive = ($data[$i]['estatus'] == 1);
    $repIdActivo = false; // Inicializar por defecto
    
    // Logic for Primary Representative
    if (!empty($data[$i]['rep_nombre']) && !empty($data[$i]['rep_apellido'])) {
        $repIdActivo = ($data[$i]['rep_estatus'] == 1 && $data[$i]['rel_estatus'] == 1);
        
        // Show if student is inactive (show history) OR if everything is active
        if (!$isStudentActive || $repIdActivo) {
            $parentesco = !empty($data[$i]['parentesco']) ? $data[$i]['parentesco'] : 'No Asignado';
            $nac = !empty($data[$i]['rep_nacionalidad']) ? $data[$i]['rep_nacionalidad'] : 'V';
            $cedula = !empty($data[$i]['rep_cedula']) ? $nac . '-' . $data[$i]['rep_cedula'] : 'S/C';

            $color = $repIdActivo ? '#007bff' : '#dc3545'; // blue if active, red if inactive
            $statusLabel = $repIdActivo ? '' : ' <span class="badge badge-danger" style="font-size:0.7em;">Inactivo</span>';

            $repText .= '<div style="margin-bottom:6px; border-left: 3px solid '. $color .'; padding-left: 5px;">';
            $repText .= '<strong style="color:'. $color .';">P:</strong> ' . $data[$i]['rep_nombre'] . ' ' . $data[$i]['rep_apellido'] . $statusLabel;
            $repText .= '<div style="font-size:0.9em; color:#555; margin-top:1px;">';
            $repText .= '<i class="fas fa-id-card" style="font-size:0.9em; margin-right:3px;"></i> ' . $cedula;
            $repText .= ' <span style="color:#aaa;">|</span> <small class="text-muted">(' . $parentesco . ')</small>';
            $repText .= '</div></div>';
        }
    }

    // Representante Secundario
    if (!empty($data[$i]['rep2_nombre']) && !empty($data[$i]['rep2_apellido'])) {
        $rep2IdActivo = ($data[$i]['rep2_estatus'] == 1 && $data[$i]['rel2_estatus'] == 1);

        // Show if student is inactive (show history) OR if everything is active
        if (!$isStudentActive || $rep2IdActivo) {
            $parentesco2 = !empty($data[$i]['parentesco2']) ? $data[$i]['parentesco2'] : 'No Asignado';
            $nac2 = !empty($data[$i]['rep2_nacionalidad']) ? $data[$i]['rep2_nacionalidad'] : 'V';
            $cedula2 = !empty($data[$i]['rep2_cedula']) ? $nac2 . '-' . $data[$i]['rep2_cedula'] : 'S/C';

            $color2 = $rep2IdActivo ? '#6c757d' : '#dc3545';
            $statusLabel2 = $rep2IdActivo ? '' : ' <span class="badge badge-danger" style="font-size:0.7em;">Inactivo</span>';

            $repText .= '<div style="margin-bottom:2px; border-left: 3px solid '. $color2 .'; padding-left: 5px;">';
            $repText .= '<strong style="color:'. $color2 .';">S:</strong> ' . $data[$i]['rep2_nombre'] . ' ' . $data[$i]['rep2_apellido'] . $statusLabel2;
            $repText .= '<div style="font-size:0.9em; color:#555; margin-top:1px;">';
            $repText .= '<i class="fas fa-id-card" style="font-size:0.9em; margin-right:3px;"></i> ' . $cedula2;
            $repText .= ' <span style="color:#aaa;">|</span> <small class="text-muted">(' . $parentesco2 . ')</small>';
            $repText .= '</div></div>';
        }
    }

    if ($repText == '') {
        $data[$i]['representante'] = 'No Asignado';
    } else {
        $data[$i]['representante'] = $repText;
    }

    // Formatear Dirección Completa
    $direccionCompleta = '';
    $partesDireccion = [];

    if (!empty($data[$i]['nombre_parroquia']))
        $partesDireccion[] = $data[$i]['nombre_parroquia'];
    if (!empty($data[$i]['nombre_municipio']))
        $partesDireccion[] = $data[$i]['nombre_municipio'];
    if (!empty($data[$i]['nombre_ciudad']))
        $partesDireccion[] = $data[$i]['nombre_ciudad'];
    if (!empty($data[$i]['nombre_estado']))
        $partesDireccion[] = $data[$i]['nombre_estado'];

    if (!empty($partesDireccion)) {
        $direccionCompleta .= implode(', ', $partesDireccion);
    }
    $data[$i]['direccion'] = $direccionCompleta;

    // Formatear fecha de nacimiento
    if (!empty($data[$i]['fecha_nac'])) {
        $data[$i]['fecha_nac'] = date('d/m/Y', strtotime($data[$i]['fecha_nac']));
    }

    // Crea la columna de opciones con botones para editar, eliminar e inscribir
    $id = $data[$i]['alumno_id'];

    // Botón PDF condicional
    $btnPdf = '';
    $inscritoEnPeriodoActivo = !empty($data[$i]['active_inscripcion_id']);
    $inscripcionId = $data[$i]['active_inscripcion_id'] ?? 0;

    if ($inscritoEnPeriodoActivo) {
        $btnPdf = '<button class="btn btn-warning btn-sm btnPdfInscripcion" rl="' . $inscripcionId . '" title="Pdf Inscripcion"><i class="fas fa-file-pdf"></i></button>';
    } else {
        $btnPdf = '<button class="btn btn-secondary btn-sm" disabled title="No inscrito en periodo activo"><i class="fas fa-file-pdf"></i></button>';
    }

    // Botón Inscribir condicional
    $btnInscribir = '';
    // Verificar si el alumno está activo (estatus == 1) Y tiene representante asignado

    $isActivo = strpos($data[$i]['estatus'], 'badge-success') !== false;
    $hasRepresentante = $data[$i]['representante'] !== 'No Asignado';

    if ($isActivo && $hasRepresentante && !$inscritoEnPeriodoActivo && $existePeriodoActivoGlobal) {
        $btnInscribir = '<button class="btn btn-primary btn-sm btnInscribirAlumno" rl="' . $id . '" rep_active="' . ($repIdActivo ? '1' : '0') . '" title="Inscribir"><i class="fas fa-user-graduate"></i></button>';
    } else {
        if (!$existePeriodoActivoGlobal) {
            $title = "No hay periodo escolar activo";
        } elseif ($inscritoEnPeriodoActivo) {
            $title = "Alumno ya inscrito en periodo activo";
        } else {
            $title = !$isActivo ? "Alumno Inactivo" : "Requiere Representante";
        }
        $btnInscribir = '<button class="btn btn-secondary btn-sm" disabled title="' . $title . '"><i class="fas fa-user-graduate"></i></button>';
    }


    // Define buttons based on status
    $btnActivate = '<button class="btn btn-success btn-sm btnActivateAlumno" rl="' . $id . '" title="Activar"><i class="fas fa-check"></i></button>';
    $btnInactivate = '<button class="btn btn-danger btn-sm btnDelAlumno" rl="' . $id . '" title="Inhabilitar"><i class="fa-solid fa-ban" aria-hidden="true"></i></button>';

    // Build options based on active status
    if ($isActivo) {
        $data[$i]['options'] = '<div class="text-center">'
            . '<button class="btn btn-dark btn-sm btnEditAlumno" rl="' . $id . '" title="Editar"><i class="fas fa-pencil-alt"></i></button>' // Active: Enabled
            . '<button class="btn btn-info btn-sm btnEditInfoAlumno" rl="' . $id . '" onclick="openModalInfoAlumno(' . $id . ')" title="Información Adicional"><i class="fa-solid fa-plus"></i></button>'
            . $btnInscribir
            . $btnPdf
            . $btnInactivate
            . '</div>';
    } else {
        $data[$i]['options'] = '<div class="text-center">'
            . '<button class="btn btn-dark btn-sm btnEditAlumno" rl="' . $id . '" title="Editar" disabled><i class="fas fa-pencil-alt"></i></button>' // Inactive: Disabled
            . '<button class="btn btn-secondary btn-sm" disabled title="Información Adicional (Inactivo)"><i class="fa-solid fa-plus"></i></button>'
            . $btnInscribir
            . $btnPdf
            . $btnActivate
            . '</div>';
    }
}

// Convierte el array de datos a formato JSON y lo envía como respuesta
echo json_encode($data, JSON_UNESCAPED_UNICODE);
die(); // Termina la ejecución del script para evitar salida adicional