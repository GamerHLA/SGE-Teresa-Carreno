<?php

require_once '../../includes/config.php';

if ($_POST) {
    $idPeriodo = $_POST['idPeriodo'];

    // Verificar si el período está asociado a algún curso activo
    $sql_curso = "SELECT * FROM curso WHERE periodo_id = $idPeriodo AND estatusC != 0";
    $query_curso = $pdo->prepare($sql_curso);
    $query_curso->execute();
    $result_curso = $query_curso->fetchAll(PDO::FETCH_ASSOC);

    if (empty($result_curso)) {
        // Si no tiene cursos asociados, eliminar (cambiar estatus a 0)
        $sql = "UPDATE periodo_escolar SET estatus = 2 WHERE periodo_id = ?";
        $query = $pdo->prepare($sql);
        $result = $query->execute(array($idPeriodo));

        if ($result && $query->rowCount() > 0) {
            $arrResponse = array('status' => true, 'msg' => 'Período escolar inhabilitado correctamente');
        } else {
            $arrResponse = array('status' => false, 'msg' => 'El período escolar ya se encuentra inhabilitado');
        }
    } else {
        // Si tiene cursos asociados, inactivar primero los cursos, inscripciones y luego el período
        $sqlInactivateCursos = "UPDATE curso SET estatusC = 2 WHERE periodo_id = ? AND estatusC = 1";
        $queryInactivateCursos = $pdo->prepare($sqlInactivateCursos);
        $queryInactivateCursos->execute(array($idPeriodo));

        $sqlInactivateInscripciones = "UPDATE inscripcion i 
                                       INNER JOIN curso c ON i.curso_id = c.curso_id 
                                       SET i.estatusI = 2 
                                       WHERE c.periodo_id = ? AND i.estatusI = 1";
        $queryInactivateInscripciones = $pdo->prepare($sqlInactivateInscripciones);
        $queryInactivateInscripciones->execute(array($idPeriodo));

        $sql = "UPDATE periodo_escolar SET estatus = 2 WHERE periodo_id = ?";
        $query = $pdo->prepare($sql);
        $result = $query->execute(array($idPeriodo));

        if ($result) {
            $arrResponse = array('status' => true, 'msg' => 'Se inhabilito correctamente');
        } else {
            $arrResponse = array('status' => false, 'msg' => 'Error al inhabilitar el período escolar');
        }
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}