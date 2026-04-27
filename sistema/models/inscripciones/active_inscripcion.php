<?php

require_once '../../includes/config.php';

if ($_POST) {
    $idInscripcion = $_POST['idInscripcion'];

    $sql = "UPDATE inscripcion SET estatusI = 1 WHERE inscripcion_id = ?";
    $query = $pdo->prepare($sql);
    $result = $query->execute(array($idInscripcion));

    if ($result) {
        $arrResponse = array('status' => true, 'msg' => 'Inscripción activada correctamente');
    } else {
        $arrResponse = array('status' => false, 'msg' => 'Error al activar la inscripción');
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}
