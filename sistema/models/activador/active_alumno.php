<?php

require_once '../../includes/config.php';

if ($_POST) {
    $idAlumno = $_POST['idAlumno'];

    $sql = "UPDATE alumnos SET estatus = 1 WHERE alumno_id = ?";
    $query = $pdo->prepare($sql);
    $result = $query->execute(array($idAlumno));

    if ($result) {
        $arrResponse = array('status' => true, 'msg' => 'Alumno activado correctamente');
    } else {
        $arrResponse = array('status' => false, 'msg' => 'Error al activar el alumno');
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}
