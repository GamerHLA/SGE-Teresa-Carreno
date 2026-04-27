<?php

require_once '../../includes/config.php';

$sqlAlumno = "SELECT a.alumno_id, a.nombre, a.apellido, a.cedula, a.estatus, n.codigo as nacionalidad 
              FROM alumnos a 
              INNER JOIN nacionalidades n ON a.id_nacionalidades = n.id 
              WHERE a.estatus = 1";
$queryAlumno = $pdo->prepare($sqlAlumno);
$queryAlumno->execute();
$data = $queryAlumno->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data,JSON_UNESCAPED_UNICODE);