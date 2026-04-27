<?php

require_once '../../includes/config.php';

if ($_POST) {
    $idRepresentante = $_POST['idRepresentante'];

    $sql = "UPDATE representantes SET estatus = 1 WHERE representantes_id = ?";
    $query = $pdo->prepare($sql);
    $result = $query->execute(array($idRepresentante));

    if ($result) {
        $arrResponse = array('status' => true, 'msg' => 'Representante activado correctamente');
    } else {
        $arrResponse = array('status' => false, 'msg' => 'Error al activar el representante');
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}
