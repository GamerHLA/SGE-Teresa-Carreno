<?php

require_once '../../includes/config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['status' => false, 'msg' => 'ID no proporcionado']);
    exit;
}

$idProfesor = intval($_GET['id']);

try {
    $sql = "SELECT 
                pr.*,
                n.codigo as nacionalidad_codigo
            FROM profesor pr
            LEFT JOIN nacionalidades n ON pr.id_nacionalidades = n.id
            WHERE pr.profesor_id = ?";
    
    $query = $pdo->prepare($sql);
    $query->execute([$idProfesor]);

    $data = $query->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error en edit_profesores.php: " . $e->getMessage());
    $data = null;
}

if(empty($data)) {
    $arrResponse = array('status' => false,'msg' => 'Datos no encontrados');
} else {
    // Verificar si es representante
    if (!empty($data['cedula'])) {
        $sqlRep = "SELECT representantes_id FROM representantes WHERE cedula = ?";
        $queryRep = $pdo->prepare($sqlRep);
        $queryRep->execute([$data['cedula']]);
        $rep = $queryRep->fetch(PDO::FETCH_ASSOC);
        $data['es_representante'] = $rep ? 1 : 0;
    } else {
        $data['es_representante'] = 0;
    }

    $arrResponse = array('status' => true,'data' => $data);
}
echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);