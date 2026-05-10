<?php
require_once '../../includes/config.php';

// OPCION PARA PROFESOR
$sqlConsultaProfesor = "SELECT pr.profesor_id, p.nombres AS nombre, p.apellidos AS apellido 
                       FROM profesores pr 
                       INNER JOIN personas p ON p.profesores_id = pr.profesor_id 
                       WHERE pr.status = 1 
                       ORDER BY p.nombres, p.apellidos ASC";
$queryConsultaProfesor = $pdo->prepare($sqlConsultaProfesor);
$queryConsultaProfesor->execute();
$data = $queryConsultaProfesor->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data,JSON_UNESCAPED_UNICODE);

?>