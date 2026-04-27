<?php
require_once '../../includes/config.php';

// Verificar método POST (o GET si es desde link directo, pero mejor POST desde JS)
$response = ['status' => false, 'msg' => ''];

try {
    // 1. Obtener el periodo escolar activo (el más reciente)
    $sqlPeriodo = "SELECT periodo_id FROM periodo_escolar WHERE estatus = 1 ORDER BY periodo_id DESC LIMIT 1";
    $queryPeriodo = $pdo->prepare($sqlPeriodo);
    $queryPeriodo->execute();
    $periodo = $queryPeriodo->fetch(PDO::FETCH_ASSOC);

    if (!$periodo) {
        throw new Exception("No hay un periodo escolar activo.");
    }

    $periodo_id = $periodo['periodo_id'];

    // 1.1 Obtener IDs de Turnos
    $sqlTurnos = "SELECT turno_id, tipo_turno FROM turno";
    $queryTurnos = $pdo->prepare($sqlTurnos);
    $queryTurnos->execute();
    $turnos = $queryTurnos->fetchAll(PDO::FETCH_ASSOC);

    $idManana = 1; // Default fallback
    $idTarde = 2; // Default fallback

    foreach ($turnos as $t) {
        if (stripos($t['tipo_turno'], 'Mañana') !== false) {
            $idManana = $t['turno_id'];
        }
        if (stripos($t['tipo_turno'], 'Tarde') !== false) {
            $idTarde = $t['turno_id'];
        }
    }

    $insertedCount = 0;

    // 2. Definir Grados y Secciones por defecto
    // Ejemplo: 1er Grado A, 1er Grado B ... hasta 6to.
    $gradosLabels = ['1', '2', '3', '4', '5', '6'];
    $seccionesLabels = ['A', 'B', 'C', 'D'];

    // Obtener Mapeo de Grados (nombre => id)
    $stmtG = $pdo->query("SELECT id_grado, grado FROM grados WHERE estatus = 1");
    $gradosMap = [];
    while ($row = $stmtG->fetch(PDO::FETCH_ASSOC)) {
        $gradosMap[$row['grado']] = $row['id_grado'];
    }

    // Obtener Mapeo de Secciones (nombre => id)
    $stmtS = $pdo->query("SELECT id_seccion, seccion FROM seccion WHERE estatus = 1");
    $seccionesMap = [];
    while ($row = $stmtS->fetch(PDO::FETCH_ASSOC)) {
        $seccionesMap[$row['seccion']] = $row['id_seccion'];
    }

    foreach ($gradosLabels as $gradoLabel) {
        $idGrado = $gradosMap[$gradoLabel] ?? 0;
        if ($idGrado == 0)
            continue;

        foreach ($seccionesLabels as $seccionLabel) {
            $idSeccion = $seccionesMap[$seccionLabel] ?? 0;
            if ($idSeccion == 0)
                continue;

            // Determinar turno por defecto
            $turno_id = $idManana;
            if ($seccionLabel == 'C' || $seccionLabel == 'D') {
                $turno_id = $idTarde;
            }

            // 3. Verificar si ya existe
            $sqlCheck = "SELECT count(*) as total FROM curso 
                         WHERE grados_id = ? AND seccion_id = ? AND periodo_id = ? AND estatusC != 0";
            $queryCheck = $pdo->prepare($sqlCheck);
            $queryCheck->execute([$idGrado, $idSeccion, $periodo_id]);
            $exists = $queryCheck->fetch(PDO::FETCH_ASSOC);

            if ($exists['total'] == 0) {
                // 4. Insertar
                // cupo = 0, profesor_id = 0
                $sqlInsert = "INSERT INTO curso (grados_id, seccion_id, cupo, turno_id, profesor_id, periodo_id, estatusC) 
                              VALUES (?, ?, 0, ?, 0, ?, 1)";
                $queryInsert = $pdo->prepare($sqlInsert);
                $queryInsert->execute([$idGrado, $idSeccion, $turno_id, $periodo_id]);
                $insertedCount++;
            }
        }
    }

    $response['status'] = true;
    if ($insertedCount > 0) {
        $response['msg'] = "Se generaron $insertedCount grados/secciones por defecto correctamente (incluyendo secciones C y D).";
    } else {
        $response['msg'] = "Ya existían todos los grados y secciones para este periodo.";
    }

} catch (Exception $e) {
    $response['msg'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
