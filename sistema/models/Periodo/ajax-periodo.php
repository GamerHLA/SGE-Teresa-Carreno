<?php
require_once '../../includes/config.php';

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Validar y procesar datos
if (!empty($_POST)) {
    $errors = [];

    // Sanitizar y validar campos
    $idPeriodo = isset($_POST['idPeriodo']) ? intval($_POST['idPeriodo']) : 0;
    $anioInicio = isset($_POST['anioInicio']) ? intval($_POST['anioInicio']) : 0;
    $anioFin = isset($_POST['anioFin']) ? intval($_POST['anioFin']) : 0;
    $status = isset($_POST['listStatus']) ? intval($_POST['listStatus']) : 0;

    // Validaciones
    if (empty($anioInicio) || $anioInicio == 0) {
        $errors[] = 'El año de inicio es obligatorio';
    } else if ($anioInicio < 2025) {
        $errors[] = 'El año de inicio debe ser igual o mayor a 2025';
    } else if ($anioInicio > 9999) {
        $errors[] = 'El año de inicio no puede ser mayor a 9999';
    }

    if (empty($anioFin) || $anioFin == 0) {
        $errors[] = 'El año de fin es obligatorio';
    } else if ($anioFin < 2025) {
        $errors[] = 'El año de fin debe ser igual o mayor a 2025';
    } else if ($anioFin > 9999) {
        $errors[] = 'El año de fin no puede ser mayor a 9999';
    }

    if ($anioInicio > 0 && $anioFin > 0) {
        if ($anioFin <= $anioInicio) {
            $errors[] = 'El año de fin debe ser mayor al año de inicio';
        }
        if ($anioFin != ($anioInicio + 1)) {
            $errors[] = 'El año de fin debe ser exactamente un año después del año de inicio';
        }
    }

    if ($status !== 2 && $status !== 1) {
        $errors[] = 'El estado no es válido';
    }

    // Si hay errores, retornarlos
    if (!empty($errors)) {
        echo json_encode(['status' => false, 'msg' => 'Errores de validación', 'errors' => $errors]);
        exit;
    }

    try {
        // Verificar si el período ya existe (mismos años, excluyendo el actual y los inactivos)
        $sql = "SELECT periodo_id FROM periodo_escolar 
                WHERE anio_inicio = ? AND anio_fin = ? 
                AND periodo_id != ? AND estatus != 0";
        $query = $pdo->prepare($sql);
        $query->execute([$anioInicio, $anioFin, $idPeriodo]);
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            echo json_encode(['status' => false, 'msg' => 'Este período escolar ya está registrado']);
            exit;
        }

        // Verificar si existe un período activo (para nuevos y actualizaciones)
        if ($status == 1) {
            $sql = "SELECT periodo_id FROM periodo_escolar WHERE estatus = 1 AND periodo_id != ?";
            $query = $pdo->prepare($sql);
            $query->execute([$idPeriodo]);
            $periodoActivo = $query->fetch(PDO::FETCH_ASSOC);

            if ($periodoActivo) {
                echo json_encode(['status' => false, 'msg' => 'Ya existe un período escolar activo. Solo puede haber un período activo a la vez.']);
                exit;
            }
        }

        // Determinar si es inserción o actualización
        if ($idPeriodo == 0) {
            $sql = "INSERT INTO periodo_escolar (anio_inicio, anio_fin, estatus) VALUES (?, ?, ?)";
            $query = $pdo->prepare($sql);
            $success = $query->execute([$anioInicio, $anioFin, $status]);
            $msg = 'Período escolar creado correctamente';
        } else {
            $sql = "UPDATE periodo_escolar SET anio_inicio = ?, anio_fin = ?, estatus = ? WHERE periodo_id = ?";
            $query = $pdo->prepare($sql);
            $success = $query->execute([$anioInicio, $anioFin, $status, $idPeriodo]);

            // Si el período se está inactivando (status = 2), inactivar todos los cursos e inscripciones asociadas
            if ($success && $status == 2) {
                // Inactivar cursos
                $sqlInactivateCursos = "UPDATE curso SET estatusC = 2 WHERE periodo_id = ? AND estatusC = 1";
                $queryInactivateCursos = $pdo->prepare($sqlInactivateCursos);
                $queryInactivateCursos->execute([$idPeriodo]);

                // Inactivar inscripciones asociadas a los cursos de este periodo (solo las que estaban activas)
                $sqlInactivateInscripciones = "UPDATE inscripcion i 
                                               INNER JOIN curso c ON i.curso_id = c.curso_id 
                                               SET i.estatusI = 2 
                                               WHERE c.periodo_id = ? AND i.estatusI = 1";
                $queryInactivateInscripciones = $pdo->prepare($sqlInactivateInscripciones);
                $queryInactivateInscripciones->execute([$idPeriodo]);
            }

            // Si el período se está activando (status = 1), activar cursos e inscripciones asociados
            if ($success && $status == 1) {
                // Activar cursos
                $sqlActivateCursos = "UPDATE curso SET estatusC = 1 WHERE periodo_id = ? AND estatusC = 2";
                $queryActivateCursos = $pdo->prepare($sqlActivateCursos);
                $queryActivateCursos->execute([$idPeriodo]);

                // Activar inscripciones asociadas a los cursos de este periodo
                // SOLO si el alumno está activo (estatus = 1)
                $sqlActivateInscripciones = "UPDATE inscripcion i 
                                             INNER JOIN curso c ON i.curso_id = c.curso_id 
                                             INNER JOIN alumnos a ON i.alumno_id = a.alumno_id
                                             SET i.estatusI = 1 
                                             WHERE c.periodo_id = ? AND i.estatusI = 2 AND a.estatus = 1";
                $queryActivateInscripciones = $pdo->prepare($sqlActivateInscripciones);
                $queryActivateInscripciones->execute([$idPeriodo]);
            }

            $msg = 'Período escolar actualizado correctamente';
        }

        if ($success) {
            echo json_encode(['status' => true, 'msg' => $msg]);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Error al guardar en la base de datos']);
        }

    } catch (PDOException $e) {
        error_log("Error en base de datos: " . $e->getMessage());
        echo json_encode(['status' => false, 'msg' => 'Error del sistema. Por favor, intente más tarde.']);
    }
} else {
    echo json_encode(['status' => false, 'msg' => 'No se recibieron datos']);
}