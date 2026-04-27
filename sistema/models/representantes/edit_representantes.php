<?php
require_once '../../includes/config.php';

if($_GET) {
    $idRepresentantes = $_GET['id'];
    
    try {
        $sql = "SELECT r.*, n.codigo as nacionalidad_codigo 
                FROM representantes r 
                LEFT JOIN nacionalidades n ON r.id_nacionalidades = n.id 
                WHERE r.representantes_id = ?";
        
        $query = $pdo->prepare($sql);
        $query->execute(array($idRepresentantes));
        $data = $query->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en edit_representantes.php: " . $e->getMessage());
        $data = null;
    }

    if(empty($data)) {
        $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados');
    } else {
        // Verificar si es profesor
    if (!empty($data['cedula'])) {
        $sqlProf = "SELECT profesor_id FROM profesor WHERE cedula = ?";
        $queryProf = $pdo->prepare($sqlProf);
        $queryProf->execute([$data['cedula']]);
        $prof = $queryProf->fetch(PDO::FETCH_ASSOC);
        $data['es_profesor'] = $prof ? 1 : 0;
    } else {
        $data['es_profesor'] = 0;
    }

    $arrResponse = array('status' => true, 'data' => $data);
    }
    echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
}
