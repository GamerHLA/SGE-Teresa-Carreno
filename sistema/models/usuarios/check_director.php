<?php
require_once '../../includes/config.php';

$exclude_id = isset($_GET['exclude_id']) ? intval($_GET['exclude_id']) : 0;

// Verificar si existe un director ACTIVO (excluyendo el ID proporcionado)
$fechaActual = date('Y-m-d');
$sql = "SELECT profesor_id FROM profesor 
        WHERE es_director = 1 
        AND profesor_id != ? 
        AND (director_fecha_fin IS NULL OR director_fecha_fin >= ?)";
$query = $pdo->prepare($sql);
$query->execute(array($exclude_id, $fechaActual));
$result = $query->fetch(PDO::FETCH_ASSOC);

$response = array('exists' => $result ? true : false);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>