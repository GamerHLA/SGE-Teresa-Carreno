<?php

require_once '../../includes/config.php';

try {
    // Consulta SQL para obtener todos los representantes con estatus diferente de 0
    $sql = "SELECT 
                r.*,
                e.estado as nombre_estado,
                c.ciudad as nombre_ciudad,
                m.municipio as nombre_municipio,
                p.parroquia as nombre_parroquia
            FROM representantes r
            LEFT JOIN estados e ON r.id_estado = e.id_estado
            LEFT JOIN ciudades c ON r.id_ciudad = c.id_ciudad
            LEFT JOIN municipios m ON r.id_municipio = m.id_municipio
            LEFT JOIN parroquias p ON r.id_parroquia = p.id_parroquia
            WHERE r.estatus != 2";

    $query = $pdo->prepare($sql);
    $query->execute();
    $data = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En caso de error, devolver array vacío
    error_log("Error en table_representantes.php: " . $e->getMessage());
    $data = [];
}

for ($i = 0; $i < count($data); $i++) {
    if ($data[$i]['estatus'] == 1) {
        $data[$i]['estatus'] = '<span class="badge badge-success">Activo</span>';
    } else {
        $data[$i]['estatus'] = '<span class="badge badge-danger">Inactivo</span>';
    }

    // Formatear Dirección Completa
    $direccionCompleta = '';
    $partesDireccion = [];

    if (!empty($data[$i]['nombre_parroquia']))
        $partesDireccion[] = $data[$i]['nombre_parroquia'];
    if (!empty($data[$i]['nombre_municipio']))
        $partesDireccion[] = $data[$i]['nombre_municipio'];
    if (!empty($data[$i]['nombre_ciudad']))
        $partesDireccion[] = $data[$i]['nombre_ciudad'];
    if (!empty($data[$i]['nombre_estado']))
        $partesDireccion[] = $data[$i]['nombre_estado'];

    if (!empty($partesDireccion)) {
        $direccionCompleta .= implode(', ', $partesDireccion);
    }
    $data[$i]['direccion'] = $direccionCompleta;

    // Formatear Teléfono
    if (!empty($data[$i]['telefono']) && strlen($data[$i]['telefono']) == 11) {
        $data[$i]['telefono'] = substr($data[$i]['telefono'], 0, 4) . '-' . substr($data[$i]['telefono'], 4);
    }

    // Define buttons based on status
    $btnEdit = '<button class="btn btn-dark btn-sm btnEditRepresentantes" rl="' . $data[$i]['representantes_id'] . '" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
    $btnActivate = '<button class="btn btn-success btn-sm btnActivateRepresentante" rl="' . $data[$i]['representantes_id'] . '" title="Activar"><i class="fas fa-check"></i></button>';
    $btnInactivate = '<button class="btn btn-danger btn-sm btnDelRepresentantes" rl="' . $data[$i]['representantes_id'] . '" title="Inactivar"><i class="fas fa-ban" aria-hidden="true"></i></button>';

    // Build options based on active status
    // Build options based on active status
    if ($data[$i]['estatus'] == '<span class="badge badge-success">Activo</span>') {
        // Active: Edit button is enabled
        $btnEdit = '<button class="btn btn-dark btn-sm btnEditRepresentantes" rl="' . $data[$i]['representantes_id'] . '" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
        $data[$i]['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnInactivate . '</div>';
    } else {
        // Inactive: Edit button is disabled
        $btnEdit = '<button class="btn btn-dark btn-sm btnEditRepresentantes" rl="' . $data[$i]['representantes_id'] . '" title="Editar" disabled><i class="fas fa-pencil-alt"></i></button>';
        $data[$i]['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnActivate . '</div>';
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
die();

