<?php
session_start();
if(empty($_SESSION['active'])) {
    header("Location: ../");
    exit();
}

require_once '../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Consulta para obtener las inscripciones
    $query = "SELECT i.inscripcion_id, 
                     a.alumno_id,
                     a.nombre as alumno_nombre, 
                     a.apellido as alumno_apellido,
                     a.cedula,
                     c.curso_id,
                     c.grado, 
                     c.seccion,
                     t.tipo_turno,
                     i.estatusI
              FROM inscripcion i
              INNER JOIN alumnos a ON i.alumno_id = a.alumno_id
              INNER JOIN curso c ON i.curso_id = c.curso_id
              INNER JOIN turno t ON i.turno_id = t.turno_id
              WHERE i.estatusI = 1
              ORDER BY i.inscripcion_id DESC";

    $stmt = $pdo->query($query);
    $inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Preparar datos para DataTables
    $data = [];
    foreach ($inscripciones as $inscripcion) {
        $data[] = [
            'inscripcion_id' => $inscripcion['inscripcion_id'],
            'alumno_nombre' => $inscripcion['alumno_nombre'] . ' ' . $inscripcion['alumno_apellido'],
            'cedula' => $inscripcion['cedula'],
            'curso' => $inscripcion['grado'] . '° "' . $inscripcion['seccion'] . '"',
            'tipo_turno' => $inscripcion['tipo_turno'],
            'estatusI' => $inscripcion['estatusI'],
            'acciones' => $inscripcion['inscripcion_id']
        ];
    }

    // Respuesta en formato DataTables
    $response = [
        "data" => $data
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    $response = [
        "error" => "Error en la consulta: " . $e->getMessage(),
        "data" => []
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>