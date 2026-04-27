<?php

require_once '../../includes/config.php';

if(!empty($_GET)) {
    $idPeriodo = $_GET['id'];
    $sql = "SELECT * FROM periodo_escolar WHERE periodo_id = ?";
    $query = $pdo->prepare($sql);
    $query->execute(array($idPeriodo));
    $data = $query->fetch(PDO::FETCH_ASSOC);

    if(empty($data)) {
        $arrResponse = array('status' => false,'msg' => 'Datos del período escolar no encontrados');
    } else {
        $arrResponse = array('status' => true,'data' => $data);
    }
    echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
}