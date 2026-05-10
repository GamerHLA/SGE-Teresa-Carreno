<?php
require_once '../../includes/config.php';

$sqlConsulta = "SELECT id_seccion, seccion FROM secciones WHERE estatus = 1";
$queryConsulta = $pdo->prepare($sqlConsulta);
$queryConsulta->execute();
$data = $queryConsulta->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>
