<?php
require_once '../../includes/config.php';

// OPCION PARA PROFESOR
$sqlConsultaProfesor = "SELECT profesor_id,nombre,apellido FROM profesor WHERE estatus = 1 ORDER BY nombre, apellido ASC";
$queryConsultaProfesor = $pdo->prepare($sqlConsultaProfesor);
$queryConsultaProfesor->execute();
$data = $queryConsultaProfesor->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data,JSON_UNESCAPED_UNICODE);

?>