<?php

require_once '../../includes/config.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // Consulta para obtener cursos con sus detalles usando JOINs en las tablas maestras
    // Solo mostrar cursos con profesor activo (estatus = 1) y que tengan profesor asignado
    $sqlCurso = "SELECT c.*, g.grado, s.seccion, c.turno_id, pe.periodo_id, CONCAT(pe.anio_inicio, ' - ', pe.anio_fin) as periodo_completo, t.tipo_turno, p.profesor_id, p.nombre, p.apellido, p.estatus as profesor_estatus,
                 (SELECT COUNT(*) FROM inscripcion i WHERE i.curso_id = c.curso_id AND i.estatusI != 0) as inscritos
                 FROM curso as c 
                 INNER JOIN grados as g ON c.grados_id = g.id_grado
                 INNER JOIN seccion as s ON c.seccion_id = s.id_seccion
                 LEFT JOIN periodo_escolar as pe ON c.periodo_id = pe.periodo_id 
                 INNER JOIN profesor as p ON c.profesor_id = p.profesor_id 
                 INNER JOIN turno as t ON c.turno_id = t.turno_id 
                 WHERE c.estatusC = 1 AND pe.estatus = 1
                 AND c.profesor_id > 0 AND p.estatus = 1
                 ORDER BY g.grado, s.seccion, t.tipo_turno";

    $queryCurso = $pdo->prepare($sqlCurso);
    $queryCurso->execute();
    $data = $queryCurso->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log("Error en options-cursos.php: " . $e->getMessage());
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}