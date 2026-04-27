<?php

require_once '../../includes/config.php';

if ($_POST) {
    $idProfesor = $_POST['idProfesor'];

    $sql = "UPDATE profesor SET estatus = 1 WHERE profesor_id = ?";
    $query = $pdo->prepare($sql);
    $result = $query->execute(array($idProfesor));

    if ($result) {
        $arrResponse = array('status' => true, 'msg' => 'Profesor activado correctamente');
    } else {
        $arrResponse = array('status' => false, 'msg' => 'Error al activar el profesor');
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}
