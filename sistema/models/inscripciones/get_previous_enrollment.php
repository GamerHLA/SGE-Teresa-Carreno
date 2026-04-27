<?php
require_once '../../includes/config.php';

if (!empty($_GET['alumno_id'])) {
    $alumnoId = intval($_GET['alumno_id']);

    try {
        // Consultar la inscripción más reciente del alumno
        // Ordenar por periodo (año más reciente) y limitar a 1 resultado
        $sql = "SELECT 
                    i.inscripcion_id, 
                    g.grado, 
                    s.seccion, 
                    CONCAT(pe.anio_inicio, ' - ', pe.anio_fin) as periodo,
                    t.tipo_turno,
                    pe.anio_inicio,
                    pe.anio_fin
                FROM inscripcion i
                INNER JOIN curso c ON i.curso_id = c.curso_id
                INNER JOIN grados g ON c.grados_id = g.id_grado
                INNER JOIN seccion s ON c.seccion_id = s.id_seccion
                INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
                INNER JOIN turno t ON i.turno_id = t.turno_id
                WHERE i.alumno_id = ? AND i.estatusI != 0
                ORDER BY pe.anio_inicio DESC, pe.anio_fin DESC
                LIMIT 1";

        $query = $pdo->prepare($sql);
        $query->execute(array($alumnoId));
        $data = $query->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $arrResponse = array(
                'status' => true,
                'data' => $data,
                'msg' => 'Inscripción anterior encontrada'
            );
        } else {
            $arrResponse = array(
                'status' => false,
                'msg' => 'No se encontró inscripción anterior'
            );
        }
    } catch (PDOException $e) {
        error_log("Error en get_previous_enrollment.php: " . $e->getMessage());
        $arrResponse = array(
            'status' => false,
            'msg' => 'Error al consultar inscripción anterior'
        );
    }
} else {
    $arrResponse = array(
        'status' => false,
        'msg' => 'ID de alumno no proporcionado'
    );
}

echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
?>