<?php
require_once '../includes/config.php';

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die("Acceso no autorizado");
}

try {
    $response = [
        'vacunas' => [],
        'enfermedades' => [],
        'discapacidades' => [],
        'parentesco' => [],
        'niveles' => [],
        'especialidades' => []
    ];

    // Vacunas
    $sqlVacunas = "SELECT id_vacuna_infantil as id, nombre FROM vacunas_infantiles WHERE activo = 0 ORDER BY nombre ASC";
    $qVacunas = $pdo->prepare($sqlVacunas);
    $qVacunas->execute();
    $response['vacunas'] = $qVacunas->fetchAll(PDO::FETCH_ASSOC);

    // Enfermedades
    $sqlEnf = "SELECT id_enfermedad_cronica as id, enfermedades as nombre FROM enfermedad WHERE activo = 0 ORDER BY enfermedades ASC";
    $qEnf = $pdo->prepare($sqlEnf);
    $qEnf->execute();
    $response['enfermedades'] = $qEnf->fetchAll(PDO::FETCH_ASSOC);

    // Discapacidades
    $sqlDisc = "SELECT id_discapacidad as id, discapacidad as nombre FROM discapacidad WHERE activo = 0 ORDER BY discapacidad ASC";
    $qDisc = $pdo->prepare($sqlDisc);
    $qDisc->execute();
    $response['discapacidades'] = $qDisc->fetchAll(PDO::FETCH_ASSOC);

    // Parentesco
    $sqlPar = "SELECT id_parentesco as id, parentesco as nombre FROM parentesco WHERE activo = 0 ORDER BY parentesco ASC";
    $qPar = $pdo->prepare($sqlPar);
    $qPar->execute();
    $response['parentesco'] = $qPar->fetchAll(PDO::FETCH_ASSOC);

    // Niveles de Estudio
    $sqlNiv = "SELECT id, nombre FROM niveles_estudio WHERE estatus = 0 ORDER BY orden ASC";
    $qNiv = $pdo->prepare($sqlNiv);
    $qNiv->execute();
    $response['niveles'] = $qNiv->fetchAll(PDO::FETCH_ASSOC);

    // Especialidades
    $sqlEsp = "SELECT ee.id, ee.nombre, ne.nombre as categoria 
               FROM especialidades_estudio ee 
               INNER JOIN niveles_estudio ne ON ee.nivel_id = ne.id 
               WHERE ee.estatus = 0 
               ORDER BY ne.orden ASC, ee.orden ASC";
    $qEsp = $pdo->prepare($sqlEsp);
    $qEsp->execute();
    $response['especialidades'] = $qEsp->fetchAll(PDO::FETCH_ASSOC);

    // Motivos de Retiro (Solo registros Blacklist: IDs NULL y estatus 0)
    $sqlMotRet = "SELECT observacion, tipo_observacion FROM observaciones 
                  WHERE estatus = 0 
                  AND (tipo_observacion = 'motivo_retiro' OR tipo_observacion = 'inhabilitacion')
                  AND alumno_id IS NULL AND representantes_id IS NULL AND profesor_id IS NULL";
    $qMotRet = $pdo->prepare($sqlMotRet);
    $qMotRet->execute();
    $rowsMotRet = $qMotRet->fetchAll(PDO::FETCH_ASSOC);
    
    $motivosRetiro = [];
    $seenRet = [];
    foreach ($rowsMotRet as $row) {
        $text = trim($row['observacion']);
        if (!empty($text) && !in_array(strtolower($text), $seenRet)) {
            $seenRet[] = strtolower($text);
            $motivosRetiro[] = ['nombre' => $text, 'tipo' => $row['tipo_observacion']];
        }
    }
    $response['motivos_retiro'] = $motivosRetiro;

    // Motivos de Asistencia (Solo registros Blacklist: IDs NULL y estatus 0)
    $sqlMotAsis = "SELECT observacion as nombre, tipo_observacion as tipo FROM observaciones 
                   WHERE estatus = 0 AND tipo_observacion = 'motivo_justificativo'
                   AND alumno_id IS NULL AND representantes_id IS NULL AND profesor_id IS NULL";

    $qMotAsis = $pdo->prepare($sqlMotAsis);
    $qMotAsis->execute();
    $response['motivos_asistencia'] = $qMotAsis->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

?>
