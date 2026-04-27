<?php
require_once '../../includes/config.php';

$sql = "SELECT periodo_id, anio_inicio, anio_fin FROM periodo_escolar WHERE estatus = 1 ORDER BY periodo_id DESC";
$query = $pdo->prepare($sql);
$query->execute();
$data = $query->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
?>