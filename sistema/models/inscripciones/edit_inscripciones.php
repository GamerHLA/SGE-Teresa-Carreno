<?php

require_once '../../includes/config.php';

if (!empty($_GET)) {
    $idInscripcion = $_GET['id'];
    $sql = "SELECT i.*, a.*, g.grado, s.seccion, pe.periodo_id, CONCAT(pe.anio_inicio, ' - ', pe.anio_fin) as periodo_completo, t.turno_id, t.tipo_turno 
            FROM inscripcion as i 
            INNER JOIN alumnos as a ON i.alumno_id = a.alumno_id 
            INNER JOIN grados as g ON i.grado_id = g.id_grado
            INNER JOIN secciones as s ON i.seccion_id = s.id_seccion
            INNER JOIN periodo_escolar as pe ON i.periodo_id = pe.periodo_id 
            INNER JOIN turno as t ON i.turno_id = t.turno_id 
            WHERE i.inscripcion_id = ?";
    $query = $pdo->prepare($sql);
    $query->execute(array($idInscripcion));
    $data = $query->fetch(PDO::FETCH_ASSOC);
    if (empty($data)) {
        $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados');
    } else {
        // Enviar estatusI para mantener la compatibilidad con el JS
        $data['estatusI'] = $data['status'];
        $arrResponse = array('status' => true, 'data' => $data);
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
}