<?php
require_once '../../includes/config.php';

if ($_POST) {
    if (!empty($_POST['periodo_id']) && isset($_POST['estatus'])) {
        $periodo_id = intval($_POST['periodo_id']);
        $estatus = intval($_POST['estatus']);

        try {
            $sql = "UPDATE periodo_escolar SET estatus = ? WHERE periodo_id = ?";
            $query = $pdo->prepare($sql);
            $result = $query->execute(array($estatus, $periodo_id));

            if ($result) {
                $arrResponse = array('status' => true, 'msg' => 'Estatus actualizado correctamente');
            } else {
                $arrResponse = array('status' => false, 'msg' => 'Error al actualizar el estatus');
            }
        } catch (PDOException $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error en la base de datos: ' . $e->getMessage());
        }
    } else {
        $arrResponse = array('status' => false, 'msg' => 'Datos incorrectos');
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}
die();
?>
