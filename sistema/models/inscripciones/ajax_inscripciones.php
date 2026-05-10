<?php
require_once '../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
    exit;
}

if (!empty($_POST)) {
    $errors = [];

    // Sanitizar y validar campos
    $idInscripcion = isset($_POST['idInscripcion']) ? intval($_POST['idInscripcion']) : 0;
    $alumno_id    = isset($_POST['listAlumno'])   ? intval($_POST['listAlumno'])   : 0;
    $grado_id     = isset($_POST['listGrado'])    ? intval($_POST['listGrado'])    : 0;
    $seccion_id   = isset($_POST['listSeccion'])  ? intval($_POST['listSeccion'])  : 0;
    $turno_id     = isset($_POST['listTurno'])    ? intval($_POST['listTurno'])    : 0;
    $profesor_id  = isset($_POST['listProfesor']) ? intval($_POST['listProfesor']) : 0;

    $statusInput = $_POST['listStatus'] ?? '1';
    $status = is_numeric($statusInput) ? intval($statusInput) : 1;

    // Validaciones básicas
    if ($alumno_id  <= 0) $errors[] = 'El alumno es obligatorio';
    if ($grado_id   <= 0) $errors[] = 'El grado es obligatorio';
    if ($seccion_id <= 0) $errors[] = 'La sección es obligatoria';
    if ($turno_id   <= 0) $errors[] = 'El turno es obligatorio';
    if ($profesor_id <= 0) $errors[] = 'El profesor es obligatorio';

    if (!empty($errors)) {
        echo json_encode(['status' => false, 'msg' => implode(', ', $errors)]);
        exit;
    }

    try {
        // Obtener el periodo activo
        $periodo_id = 0;

        if ($idInscripcion > 0) {
            // Edición: mantener el periodo original
            $qGetP = $pdo->prepare("SELECT periodo_id FROM inscripcion WHERE inscripcion_id = ?");
            $qGetP->execute([$idInscripcion]);
            $resP = $qGetP->fetch(PDO::FETCH_ASSOC);
            if ($resP) {
                $periodo_id = intval($resP['periodo_id']);
            }
        }

        if ($periodo_id <= 0) {
            // Nuevo registro o no se encontró: buscar periodo activo
            $qActiveP = $pdo->prepare("SELECT periodo_id FROM periodo_escolar WHERE estatus = 1 LIMIT 1");
            $qActiveP->execute();
            $resActiveP = $qActiveP->fetch(PDO::FETCH_ASSOC);
            if ($resActiveP) {
                $periodo_id = intval($resActiveP['periodo_id']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'No hay un periodo escolar activo. Por favor active un periodo antes de inscribir.']);
                exit;
            }
        }

        // Verificar duplicidad: un alumno solo puede tener una inscripción activa por periodo
        $sqlDuplicado = "SELECT inscripcion_id FROM inscripcion WHERE alumno_id = ? AND status != 0 AND periodo_id = ?";
        $paramsDuplicado = [$alumno_id, $periodo_id];

        if ($idInscripcion > 0) {
            $sqlDuplicado .= " AND inscripcion_id != ?";
            $paramsDuplicado[] = $idInscripcion;
        }

        $qDup = $pdo->prepare($sqlDuplicado);
        $qDup->execute($paramsDuplicado);
        if ($qDup->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['status' => false, 'msg' => 'El alumno ya tiene una inscripción activa en este Periodo Escolar.']);
            exit;
        }

        if ($idInscripcion == 0) {
            // Generar ID manualmente (no hay SERIAL en la tabla)
            $qMaxId = $pdo->query("SELECT COALESCE(MAX(inscripcion_id), 0) + 1 AS nuevo_id FROM inscripcion");
            $nuevoId = intval($qMaxId->fetch(PDO::FETCH_ASSOC)['nuevo_id']);

            $sql = "INSERT INTO inscripcion (inscripcion_id, alumno_id, grado_id, seccion_id, turno_id, profesor_id, periodo_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $query = $pdo->prepare($sql);
            $success = $query->execute([$nuevoId, $alumno_id, $grado_id, $seccion_id, $turno_id, $profesor_id, $periodo_id, $status]);

            if ($success) {
                echo json_encode(['status' => true, 'msg' => 'Inscripción creada correctamente']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error al crear la inscripción']);
            }
        } else {
            // Actualizar inscripción existente
            $sql = "UPDATE inscripcion SET alumno_id = ?, grado_id = ?, seccion_id = ?, turno_id = ?, profesor_id = ?, status = ?
                    WHERE inscripcion_id = ?";
            $query = $pdo->prepare($sql);
            $success = $query->execute([$alumno_id, $grado_id, $seccion_id, $turno_id, $profesor_id, $status, $idInscripcion]);

            if ($success) {
                echo json_encode(['status' => true, 'msg' => 'Inscripción actualizada correctamente']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error al actualizar la inscripción']);
            }
        }

    } catch (PDOException $e) {
        error_log("Error en inscripciones: " . $e->getMessage());
        echo json_encode(['status' => false, 'msg' => 'Error del sistema: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => false, 'msg' => 'No se recibieron datos']);
}