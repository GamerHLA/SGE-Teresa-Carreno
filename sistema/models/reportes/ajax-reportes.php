<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/config.php';

if (!empty($_POST)) {
    if ($_POST['action'] == 'get_cursos') {
        $sql = "SELECT * FROM curso WHERE estatusC = 1";
        $query = $pdo->prepare($sql);
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_POST['action'] == 'get_periodos') {
        $sql = "SELECT * FROM periodo_escolar ORDER BY periodo_id DESC";
        $query = $pdo->prepare($sql);
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_POST['action'] == 'get_periodo_actual') {
        $sql = "SELECT * FROM periodo_escolar WHERE estatus = 1 LIMIT 1";
        $query = $pdo->prepare($sql);
        $query->execute();
        $data = $query->fetch(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }



    // Búsqueda de alumnos estándar (Solo activos e inscritos)
    if ($_POST['action'] == 'get_alumno_motivo') {
        $alumno_id = $_POST['alumno_id'];
        // Buscamos la observación de inhabilitación
        $sql = "SELECT observacion FROM observaciones 
                WHERE alumno_id = ? AND tipo_observacion = 'inhabilitacion' 
                ORDER BY observacion_id DESC LIMIT 1";
        $query = $pdo->prepare($sql);
        $query->execute([$alumno_id]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        $motivo = '';
        if ($row) {
            $obs = $row['observacion'];
            if (strpos($obs, 'Motivo:') !== false) {
                $parts = explode('Motivo:', $obs);
                $motivo = trim($parts[1]);
            } else {
                $motivo = $obs; // Si no tiene el tag Motivo, devolvemos todo
            }
        }
        echo json_encode(['status' => true, 'motivo' => $motivo, 'full' => ($row ? $row['observacion'] : '')]);
        exit;
    }

    if ($_POST['action'] == 'search_alumno') {
        $search = isset($_POST['search']) ? $_POST['search'] : '';
        $searchParam = "%" . $search . "%";

        $sql = "SELECT i.inscripcion_id, 
                       a.alumno_id,
                       a.nombre as alumno_nombre, 
                       a.apellido as alumno_apellido,
                       a.cedula,
                       n.codigo as nacionalidad,
                       g.grado, 
                       s.seccion
                FROM inscripcion i
                INNER JOIN alumnos a ON i.alumno_id = a.alumno_id
                INNER JOIN nacionalidades n ON a.id_nacionalidades = n.id
                INNER JOIN curso c ON i.curso_id = c.curso_id
                INNER JOIN grados g ON c.grados_id = g.id_grado
                INNER JOIN seccion s ON c.seccion_id = s.id_seccion
                INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
                WHERE i.estatusI = 1 AND pe.estatus = 1
                AND (a.cedula LIKE ? OR a.nombre LIKE ? OR a.apellido LIKE ?)
                ORDER BY a.nombre ASC, a.apellido ASC
                LIMIT 20";

        $query = $pdo->prepare($sql);
        $query->execute([$searchParam, $searchParam, $searchParam]);
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Búsqueda de alumnos para Boleta de Retiro (Solo INACTIVOS/RETIRADOS)
    if ($_POST['action'] == 'search_alumno_retiro') {
        $search = isset($_POST['search']) ? $_POST['search'] : '';
        $searchParam = "%" . $search . "%";

        // SOLO alumnos inactivos (estatus = 2) que tengan al menos una inscripción
        // SOLO alumnos inactivos (estatus = 2) que tengan al menos una inscripción
        // Usamos una subconsulta para asegurarnos de obtener la última inscripción y sus datos correctos (grado/sección)
        $sql = "SELECT a.alumno_id,
                       a.nombre as alumno_nombre, 
                       a.apellido as alumno_apellido,
                       a.cedula,
                       n.codigo as nacionalidad,
                       g.grado, 
                       s.seccion,
                       i.inscripcion_id
                FROM alumnos a
                INNER JOIN nacionalidades n ON a.id_nacionalidades = n.id
                INNER JOIN inscripcion i ON i.inscripcion_id = (
                    SELECT MAX(i2.inscripcion_id) 
                    FROM inscripcion i2 
                    WHERE i2.alumno_id = a.alumno_id
                )
                INNER JOIN curso c ON i.curso_id = c.curso_id
                INNER JOIN grados g ON c.grados_id = g.id_grado
                INNER JOIN seccion s ON c.seccion_id = s.id_seccion
                INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
                WHERE a.estatus = 2 AND pe.estatus = 1
                AND (a.cedula LIKE ? OR a.nombre LIKE ? OR a.apellido LIKE ?)
                ORDER BY a.nombre ASC, a.apellido ASC
                LIMIT 20";

        $query = $pdo->prepare($sql);
        $query->execute([$searchParam, $searchParam, $searchParam]);
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }



    if ($_POST['action'] == 'search_representante') {
        $search = isset($_POST['search']) ? $_POST['search'] : '';
        $reporte = isset($_POST['reporte']) ? $_POST['reporte'] : '';
        $searchParam = "%" . $search . "%";

        $sql = "SELECT DISTINCT r.representantes_id as representante_id, r.cedula, n.codigo as nacionalidad, r.nombre, r.apellido
                FROM representantes r
                INNER JOIN nacionalidades n ON r.id_nacionalidades = n.id";
        
        // Si es constancia de asistencia (justificativo), filtramos por alumnos e inscripciones activas
        if ($reporte == 'justificativo') {
            $sql .= " INNER JOIN alumno_representante ar ON r.representantes_id = ar.representante_id AND ar.estatus = 1
                      INNER JOIN alumnos a ON ar.alumno_id = a.alumno_id AND a.estatus = 1
                      INNER JOIN inscripcion i ON a.alumno_id = i.alumno_id AND i.estatusI = 1
                      INNER JOIN curso c ON i.curso_id = c.curso_id
                      INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id AND pe.estatus = 1
                      WHERE r.estatus = 1";
        } else {
            $sql .= " WHERE r.estatus = 1";
        }
        
        $sql .= " AND (r.cedula LIKE ? OR r.nombre LIKE ? OR r.apellido LIKE ?)
                ORDER BY r.nombre ASC, r.apellido ASC
                LIMIT 20";

        $query = $pdo->prepare($sql);
        $query->execute([$searchParam, $searchParam, $searchParam]);
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_POST['action'] == 'get_motivos') {
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'motivo_%';

        // Si el tipo es retiro, incluimos inhabilitación. Si no, solo el tipo solicitado.
        // Y filtramos por alumno_id > 0 o representantes_id > 0 dependiendo del tipo.

        $sql = "SELECT DISTINCT observacion, tipo_observacion 
                FROM observaciones 
                WHERE 1=1 ";

        if ($tipo == 'motivo_retiro') {
            $sql .= " AND (tipo_observacion = 'motivo_retiro' OR tipo_observacion = 'inhabilitacion') AND alumno_id > 0";
        } elseif ($tipo == 'motivo_justificativo') {
            $sql .= " AND tipo_observacion = 'motivo_justificativo' AND representantes_id > 0";
        } else {
            $sql .= " AND tipo_observacion LIKE ? AND (alumno_id > 0 OR representantes_id > 0)";
        }

        // EXCLUIR motivos inhabilitados (Blacklist)
        // Buscamos si existe un registro con IDs NULL y estatus 0 para este motivo
        $sql .= " AND observacion NOT IN (
            SELECT observacion FROM observaciones 
            WHERE estatus = 0 AND (alumno_id IS NULL AND representantes_id IS NULL AND profesor_id IS NULL)
        )";

        $sql .= " ORDER BY observacion ASC";

        $query = $pdo->prepare($sql);
        if ($tipo != 'motivo_retiro' && $tipo != 'motivo_justificativo') {
            $query->execute([$tipo]);
        } else {
            $query->execute();
        }
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        $finalData = [];
        $seen = []; // Para evitar duplicados después del parseo

        foreach ($rows as $row) {
            $text = $row['observacion'];

            // Si es inhabilitación, extraemos solo el motivo
            if ($row['tipo_observacion'] == 'inhabilitacion') {
                // Formato esperado: "... . Motivo: XXXXX"
                $parts = explode('Motivo:', $text);
                if (count($parts) > 1) {
                    $text = trim(end($parts));
                }
            }

            $text = trim($text);
            if (!empty($text) && !in_array(strtolower($text), $seen)) {
                $seen[] = strtolower($text);
                $finalData[] = [
                    'id' => $text, // Usamos el texto como ID para el select2
                    'text' => $text
                ];
            }
        }

        // Ordenar alfabéticamente
        usort($finalData, function ($a, $b) {
            return strcasecmp($a['text'], $b['text']);
        });

        echo json_encode($finalData, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_POST['action'] == 'save_motivo') {
        $motivo = $_POST['motivo'];
        $tipo = $_POST['tipo']; // 'motivo_retiro' o 'motivo_justificativo'

        // Usar NULL si el ID es 0 o no está definido, para evitar errores de FK
        $alumno_id = (isset($_POST['alumno_id']) && $_POST['alumno_id'] > 0) ? $_POST['alumno_id'] : null;
        $representante_id = (isset($_POST['representante_id']) && $_POST['representante_id'] > 0) ? $_POST['representante_id'] : null;

        $result = false;

        // Si es un motivo de retiro, intentamos sobrescribir el motivo en la observación de 'inhabilitacion'
        if ($tipo == 'motivo_retiro' && $alumno_id) {
            // Buscamos si ya tiene una inhabilitación
            $sqlCheck = "SELECT observacion_id, observacion FROM observaciones 
                         WHERE alumno_id = ? AND tipo_observacion = 'inhabilitacion' 
                         ORDER BY observacion_id DESC LIMIT 1";
            $queryCheck = $pdo->prepare($sqlCheck);
            $queryCheck->execute([$alumno_id]);
            $obs = $queryCheck->fetch(PDO::FETCH_ASSOC);

            if ($obs) {
                // Sobrescribimos el motivo dentro de la observación existente
                // El formato suele ser: "... Motivo: XXX"
                $textoOriginal = $obs['observacion'];
                if (strpos($textoOriginal, 'Motivo:') !== false) {
                    $parts = explode('Motivo:', $textoOriginal);
                    $nuevoTexto = $parts[0] . 'Motivo: ' . $motivo;
                } else {
                    // Si no tiene el tag "Motivo:", lo agregamos al final
                    $nuevoTexto = $textoOriginal . ". Motivo: " . $motivo;
                }

                $sqlUpd = "UPDATE observaciones SET observacion = ? WHERE observacion_id = ?";
                $queryUpd = $pdo->prepare($sqlUpd);
                $result = $queryUpd->execute([$nuevoTexto, $obs['observacion_id']]);
            }
        }

        // Si no se sobrescribió (o es otro tipo), insertamos normal
        if (!$result) {
            $sql = "INSERT INTO observaciones (alumno_id, representantes_id, profesor_id, tipo_observacion, observacion, estatus) 
                    VALUES (?, ?, NULL, ?, ?, 1)";
            $query = $pdo->prepare($sql);
            $result = $query->execute([$alumno_id, $representante_id, $tipo, $motivo]);
        }

        if ($result) {
            echo json_encode(['status' => true, 'msg' => 'Motivo guardado']);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Error al guardar motivo']);
        }
        exit;
    }

    if ($_POST['action'] == 'disable_motivo') {
        $motivo = $_POST['motivo'];
        $tipo = $_POST['tipo']; // 'motivo_retiro' o 'motivo_justificativo'

        // En lugar de actualizar todos los registros antiguos, creamos/actualizamos un "registro centinela" 
        // (Blacklist) para este texto de motivo.
        // Un registro con IDs NULL y estatus 0 significa "Este texto ya no debe sugerirse".

        // 1. Verificamos si ya existe el registro centinela (puede estar estatus 1 si fue reactivado antes)
        $sqlCheck = "SELECT observacion_id FROM observaciones 
                     WHERE observacion = ? AND tipo_observacion = ? 
                     AND alumno_id IS NULL AND representantes_id IS NULL AND profesor_id IS NULL LIMIT 1";
        $queryCheck = $pdo->prepare($sqlCheck);
        $queryCheck->execute([$motivo, $tipo]);
        $blacklist = $queryCheck->fetch(PDO::FETCH_ASSOC);

        if ($blacklist) {
            // Ya existe, lo ponemos en estatus 0 (inhabilitado)
            $sqlUpd = "UPDATE observaciones SET estatus = 0 WHERE observacion_id = ?";
            $queryUpd = $pdo->prepare($sqlUpd);
            $res = $queryUpd->execute([$blacklist['observacion_id']]);
        } else {
            // No existe, creamos uno nuevo como Blacklist
            $sqlIns = "INSERT INTO observaciones (alumno_id, representantes_id, profesor_id, tipo_observacion, observacion, estatus) 
                       VALUES (NULL, NULL, NULL, ?, ?, 0)";
            $queryIns = $pdo->prepare($sqlIns);
            $res = $queryIns->execute([$tipo, $motivo]);
        }

        if ($res) {
            echo json_encode(['status' => true, 'msg' => 'Motivo inhabilitado (no se sugerirá más)']);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Error al inhabilitar motivo']);
        }
        exit;
    }

    if ($_POST['action'] == 'reactivate_motivo') {
        $motivo = $_POST['motivo'];
        $tipoRequested = $_POST['tipo'];

        // Para reactivar, simplemente buscamos el registro centinela y lo ponemos en estatus 1
        $sql = "UPDATE observaciones SET estatus = 1 
                WHERE observacion = ? 
                AND (tipo_observacion = 'motivo_retiro' OR tipo_observacion = 'motivo_justificativo' OR tipo_observacion = 'inhabilitacion')
                AND alumno_id IS NULL AND representantes_id IS NULL AND profesor_id IS NULL";
        $query = $pdo->prepare($sql);
        $res = $query->execute([$motivo]);

        if ($res) {
            echo json_encode(['status' => true, 'msg' => 'Motivo reactivado (volverá a sugerirse)']);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Error al reactivar motivo']);
        }
        exit;
    }
}
?>
