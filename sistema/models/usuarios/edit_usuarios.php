<?php

require_once '../../includes/config.php';

    $idUser = $_GET['id'];
    $sql = "SELECT * FROM usuarios WHERE user_id = ?";
    $query = $pdo->prepare($sql);
    $query->execute(array($idUser));
    $data = $query->fetch(PDO::FETCH_ASSOC);

    if(empty($data)) {
        $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados');
    } else {
        $arrResponse = array('status' => true, 'data' => $data);
    }
    echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
