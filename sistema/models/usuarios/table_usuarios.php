<?php

require_once '../../includes/config.php';

header('Content-Type: application/json; charset=UTF-8');

// EXTRAER USUARIOS
try {
    $sql = "SELECT u.user_id,u.nombre,u.usuario,r.rol_id,r.nombre_rol,u.estatus FROM usuarios as u INNER JOIN rol as r ON u.rol = r.rol_id WHERE u.estatus != 0";
    $query = $pdo->prepare($sql);
    $query->execute();

    $data = $query->fetchAll(PDO::FETCH_ASSOC);

    for ($i = 0; $i < count($data); $i++) {
        if ($data[$i]['estatus'] == 1) {
            $data[$i]['estatus'] = '<span class="badge badge-success">Activo</span>';
        } else {
            $data[$i]['estatus'] = '<span class="badge badge-danger">Inactivo</span>';
        }

        // Define buttons based on status
        $btnEdit = '<button class="btn btn-dark btn-sm btnEditUser" rl="' . $data[$i]['user_id'] . '" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
        $btnActivate = '<button class="btn btn-success btn-sm btnActivateUsuario" rl="' . $data[$i]['user_id'] . '" title="Activar"><i class="fas fa-check"></i></button>';
        $btnInactivate = '<button class="btn btn-danger btn-sm btnDelUser" rl="' . $data[$i]['user_id'] . '" title="Inhabilitar"><i class="fas fa-ban" aria-hidden="true"></i></button>';

        // Build options based on active status
        // Build options based on active status
        if ($data[$i]['estatus'] == '<span class="badge badge-success">Activo</span>') {
             $btnEdit = '<button class="btn btn-dark btn-sm btnEditUser" rl="' . $data[$i]['user_id'] . '" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
            $data[$i]['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnInactivate . '</div>';
        } else {
             $btnEdit = '<button class="btn btn-dark btn-sm btnEditUser" rl="' . $data[$i]['user_id'] . '" title="Editar" disabled><i class="fas fa-pencil-alt"></i></button>';
            $data[$i]['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnActivate . '</div>';
        }
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(array(), JSON_UNESCAPED_UNICODE);
}
die();
