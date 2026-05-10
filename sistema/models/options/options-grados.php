<?php
require_once '../../includes/config.php';

$sqlConsulta = "SELECT id_grado, grado FROM grados WHERE status = 1 ORDER BY grado ASC";
$queryConsulta = $pdo->prepare($sqlConsulta);
$queryConsulta->execute();
$data = $queryConsulta->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>
