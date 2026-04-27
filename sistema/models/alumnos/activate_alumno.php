<?php

// Iniciar buffer de salida para evitar cualquier output antes del JSON
ob_start();

require_once '../../includes/config.php';

// Iniciar sesión solo si no está ya iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['idAlumno']) && !empty($_POST['idAlumno'])) {
            $idAlumno = intval($_POST['idAlumno']);
            $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';

            if (empty($motivo)) {
                ob_clean(); // Limpiar cualquier output previo
                $arrResponse = array('status' => false, 'msg' => 'El motivo de reactivación es obligatorio');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                ob_end_flush();
                exit();
            }

            // Insertar en observaciones
            $observacionFinal = "Reactivación de Alumno. Motivo: " . $motivo;

            $sqlObs = "INSERT INTO observaciones (alumno_id, tipo_observacion, observacion, estatus) VALUES (?, 'reactivacion', ?, 1)";
            $queryObs = $pdo->prepare($sqlObs);
            $queryObs->execute([$idAlumno, $observacionFinal]);

            // Activar el alumno
            $sql = "UPDATE alumnos SET estatus = 1 WHERE alumno_id = ?";
            $query = $pdo->prepare($sql);
            $result = $query->execute([$idAlumno]);

            if ($result && $query->rowCount() > 0) {
                // TAMBIÉN reactivar la última inscripción del alumno (estatusI = 1 para Activo)
                // Solo si antes estaba inactiva (2)
                $sql_update_inscripcion = "UPDATE inscripcion SET estatusI = 1 WHERE alumno_id = ? AND estatusI = 2 ORDER BY inscripcion_id DESC LIMIT 1";
                $query_update_inscripcion = $pdo->prepare($sql_update_inscripcion);
                $query_update_inscripcion->execute(array($idAlumno));

                $arrResponse = array('status' => true, 'msg' => 'Alumno activado correctamente');
            } else {
                $arrResponse = array('status' => false, 'msg' => 'No se pudo activar el alumno o ya está activo');
            }
        } else {
            $arrResponse = array('status' => false, 'msg' => 'Error de datos');
        }
    } else {
        $arrResponse = array('status' => false, 'msg' => 'Método no permitido');
    }
    
    ob_clean(); // Limpiar cualquier output previo
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    
} catch (Exception $e) {
    ob_clean();
    $arrResponse = array('status' => false, 'msg' => 'Error en el servidor: ' . $e->getMessage());
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}
