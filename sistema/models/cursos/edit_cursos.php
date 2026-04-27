<?php

require_once '../../includes/config.php';

if (!empty($_GET)) {
    $idCurso = $_GET['id'];
    $sql = "SELECT c.*, g.grado, s.seccion 
            FROM curso as c 
            INNER JOIN grados g ON c.grados_id = g.id_grado
            INNER JOIN seccion s ON c.seccion_id = s.id_seccion
            WHERE c.curso_id = ?";
    $query = $pdo->prepare($sql);
    $query->execute(array($idCurso));
    $data = $query->fetch(PDO::FETCH_ASSOC);
    if (empty($data)) {
        $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados');
    } else {
        $arrResponse = array('status' => true, 'data' => $data);
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}