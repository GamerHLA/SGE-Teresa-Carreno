<?php
require_once '../../includes/config.php';

if($_GET) {
    $idRepresentantes = $_GET['id'];
    
    try {
        $sql = "SELECT r.*, p.cedula, p.nombres AS nombre, p.apellidos AS apellido, p.telefono, p.correo_electronico AS correo, p.sexo, p.id_estado, p.id_ciudad, p.id_municipio, p.id_parroquia, p.id_nacionalidades, n.codigo AS nacionalidad_codigo 
                FROM representantes r 
                LEFT JOIN personas p ON p.representantes_id = r.id_representates
                LEFT JOIN nacionalidades n ON p.id_nacionalidades = n.id 
                WHERE r.id_representates = ?";
        
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
        $sqlProf = "SELECT pr.profesor_id FROM profesores pr 
                    INNER JOIN personas pp ON pp.profesores_id = pr.profesor_id 
                    WHERE pp.cedula = ?";
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
