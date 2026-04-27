<?php
require_once '../../includes/config.php';

if (isset($_GET['alumno_id']) && !empty($_GET['alumno_id'])) {
    $alumno_id = intval($_GET['alumno_id']);

    try {
        // Obtener el representante principal del alumno
        $sql = "SELECT 
                    r.representantes_id,
                    r.nombre,
                    r.apellido,
                    p.parentesco
                FROM alumno_representante ar
                INNER JOIN representantes r ON ar.representante_id = r.representantes_id
                LEFT JOIN parentesco p ON ar.parentesco_id = p.id_parentesco
                WHERE ar.alumno_id = ? 
                AND ar.estatus = 1 
                AND r.estatus = 1
                ORDER BY ar.es_principal DESC, ar.relacion_id ASC
                LIMIT 1";

        $query = $pdo->prepare($sql);
        $query->execute([$alumno_id]);
        $data = $query->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            echo json_encode([
                'status' => true,
                'data' => [
                    'nombre_completo' => $data['nombre'] . ' ' . $data['apellido'],
                    'parentesco' => $data['parentesco']
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => false,
                'data' => [
                    'nombre_completo' => 'No asignado',
                    'parentesco' => 'No asignado'
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    } catch (PDOException $e) {
        error_log("Error en options-representante-alumno.php: " . $e->getMessage());
        echo json_encode([
            'status' => false,
            'msg' => 'Error al obtener el representante'
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'status' => false,
        'msg' => 'ID de alumno no proporcionado'
    ], JSON_UNESCAPED_UNICODE);
}
?>