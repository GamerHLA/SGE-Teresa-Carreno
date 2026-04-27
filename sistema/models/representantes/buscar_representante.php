<?php
// Archivo para buscar representante por cédula y devolver datos en JSON
require_once '../../includes/config.php';

session_start();
if (empty($_SESSION['active'])) {
    http_response_code(401);
    echo json_encode(['status' => false, 'msg' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

if (isset($_GET['cedula']) && !empty($_GET['cedula'])) {
    $cedula = trim($_GET['cedula']);
    
    try {
        $sql = "SELECT r.nombre, r.apellido, n.codigo as nacionalidad 
                FROM representantes r 
                INNER JOIN nacionalidades n ON r.id_nacionalidades = n.id 
                WHERE r.cedula = ? AND r.estatus = 1";
        $query = $pdo->prepare($sql);
        $query->execute([$cedula]);
        $data = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            echo json_encode([
                'status' => true, 
                'data' => [
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'nombre_completo' => $data['nombre'] . ' ' . $data['apellido'],
                    'nacionalidad' => $data['nacionalidad']
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Representante no encontrado']);
        }
    } catch (PDOException $e) {
        error_log("Error en buscar_representante.php: " . $e->getMessage());
        echo json_encode(['status' => false, 'msg' => 'Error en la consulta']);
    }
} else {
    echo json_encode(['status' => false, 'msg' => 'Cédula no proporcionada']);
}

