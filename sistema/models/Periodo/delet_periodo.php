<?php
require_once '../../includes/config.php';

if ($_POST) {
    $idPeriodo = $_POST['idPeriodo'];

    // Verificar si el período tiene inscripciones activas
    $sql_inscripciones = "SELECT * FROM inscripcion WHERE periodo_id = ? AND status = 1";
    $query_inscripciones = $pdo->prepare($sql_inscripciones);
    $query_inscripciones->execute(array($idPeriodo));
    $result_inscripciones = $query_inscripciones->fetchAll(PDO::FETCH_ASSOC);

    if (empty($result_inscripciones)) {
        // Si no tiene inscripciones activas, inhabilitar período
        $sql = "UPDATE periodo_escolar SET estatus = 2 WHERE periodo_id = ?";
        $query = $pdo->prepare($sql);
        $result = $query->execute(array($idPeriodo));

        if ($result && $query->rowCount() > 0) {
            $arrResponse = array('status' => true, 'msg' => 'Período escolar inhabilitado correctamente');
        } else {
            $arrResponse = array('status' => false, 'msg' => 'El período escolar ya se encuentra inhabilitado');
        }
    } else {
        // Si tiene inscripciones activas, primero inhabilitar inscripciones
        $sqlInactivateInscripciones = "UPDATE inscripcion SET status = 2 WHERE periodo_id = ? AND status = 1";
        $queryInactivateInscripciones = $pdo->prepare($sqlInactivateInscripciones);
        $queryInactivateInscripciones->execute(array($idPeriodo));

        // Luego inhabilitar el período
        $sql = "UPDATE periodo_escolar SET estatus = 2 WHERE periodo_id = ?";
        $query = $pdo->prepare($sql);
        $result = $query->execute(array($idPeriodo));

        if ($result) {
            $arrResponse = array('status' => true, 'msg' => 'Período escolar e inscripciones inhabilitados correctamente');
        } else {
            $arrResponse = array('status' => false, 'msg' => 'Error al inhabilitar el período escolar');
        }
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}
?>