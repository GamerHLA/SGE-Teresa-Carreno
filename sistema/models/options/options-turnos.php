<?php

require_once '../../includes/config.php';

$sqlConsultaTurno = "SELECT turno_id,tipo_turno FROM turno";
$queryConsultaTurno = $pdo->prepare($sqlConsultaTurno);
$queryConsultaTurno->execute();
$data = $queryConsultaTurno->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data,JSON_UNESCAPED_UNICODE);