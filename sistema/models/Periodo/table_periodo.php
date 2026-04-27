<?php

require_once '../../includes/config.php';

$sql = "SELECT *, CONCAT(anio_inicio, ' - ', anio_fin) as periodo_completo FROM periodo_escolar WHERE estatus != 0 ORDER BY anio_inicio DESC";
$query = $pdo->prepare($sql);
$query->execute();
$data = $query->fetchAll(PDO::FETCH_ASSOC);

for ($i = 0; $i < count($data); $i++) {
    $btnActivate = '<button class="btn btn-success btn-sm btnActivatePeriodo" rl="' . $data[$i]['periodo_id'] . '" title="Activar"><i class="fas fa-check"></i></button>';
    $btnInactivate = '<button class="btn btn-danger btn-sm btnDelPeriodo" rl="' . $data[$i]['periodo_id'] . '" title="Inhabilitar"><i class="fas fa-ban" aria-hidden="true"></i></button>';

    if ($data[$i]['estatus'] == 1) {
        $data[$i]['estatus'] = '<span class="badge badge-success">Activo</span>';
        $data[$i]['options'] = '<div class="text-center">' . $btnInactivate . '</div>';
    } else {
        $data[$i]['estatus'] = '<span class="badge badge-danger">Inactivo</span>';
        $data[$i]['options'] = '<div class="text-center">' . $btnActivate . '</div>';
    }
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
die();