<?php

require_once '../../includes/config.php';

if ($_POST) {
    $idInscripcion = $_POST['idInscripcion'];

    $sql_update = "UPDATE inscripcion SET estatusI = 2 WHERE inscripcion_id = ?";
    $query_update = $pdo->prepare($sql_update);
    $result = $query_update->execute(array($idInscripcion));
    if ($result && $query_update->rowCount() > 0) {
        $arrResponse = array('status' => true, 'msg' => 'Inhabilitado correctamente');
    } else {
        $arrResponse = array('status' => false, 'msg' => 'La inscripción ya se encuentra inhabilitada');
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}