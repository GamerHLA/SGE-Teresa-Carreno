<?php

require_once '../../includes/config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que el parámetro idUser exista y no esté vacío
    if (isset($_POST['idUser']) && !empty($_POST['idUser'])) {
        $idUser = intval($_POST['idUser']); // Convertir a entero por seguridad

        // Verificar si el usuario es administrador
        $sql_rol = "SELECT rol FROM usuarios WHERE user_id = ?";
        $query_rol = $pdo->prepare($sql_rol);
        $query_rol->execute([$idUser]);
        $user_rol = $query_rol->fetchColumn();

        if ($user_rol == 1) { // 1 es el ID de Administrador
            $sql_count = "SELECT COUNT(*) FROM usuarios WHERE rol = 1 AND estatus = 1";
            $query_count = $pdo->prepare($sql_count);
            $query_count->execute();
            $admin_count = $query_count->fetchColumn();

            if ($admin_count <= 1) {
                echo json_encode(array('status' => false, 'msg' => 'No se puede eliminar el único administrador del sistema'), JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $sql = "UPDATE usuarios SET estatus = 2 WHERE user_id = ?";
        $query = $pdo->prepare($sql);
        $result = $query->execute([$idUser]);

        if ($result && $query->rowCount() > 0) {
            $arrResponse = array('status' => true, 'msg' => 'Usuario inhabilitado correctamente');
        } else {
            $arrResponse = array('status' => false, 'msg' => 'El usuario ya se encuentra inhabilitado');
        }
    } else {
        $arrResponse = array('status' => false, 'msg' => 'Parámetro idUser faltante');
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(array('status' => false, 'msg' => 'Método no permitido'), JSON_UNESCAPED_UNICODE);
}