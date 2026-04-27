<?php

require_once '../../includes/config.php';

if ($_POST) {
    $idCurso = $_POST['idCurso'];

    $sql = "SELECT * FROM inscripcion WHERE curso_id = $idCurso AND estatusI != 0";
    $query = $pdo->prepare($sql);
    $query->execute();
    $result = $query->fetchAll(PDO::FETCH_ASSOC);

    if (empty($result)) {
        $sql_update = "UPDATE curso SET estatusC = 2 WHERE curso_id = ?";
        $query_update = $pdo->prepare($sql_update);
        $result = $query_update->execute(array($idCurso));
        if ($result && $query_update->rowCount() > 0) {
            $arrResponse = array('status' => true, 'msg' => 'Inhabilitado correctamente');
        } else {
            $arrResponse = array('status' => false, 'msg' => 'El Grado/Sección ya se encuentra inhabilitado');
        }
    } else {
        $arrResponse = array('status' => false, 'msg' => 'No se puede inhabilitar un Grado/Sección asociado a una inscripción');
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}
