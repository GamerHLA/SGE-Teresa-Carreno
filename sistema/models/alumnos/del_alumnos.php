<?php

// Iniciar buffer de salida para evitar cualquier output antes del JSON
ob_start();

require_once '../../includes/config.php'; // Incluye el archivo de configuración de la base de datos

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
                $arrResponse = array('status' => false, 'msg' => 'El motivo es obligatorio');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                ob_end_flush();
                exit();
            }

            // Obtener historial de inscripciones del alumno (todos los tiempos)
            $sqlHistory = "SELECT 
                                g.grado, 
                                s.seccion, 
                                pe.anio_inicio, 
                                pe.anio_fin 
                           FROM inscripcion i
                           INNER JOIN curso c ON i.curso_id = c.curso_id
                           INNER JOIN grados g ON c.grados_id = g.id_grado
                           INNER JOIN seccion s ON c.seccion_id = s.id_seccion
                           INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
                           WHERE i.alumno_id = ?
                           ORDER BY pe.anio_inicio ASC";
            $queryHistory = $pdo->prepare($sqlHistory);
            $queryHistory->execute([$idAlumno]);
            $historialInscripciones = $queryHistory->fetchAll(PDO::FETCH_ASSOC);

            $historyString = "Inhabilitación de Alumno. ";
            if (!empty($historialInscripciones)) {
                $historyString .= "Historial de Grados/Períodos: ";
                $cursosStr = [];
                foreach ($historialInscripciones as $inscripcion) {
                    // Formato: 1° - A (2023-2024)
                    $cursosStr[] = "{$inscripcion['grado']}° - {$inscripcion['seccion']} ({$inscripcion['anio_inicio']}-{$inscripcion['anio_fin']})";
                }
                $historyString .= implode(', ', $cursosStr);
            } else {
                $historyString .= "No tenía inscripciones registradas.";
            }

            // Añadir el motivo del usuario
            $observacionFinal = $historyString . ". Motivo: " . $motivo;

            // Insertar el motivo en la tabla de observaciones
            $sqlObs = "INSERT INTO observaciones (alumno_id, tipo_observacion, observacion, estatus) VALUES (?, 'inhabilitacion', ?, 1)";
            $queryObs = $pdo->prepare($sqlObs);
            $queryObs->execute([$idAlumno, $observacionFinal]);

            // Consulta para desactivar (eliminar lógicamente) el alumno
            $sql_update = "UPDATE alumnos SET estatus = 2 WHERE alumno_id = ?";
            $query_update = $pdo->prepare($sql_update);
            $request = $query_update->execute(array($idAlumno));

            if ($request && $query_update->rowCount() > 0) {
                // TAMBIÉN desactivar las inscripciones activas (estatusI = 2 para Inactivo)
                $sql_update_inscripcion = "UPDATE inscripcion SET estatusI = 2 WHERE alumno_id = ? AND estatusI = 1";
                $query_update_inscripcion = $pdo->prepare($sql_update_inscripcion);
                $query_update_inscripcion->execute(array($idAlumno));

                $arrResponse = array('status' => true, 'msg' => 'Inhabilitado correctamente');
            } else {
                $arrResponse = array('status' => false, 'msg' => 'El alumno ya se encuentra inhabilitado');
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