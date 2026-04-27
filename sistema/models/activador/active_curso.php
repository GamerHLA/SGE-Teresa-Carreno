<?php

require_once '../../includes/config.php';

if ($_POST) {
    $idCurso = $_POST['idCurso'];

    // Validar si el curso tiene profesor asignado
    // Validar si el curso tiene profesor asignado Y ACTIVO
    $sqlCheck = "SELECT c.profesor_id, p.estatus 
                 FROM curso c 
                 LEFT JOIN profesor p ON c.profesor_id = p.profesor_id 
                 WHERE c.curso_id = ?";
    $queryCheck = $pdo->prepare($sqlCheck);
    $queryCheck->execute(array($idCurso));
    $dataCheck = $queryCheck->fetch(PDO::FETCH_ASSOC);

    if (empty($dataCheck['profesor_id']) || $dataCheck['profesor_id'] == 0 || $dataCheck['estatus'] != 1) {
        $arrResponse = array('status' => false, 'msg' => 'No se puede activar el grado/sección porque no tiene profesor asignado.');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "UPDATE curso SET estatusC = 1 WHERE curso_id = ?";
    $query = $pdo->prepare($sql);
    $result = $query->execute(array($idCurso));

    if ($result) {
        $arrResponse = array('status' => true, 'msg' => 'Grado/Sección activado correctamente');
    } else {
        $arrResponse = array('status' => false, 'msg' => 'Error al activar el grado/sección');
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}
