<?php

require_once '../../includes/config.php';

try {
    // Consulta SQL para obtener todas las observaciones con información del alumno O representante O profesor
    $sql = "SELECT 
                o.observacion_id,
                o.alumno_id,
                o.representantes_id,
                o.profesor_id,
                o.tipo_observacion,
                o.observacion,
                o.fecha_creacion,
                o.estatus,
                a.cedula as alumno_cedula,
                a.nombre as alumno_nombre,
                a.apellido as alumno_apellido,
                a.id_nacionalidades as alumno_nacionalidad,
                r.cedula as representante_cedula,
                r.nombre as representante_nombre,
                r.apellido as representante_apellido,
                r.id_nacionalidades as representante_nacionalidad,
                p.cedula as profesor_cedula,
                p.nombre as profesor_nombre,
                p.apellido as profesor_apellido
            FROM observaciones o
            LEFT JOIN alumnos a ON o.alumno_id = a.alumno_id
            LEFT JOIN representantes r ON o.representantes_id = r.representantes_id
            LEFT JOIN profesor p ON o.profesor_id = p.profesor_id
            WHERE o.estatus = 1 
            AND o.tipo_observacion != 'motivo_justificativo' 
            AND o.tipo_observacion != 'motivo_retiro'
            ORDER BY o.fecha_creacion DESC";

    $query = $pdo->prepare($sql);
    $query->execute();
    $data = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En caso de error, devolver array vacío
    error_log("Error en table_observaciones.php: " . $e->getMessage());
    $data = [];
}

for ($i = 0; $i < count($data); $i++) {
    // Add row number (1 for the lastest because of DESC order)
    $data[$i]['numero_registro'] = $i + 1;
    // Raw timestamp for accurate sorting in DataTable
    $data[$i]['fecha_raw'] = strtotime($data[$i]['fecha_creacion']);
    
    // Format Nombre y Apellido - Use alumno if exists, otherwise representante
    if (!empty($data[$i]['alumno_id']) && $data[$i]['alumno_id'] > 0) {
        // Es un alumno
        $data[$i]['nombre_completo'] = ($data[$i]['alumno_nombre'] ?? '') . ' ' . ($data[$i]['alumno_apellido'] ?? '') . ' (Alumno)';
    } elseif (!empty($data[$i]['representantes_id']) && $data[$i]['representantes_id'] > 0) {
        // Es un representante
        $data[$i]['nombre_completo'] = ($data[$i]['representante_nombre'] ?? '') . ' ' . ($data[$i]['representante_apellido'] ?? '') . ' (Repre.)';
    } elseif (!empty($data[$i]['profesor_id']) && $data[$i]['profesor_id'] > 0) {
        // Es un profesor
        $data[$i]['nombre_completo'] = ($data[$i]['profesor_nombre'] ?? '') . ' ' . ($data[$i]['profesor_apellido'] ?? '') . ' (Prof.)';
    } else {
        $data[$i]['nombre_completo'] = 'Desconocido';
    }
    
    // Format Tipo with badge - Map all observation types
    $tipo = $data[$i]['tipo_observacion'];
    $tipoLabel = '';
    $badgeClass = 'badge-info'; // Default
    
    // Map tipo_observacion to readable labels
    switch ($tipo) {
        case 'retiro':
            $tipoLabel = 'Retiro de Institución';
            $badgeClass = 'badge-danger';
            break;
        case 'reactivacion':
            $tipoLabel = 'Reactivación';
            $badgeClass = 'badge-success';
            break;
        case 'inhabilitacion':
            $tipoLabel = 'Inhabilitación';
            $badgeClass = 'badge-warning';
            break;
        case 'motivo_retiro':
            $tipoLabel = 'Motivo de Retiro';
            $badgeClass = 'badge-danger';
            break;
        case 'motivo_justificativo':
            $tipoLabel = 'Justificativo Representante';
            $badgeClass = 'badge-info';
            break;
        case 'general':
            $tipoLabel = 'Observación General';
            $badgeClass = 'badge-secondary';
            break;
        default:
            // For any other tipo, use the raw value capitalized
            $tipoLabel = ucfirst(str_replace('_', ' ', $tipo));
            $badgeClass = 'badge-info';
            break;
    }
    
    $data[$i]['tipo_badge'] = '<span class="badge ' . $badgeClass . '">' . $tipoLabel . '</span>';
    
    // Format Fecha y Hora completa
    $fecha = $data[$i]['fecha_creacion'];
    $data[$i]['fecha_hora_completa'] = date('d/m/Y h:i A', strtotime($fecha));
    
    // Format Observación - convert newlines to <br> tags
    $observacion = htmlspecialchars($data[$i]['observacion'], ENT_QUOTES, 'UTF-8');
    $observacion = nl2br($observacion); // Convert \n to <br>
    $data[$i]['observacion_html'] = $observacion;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
die();
