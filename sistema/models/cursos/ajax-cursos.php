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
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action == 'checkProfesor') {
        $profesor = isset($_POST['listProfesor']) ? intval($_POST['listProfesor']) : 0;
        $turno = isset($_POST['listTurno']) ? intval($_POST['listTurno']) : 0;
        $periodo = isset($_POST['listPeriodo']) ? intval($_POST['listPeriodo']) : 0;
        $idCurso = isset($_POST['idCurso']) ? intval($_POST['idCurso']) : 0;

        if ($profesor > 0 && $turno > 0 && $periodo > 0) {
            try {
                if ($idCurso == 0) {
                    $sql = "SELECT curso_id FROM curso WHERE profesor_id = ? AND turno_id = ? AND periodo_id = ? AND estatusC = 1";
                    $query = $pdo->prepare($sql);
                    $query->execute([$profesor, $turno, $periodo]);
                } else {
                    $sql = "SELECT curso_id FROM curso WHERE profesor_id = ? AND turno_id = ? AND periodo_id = ? AND estatusC = 1 AND curso_id != ?";
                    $query = $pdo->prepare($sql);
                    $query->execute([$profesor, $turno, $periodo, $idCurso]);
                }
                $result = $query->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    echo json_encode(['status' => false, 'msg' => 'El profesor ya está asignado a otro grupo en el mismo turno y periodo']);
                } else {
                    echo json_encode(['status' => true, 'msg' => 'Disponible']);
                }
            } catch (PDOException $e) {
                echo json_encode(['status' => false, 'msg' => 'Error al validar disponibilidad']);
            }
        } else {
            echo json_encode(['status' => true, 'msg' => 'Incompleto']);
        }
        exit;
    }

    $errors = [];

    // Sanitizar y validar campos
    $idCurso = isset($_POST['idCurso']) ? intval($_POST['idCurso']) : 0;
    $grado = isset($_POST['txtGrado']) ? $_POST['txtGrado'] : '';
    $seccion = isset($_POST['txtSeccion']) ? $_POST['txtSeccion'] : '';
    $cupo = isset($_POST['txtCupo']) ? intval($_POST['txtCupo']) : 0;
    $turno = isset($_POST['listTurno']) ? intval($_POST['listTurno']) : 0;
    $profesor = isset($_POST['listProfesor']) ? intval($_POST['listProfesor']) : 0;
    $periodo = isset($_POST['listPeriodo']) ? intval($_POST['listPeriodo']) : 0;

    // Validaciones
    if (empty($grado))
        $errors[] = 'El grado es obligatorio';
    if (empty($seccion))
        $errors[] = 'La sección es obligatoria';
    // if ($cupo <= 0)  // Permitir cupo 0
    //    $errors[] = 'El cupo debe ser mayor a 0';
    if ($cupo > 50)
        $errors[] = 'El cupo no puede ser mayor a 50';
    if ($turno <= 0)
        $errors[] = 'El turno es obligatorio';
    // if ($profesor <= 0) // Permitir profesor 0 (sin asignar)
    //    $errors[] = 'El profesor es obligatorio';
    if ($periodo <= 0)
        $errors[] = 'El periodo escolar es obligatorio';

    // Si hay errores, retornarlos
    if (!empty($errors)) {
        echo json_encode(['status' => false, 'msg' => 'Errores de validación', 'errors' => $errors]);
        exit;
    }

    // Obtener IDs de Grado y Sección
    try {
        $stmtG = $pdo->prepare("SELECT id_grado FROM grados WHERE grado = ? LIMIT 1");
        $stmtG->execute([$grado]);
        $resG = $stmtG->fetch(PDO::FETCH_ASSOC);
        $idGradoReal = $resG ? intval($resG['id_grado']) : 0;

        $stmtS = $pdo->prepare("SELECT id_seccion FROM seccion WHERE seccion = ? LIMIT 1");
        $stmtS->execute([$seccion]);
        $resS = $stmtS->fetch(PDO::FETCH_ASSOC);
        $idSeccionReal = $resS ? intval($resS['id_seccion']) : 0;

        if ($idGradoReal <= 0 || $idSeccionReal <= 0) {
            echo json_encode(['status' => false, 'msg' => 'El grado o sección especificado no es válido en el sistema']);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => false, 'msg' => 'Error al validar grado/sección: ' . $e->getMessage()]);
        exit;
    }

    try {
        // Verificar si ya existe la combinación grado-sección-turno-periodo
        if ($idCurso == 0) {
            // Para inserción: verificar si ya existe (excluyendo inactivos)
            $sql = "SELECT curso_id FROM curso WHERE grados_id = ? AND seccion_id = ? AND turno_id = ? AND periodo_id = ? AND estatusC = 1";
            $query = $pdo->prepare($sql);
            $query->execute([$idGradoReal, $idSeccionReal, $turno, $periodo]);
            $result = $query->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                echo json_encode(['status' => false, 'msg' => 'Ya existe un registro activo con la misma combinación de grado, sección, turno y periodo']);
                exit;
            }

            // Verificar que el profesor no esté asignado a otro registro en el mismo turno y periodo
            $sql = "SELECT curso_id FROM curso WHERE profesor_id = ? AND turno_id = ? AND periodo_id = ? AND estatusC = 1";
            $query = $pdo->prepare($sql);
            $query->execute([$profesor, $turno, $periodo]);
            $result = $query->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                echo json_encode(['status' => false, 'msg' => 'El profesor ya está asignado a otro grupo en el mismo turno y periodo']);
                exit;
            }

            // Insertar nuevo registro - Siempre con estatusC = 1 (Activo)
            $sql = "INSERT INTO curso (grados_id, seccion_id, cupo, turno_id, profesor_id, periodo_id, estatusC) VALUES (?, ?, ?, ?, ?, ?, 1)";
            $query = $pdo->prepare($sql);
            $success = $query->execute([$idGradoReal, $idSeccionReal, $cupo, $turno, $profesor, $periodo]);

            if ($success) {
                echo json_encode(['status' => true, 'msg' => 'Se ha creado correctamente']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error al crear el registro']);
            }

        } else {
            // Para actualización: verificar si ya existe en otros registros (excluyendo el actual)
            $sql = "SELECT curso_id FROM curso WHERE grados_id = ? AND seccion_id = ? AND turno_id = ? AND periodo_id = ? AND estatusC = 1 AND curso_id != ?";
            $query = $pdo->prepare($sql);
            $query->execute([$idGradoReal, $idSeccionReal, $turno, $periodo, $idCurso]);
            $result = $query->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                echo json_encode(['status' => false, 'msg' => 'Ya existe un registro activo con la misma combinación de grado, sección, turno y periodo']);
                exit;
            }

            // Verificar que el profesor no esté asignado a otro registro en el mismo turno y periodo (excluyendo el actual)
            $sql = "SELECT curso_id FROM curso WHERE profesor_id = ? AND turno_id = ? AND periodo_id = ? AND estatusC = 1 AND curso_id != ?";
            $query = $pdo->prepare($sql);
            $query->execute([$profesor, $turno, $periodo, $idCurso]);
            $result = $query->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                echo json_encode(['status' => false, 'msg' => 'El profesor ya está asignado a otro grupo en el mismo turno y periodo']);
                exit;
            }

            // Actualizar registro existente - NO modifica estatusC (se maneja con botones)
            $sql = "UPDATE curso SET grados_id = ?, seccion_id = ?, cupo = ?, turno_id = ?, profesor_id = ?, periodo_id = ? WHERE curso_id = ?";
            $query = $pdo->prepare($sql);
            $success = $query->execute([$idGradoReal, $idSeccionReal, $cupo, $turno, $profesor, $periodo, $idCurso]);

            if ($success) {
                echo json_encode(['status' => true, 'msg' => 'Se ha actualizado correctamente']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error al actualizar el registro']);
            }
        }

    } catch (PDOException $e) {
        error_log("Error en base de datos: " . $e->getMessage());
        // Mostrar el error real para debugging (en producción, cambiar por mensaje genérico)
        echo json_encode(['status' => false, 'msg' => 'Error del sistema: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => false, 'msg' => 'No se recibieron datos']);
}