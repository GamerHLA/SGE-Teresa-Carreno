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
    $idInscripcion = isset($_POST['idInscripcion']) ? intval($_POST['idInscripcion']) : 0;
    $alumno = isset($_POST['listAlumno']) ? intval($_POST['listAlumno']) : 0;
    $curso = isset($_POST['listCurso']) ? intval($_POST['listCurso']) : 0;
    // El turno_id viene del curso seleccionado (campo hidden listTurnoId)
    $turno = isset($_POST['listTurnoId']) ? intval($_POST['listTurnoId']) : 0;

    // SOLUCIÓN: Manejo flexible del estado
    $statusInput = $_POST['listStatus'] ?? '';
    if (is_numeric($statusInput)) {
        $status = intval($statusInput);
    } else {
        // Si es texto, convertir a número
        $status = (strtolower($statusInput) == 'activo') ? 1 : 2;
    }

    // Validaciones
    if ($alumno <= 0)
        $errors[] = 'El alumno es obligatorio';
    if ($curso <= 0)
        $errors[] = 'El curso es obligatorio';

    // Si no se recibió el turno_id, obtenerlo del curso
    if ($turno <= 0 && $curso > 0) {
        try {
            $sqlTurno = "SELECT turno_id FROM curso WHERE curso_id = ?";
            $queryTurno = $pdo->prepare($sqlTurno);
            $queryTurno->execute([$curso]);
            $resultTurno = $queryTurno->fetch(PDO::FETCH_ASSOC);
            if ($resultTurno) {
                $turno = intval($resultTurno['turno_id']);
            } else {
                $errors[] = 'No se pudo obtener el turno del curso seleccionado';
            }
        } catch (PDOException $e) {
            $errors[] = 'Error al obtener el turno del curso';
        }
    }

    if ($turno <= 0)
        $errors[] = 'El turno es obligatorio';

    // SOLUCIÓN: Validación flexible de estado
    $estadosValidos = [2, 1]; // 0 = Inactivo, 1 = Activo
    if (!in_array($status, $estadosValidos)) {
        $errors[] = 'El estado no es válido. Use: 0 (Inactivo) o 1 (Activo)';
    }

    // Si hay errores, retornarlos
    if (!empty($errors)) {
        echo json_encode(['status' => false, 'msg' => 'Errores de validación', 'errors' => $errors]);
        exit;
    }

    // VERIFICAR PERIODO ACTIVO
    try {
        $sqlPeriodo = "SELECT pe.estatus 
                       FROM curso c 
                       INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id 
                       WHERE c.curso_id = ?";
        $queryPeriodo = $pdo->prepare($sqlPeriodo);
        $queryPeriodo->execute([$curso]);
        $resPeriodo = $queryPeriodo->fetch(PDO::FETCH_ASSOC);

        if ($resPeriodo && $resPeriodo['estatus'] != 1) {
            echo json_encode(['status' => false, 'msg' => 'No se puede inscribir en un periodo académico inactivo']);
            exit;
        }
    } catch (PDOException $e) {
        // Si falla la verificación (ej. columna no existe), continuamos pero logueamos
        error_log("Error al verificar estado del periodo: " . $e->getMessage());
    }

    // VERIFICAR ESTATUS DEL PROFESOR ASIGNADO AL CURSO
    // Esta validación es redundante ahora que filtramos en options-cursos.php, pero la mantenemos como seguridad adicional
    try {
        $sqlProfesor = "SELECT c.profesor_id, p.estatus, p.nombre, p.apellido
                       FROM curso c 
                       LEFT JOIN profesor p ON c.profesor_id = p.profesor_id 
                       WHERE c.curso_id = ?";
        $queryProfesor = $pdo->prepare($sqlProfesor);
        $queryProfesor->execute([$curso]);
        $resProfesor = $queryProfesor->fetch(PDO::FETCH_ASSOC);

        if ($resProfesor && $resProfesor['profesor_id'] > 0) {
            // Si el curso tiene un profesor asignado, verificar que esté activo
            if ($resProfesor['estatus'] != 1) {
                $nombreProfesor = ($resProfesor['nombre'] ?? '') . ' ' . ($resProfesor['apellido'] ?? '');
                echo json_encode(['status' => false, 'msg' => 'No se puede inscribir en este curso porque el profesor asignado (' . trim($nombreProfesor) . ') está inactivo']);
                exit;
            }
        }
    } catch (PDOException $e) {
        // Si falla la verificación, continuamos pero logueamos
        error_log("Error al verificar estado del profesor: " . $e->getMessage());
    }

    // VERIFICAR CUPO DISPONIBLE Y DUPLICIDAD DE INSCRIPCIÓN
    try {
        // Obtener datos del curso (cupo, grado, seccion, periodo)
        $sqlCursoInfo = "SELECT cupo, periodo_id FROM curso WHERE curso_id = ?";
        $queryCursoInfo = $pdo->prepare($sqlCursoInfo);
        $queryCursoInfo->execute([$curso]);
        $resCursoInfo = $queryCursoInfo->fetch(PDO::FETCH_ASSOC);

        if (!$resCursoInfo) {
            echo json_encode(['status' => false, 'msg' => 'Curso no encontrado']);
            exit;
        }

        $cupoMaximo = intval($resCursoInfo['cupo']);
        $periodoTarget = $resCursoInfo['periodo_id'];

        // VERIFICAR DUPLICIDAD: Un alumno solo puede tener una inscripción por Periodo Escolar (sin importar grado o sección)
        $sqlDuplicado = "SELECT i.inscripcion_id 
                         FROM inscripcion i 
                         INNER JOIN curso c ON i.curso_id = c.curso_id 
                         WHERE i.alumno_id = ? 
                         AND i.estatusI != 0 
                         AND c.periodo_id = ?";

        $paramsDuplicado = [$alumno, $periodoTarget];

        // Si es actualización, excluir la inscripción actual
        if ($idInscripcion > 0) {
            $sqlDuplicado .= " AND i.inscripcion_id != ?";
            $paramsDuplicado[] = $idInscripcion;
        }

        $queryDuplicado = $pdo->prepare($sqlDuplicado);
        $queryDuplicado->execute($paramsDuplicado);
        $resDuplicado = $queryDuplicado->fetch(PDO::FETCH_ASSOC);

        if ($resDuplicado) {
            echo json_encode(['status' => false, 'msg' => 'El alumno ya tiene una inscripción activa en este Periodo Escolar.']);
            exit;
        }

        // Contar inscritos activos en el curso
        $sqlCount = "SELECT COUNT(*) as total FROM inscripcion WHERE curso_id = ? AND estatusI != 0";
        // Si es actualización, excluir la inscripción actual del conteo
        if ($idInscripcion > 0) {
            $sqlCount .= " AND inscripcion_id != ?";
            $paramsCount = [$curso, $idInscripcion];
        } else {
            $paramsCount = [$curso];
        }

        $queryCount = $pdo->prepare($sqlCount);
        $queryCount->execute($paramsCount);
        $resCount = $queryCount->fetch(PDO::FETCH_ASSOC);
        $totalInscritos = $resCount ? intval($resCount['total']) : 0;

        if ($totalInscritos >= $cupoMaximo) {
            echo json_encode(['status' => false, 'msg' => 'El curso seleccionado no tiene cupos disponibles. (Inscritos: ' . $totalInscritos . '/' . $cupoMaximo . ')']);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error al verificar cupos/duplicidad: " . $e->getMessage());
        echo json_encode(['status' => false, 'msg' => 'Error al verificar disponibilidad']);
        exit;
    }

    try {
        // Verificar si ya existe la combinación alumno-curso-turno
        if ($idInscripcion == 0) {
            // Para inserción: verificar si ya existe (excluyendo inactivos)
            $sql = "SELECT inscripcion_id FROM inscripcion WHERE alumno_id = ? AND curso_id = ? AND turno_id = ? AND estatusI != 0";
            $query = $pdo->prepare($sql);
            $query->execute([$alumno, $curso, $turno]);
            $result = $query->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                echo json_encode(['status' => false, 'msg' => 'El alumno ya está inscrito en este curso y turno']);
                exit;
            }

            // Insertar nueva inscripción
            $sql = "INSERT INTO inscripcion (alumno_id, curso_id, turno_id, estatusI) VALUES (?, ?, ?, ?)";
            $query = $pdo->prepare($sql);
            $success = $query->execute([$alumno, $curso, $turno, $status]);

            if ($success) {
                echo json_encode(['status' => true, 'msg' => 'Inscripción creada correctamente']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error al crear la inscripción']);
            }

        } else {
            // Para actualización: verificar si ya existe en otras inscripciones (excluyendo la actual y las inactivas)
            $sql = "SELECT inscripcion_id FROM inscripcion WHERE alumno_id = ? AND curso_id = ? AND turno_id = ? AND estatusI != 0 AND inscripcion_id != ?";
            $query = $pdo->prepare($sql);
            $query->execute([$alumno, $curso, $turno, $idInscripcion]);
            $result = $query->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                echo json_encode(['status' => false, 'msg' => 'El alumno ya está inscrito en este curso y turno']);
                exit;
            }

            // Actualizar inscripción existente
            $sql = "UPDATE inscripcion SET alumno_id = ?, curso_id = ?, turno_id = ?, estatusI = ? WHERE inscripcion_id = ?";
            $query = $pdo->prepare($sql);
            $success = $query->execute([$alumno, $curso, $turno, $status, $idInscripcion]);

            if ($success) {
                echo json_encode(['status' => true, 'msg' => 'Inscripción actualizada correctamente']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error al actualizar la inscripción']);
            }
        }

    } catch (PDOException $e) {
        error_log("Error en base de datos: " . $e->getMessage());
        echo json_encode(['status' => false, 'msg' => 'Error del sistema. Por favor, intente más tarde.']);
    }
} else {
    echo json_encode(['status' => false, 'msg' => 'No se recibieron datos']);
}