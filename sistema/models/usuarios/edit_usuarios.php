<?php

require_once '../../includes/config.php';

    $idUser = $_GET['id'];
    $sql = "SELECT 
                u.id_usuario as user_id, 
                p.nombres as nombre, 
                u.usuario, 
                u.id_rol as rol, 
                u.estatus, 
                p.profesores_id as profesor_id 
            FROM usuarios as u 
            INNER JOIN personas as p ON u.id_persona = p.id_persona 
            WHERE u.id_usuario = ?";
    $query = $pdo->prepare($sql);
    $query->execute(array($idUser));
    $data = $query->fetch(PDO::FETCH_ASSOC);

    if(empty($data)) {
        $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados');
    } else {
        $arrResponse = array('status' => true, 'data' => $data);
    }
    echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
