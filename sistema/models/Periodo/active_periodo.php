<?php
require_once '../../includes/config.php';

if ($_POST) {
    $idPeriodo = $_POST['idPeriodo'];

    // Verificar si ya existe un período activo
    $sql_active = "SELECT * FROM periodo_escolar WHERE estatus = 1";
    $query_active = $pdo->prepare($sql_active);
    $query_active->execute();
    $result_active = $query_active->fetchAll(PDO::FETCH_ASSOC);

    if (count($result_active) > 0) {
        $arrResponse = array('status' => false, 'msg' => 'Ya existe un período escolar activo. Debe inactivarlo antes de activar otro.');
    } else {
        $sql = "UPDATE periodo_escolar SET estatus = 1 WHERE periodo_id = ?";
        $query = $pdo->prepare($sql);
        $result = $query->execute(array($idPeriodo));

        if ($result) {
            // Reactivar inscripciones asociadas a alumnos activos
            $sqlInscripciones = "UPDATE inscripcion i 
                                 INNER JOIN alumnos a ON i.alumno_id = a.id_alumnos
                                 SET i.status = 1 
                                 WHERE i.periodo_id = ? AND i.status = 2 AND a.estatus = 1";
            $queryInscripciones = $pdo->prepare($sqlInscripciones);
            $queryInscripciones->execute(array($idPeriodo));

            $arrResponse = array('status' => true, 'msg' => 'Período escolar e inscripciones reactivados correctamente');
        } else {
            $arrResponse = array('status' => false, 'msg' => 'Error al activar el período escolar');
        }
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}