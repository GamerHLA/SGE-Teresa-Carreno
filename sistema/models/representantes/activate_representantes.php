<?php
require_once '../../includes/config.php';

if ($_POST) {
    if (empty($_POST['idRepresentantes'])) {
        echo json_encode(array('status' => false, 'msg' => 'Error de datos'));
        exit;
    }

    $idRepresentantes = intval($_POST['idRepresentantes']);
    $observacion = $_POST['observacion'] ?? 'Sin motivo especificado';

    try {
        $pdo->beginTransaction();

        $sql_update = "UPDATE representantes SET estatus = 1 WHERE representantes_id = ?";
        $query_update = $pdo->prepare($sql_update);
        $request = $query_update->execute(array($idRepresentantes));

        if ($request) {
            // Activar también las relaciones en la tabla intermedia alumno_representante
            $sql_activate_rel = "UPDATE alumno_representante SET estatus = 1 WHERE representante_id = ?";
            $query_activate_rel = $pdo->prepare($sql_activate_rel);
            $query_activate_rel->execute([$idRepresentantes]);

            // Guardar observación
            $sqlObs = "INSERT INTO observaciones (representantes_id, tipo_observacion, observacion, estatus) VALUES (?, 'reactivacion', ?, 1)";
            $queryObs = $pdo->prepare($sqlObs);
            $queryObs->execute([$idRepresentantes, $observacion]);

            $pdo->commit();
            $arrResponse = array('status' => true, 'msg' => 'Representante activado correctamente.');
        } else {
            $pdo->rollBack();
            $arrResponse = array('status' => false, 'msg' => 'Error al activar el representante.');
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(array('status' => false, 'msg' => 'Error del sistema: ' . $e->getMessage()));
    }
}
?>
