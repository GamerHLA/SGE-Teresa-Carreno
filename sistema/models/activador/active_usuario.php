<?php

require_once '../../includes/config.php';

if ($_POST) {
    $idUsuario = $_POST['idUsuario'];

    $sql = "UPDATE usuarios SET estatus = 1 WHERE user_id = ?";
    $query = $pdo->prepare($sql);
    $result = $query->execute(array($idUsuario));

    if ($result) {
        $arrResponse = array('status' => true, 'msg' => 'Usuario activado correctamente');
    } else {
        $arrResponse = array('status' => false, 'msg' => 'Error al activar el usuario');
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}
