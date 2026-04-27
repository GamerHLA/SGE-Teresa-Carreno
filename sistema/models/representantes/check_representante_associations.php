<?php
// Endpoint para verificar asociaciones de representante con alumnos
// Retorna información sobre estudiantes asociados y si se puede inactivar el representante

require_once '../../includes/config.php';

// Limpiar cualquier salida previa para asegurar un JSON válido
ob_start();
header('Content-Type: application/json');

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
    exit;
}

if (!empty($_POST)) {
    $idRepresentante = isset($_POST['idRepresentante']) ? intval($_POST['idRepresentante']) : 0;
    
    if ($idRepresentante <= 0) {
        echo json_encode(['status' => false, 'msg' => 'ID de representante inválido']);
        exit;
    }
    
    try {
        // Obtener estudiantes asociados a este representante
        // Fetch student name, principal status, and if they have a substitute
        // Also fetch the name of the substitute if they have one
        $sqlAssociations = "SELECT 
                                a.alumno_id,
                                a.nombre,
                                a.apellido,
                                a.estatus,
                                ar.es_principal,
                                (SELECT COUNT(*) 
                                 FROM alumno_representante ar2 
                                 INNER JOIN representantes r2 ON ar2.representante_id = r2.representantes_id
                                 WHERE ar2.alumno_id = a.alumno_id 
                                 AND ar2.estatus = 1
                                 AND r2.estatus = 1
                                 AND ar2.representante_id != ?) as total_representantes_restantes,
                                (SELECT CONCAT(r_sub.nombre, ' ', r_sub.apellido)
                                 FROM alumno_representante ar3
                                 INNER JOIN representantes r_sub ON ar3.representante_id = r_sub.representantes_id
                                 WHERE ar3.alumno_id = a.alumno_id
                                 AND ar3.estatus = 1
                                 AND r_sub.estatus = 1
                                 AND ar3.es_principal = 0
                                 AND ar3.representante_id != ?
                                 LIMIT 1) as nombre_sucesor
                            FROM alumno_representante ar
                            INNER JOIN alumnos a ON ar.alumno_id = a.alumno_id
                            WHERE ar.representante_id = ?";
        
        $queryAssociations = $pdo->prepare($sqlAssociations);
        $queryAssociations->execute([$idRepresentante, $idRepresentante, $idRepresentante]);
        $students = $queryAssociations->fetchAll(PDO::FETCH_ASSOC);
        
        $hasAssociations = count($students) > 0;
        $canInactivate = true; 
        $hasSubstitutionNeeded = false;
        
        // Analizar asociaciones
        if ($hasAssociations) {
            foreach ($students as $student) {
                // Si es principal, no hay nadie más activo Y el alumno está activo
                if ($student['es_principal'] == 1 && $student['total_representantes_restantes'] == 0 && $student['estatus'] == 1) {
                    $canInactivate = false;
                }
                
                // Si es principal y hay un sucesor
                if ($student['es_principal'] == 1 && !empty($student['nombre_sucesor'])) {
                    $hasSubstitutionNeeded = true;
                }
            }
        }
        
        ob_end_clean();
        echo json_encode([
            'status' => true,
            'has_associations' => $hasAssociations,
            'students' => $students,
            'can_inactivate' => $canInactivate,
            'has_substitution_needed' => $hasSubstitutionNeeded,
            'student_count' => count($students)
        ]);
        
    } catch (Throwable $e) {
        ob_end_clean();
        error_log("Error verificando asociaciones de representante: " . $e->getMessage());
        echo json_encode(['status' => false, 'msg' => 'Error del sistema: ' . $e->getMessage()]);
    }
} else {
    ob_end_clean();
    echo json_encode(['status' => false, 'msg' => 'No se recibieron datos']);
}
