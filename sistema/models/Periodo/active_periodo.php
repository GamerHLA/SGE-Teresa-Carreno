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
            // Activar también los cursos asociados a este periodo (que no estén eliminados estatusC != 0)
            $sqlCursos = "UPDATE curso SET estatusC = 1 WHERE periodo_id = ? AND estatusC != 0";
            $queryCursos = $pdo->prepare($sqlCursos);
            $queryCursos->execute(array($idPeriodo));

            // Activar también las inscripciones asociadas a los alumnos activos
            $sqlInscripciones = "UPDATE inscripcion i 
                                 INNER JOIN curso c ON i.curso_id = c.curso_id 
                                 INNER JOIN alumnos a ON i.alumno_id = a.alumno_id
                                 SET i.estatusI = 1 
                                 WHERE c.periodo_id = ? AND i.estatusI = 2 AND a.estatus = 1";
            $queryInscripciones = $pdo->prepare($sqlInscripciones);
            $queryInscripciones->execute(array($idPeriodo));

            $arrResponse = array('status' => true, 'msg' => 'Período escolar, sus cursos e inscripciones reactivados correctamente');
        } else {
            $arrResponse = array('status' => false, 'msg' => 'Error al activar el período escolar');
        }
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}
