<?php
require 'includes/config.php';
header('Content-Type: text/html; charset=utf-8');
echo "<h2>Diagnóstico de Inscripciones</h2>";

// 1. Verificar grados
try {
    $r = $pdo->query("SELECT id_grado, grado FROM grados WHERE status = 1 ORDER BY grado ASC");
    $grados = $r->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Grados activos (" . count($grados) . "):</h3><pre>" . print_r($grados, true) . "</pre>";
} catch(Exception $e) { echo "<b>ERROR grados: " . $e->getMessage() . "</b><br>"; }

// 2. Verificar secciones
try {
    $r = $pdo->query("SELECT id_seccion, seccion FROM secciones WHERE estatus = 1");
    $secciones = $r->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Secciones activas (" . count($secciones) . "):</h3><pre>" . print_r($secciones, true) . "</pre>";
} catch(Exception $e) { echo "<b>ERROR secciones: " . $e->getMessage() . "</b><br>"; }

// 3. Verificar turnos
try {
    $r = $pdo->query("SELECT turno_id, tipo_turno FROM turno");
    $turnos = $r->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Turnos (" . count($turnos) . "):</h3><pre>" . print_r($turnos, true) . "</pre>";
} catch(Exception $e) { echo "<b>ERROR turnos: " . $e->getMessage() . "</b><br>"; }

// 4. Verificar profesores activos
try {
    $r = $pdo->query("SELECT pr.profesor_id, p.nombres, p.apellidos FROM profesores pr INNER JOIN personas p ON p.profesores_id = pr.profesor_id WHERE pr.status = 1");
    $profs = $r->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Profesores activos (" . count($profs) . "):</h3><pre>" . print_r($profs, true) . "</pre>";
} catch(Exception $e) { echo "<b>ERROR profesores: " . $e->getMessage() . "</b><br>"; }

// 5. Verificar periodo activo
try {
    $r = $pdo->query("SELECT * FROM periodo_escolar WHERE estatus = 1 LIMIT 1");
    $pe = $r->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Periodo Activo:</h3><pre>" . print_r($pe, true) . "</pre>";
    if (!$pe) echo "<b style='color:red'>⚠️ NO HAY PERIODO ACTIVO - Esto bloquea todas las inscripciones</b><br>";
} catch(Exception $e) { echo "<b>ERROR periodo: " . $e->getMessage() . "</b><br>"; }

// 6. Simular INSERT de inscripcion con datos de prueba
echo "<h3>Prueba de INSERT (simulada):</h3>";
try {
    // Obtener IDs de prueba
    $alumno_id = $pdo->query("SELECT id_alumnos FROM alumnos WHERE estatus = 1 LIMIT 1")->fetchColumn();
    $grado_id = $pdo->query("SELECT id_grado FROM grados WHERE status = 1 LIMIT 1")->fetchColumn();
    $seccion_id = $pdo->query("SELECT id_seccion FROM secciones WHERE estatus = 1 LIMIT 1")->fetchColumn();
    $turno_id = $pdo->query("SELECT turno_id FROM turno LIMIT 1")->fetchColumn();
    $profesor_id = $pdo->query("SELECT profesor_id FROM profesores WHERE status = 1 LIMIT 1")->fetchColumn();
    $periodo_id = $pdo->query("SELECT periodo_id FROM periodo_escolar WHERE estatus = 1 LIMIT 1")->fetchColumn();
    $nuevo_id = $pdo->query("SELECT COALESCE(MAX(inscripcion_id), 0) + 1 AS nid FROM inscripcion")->fetchColumn();
    
    echo "alumno_id: $alumno_id <br>";
    echo "grado_id: $grado_id <br>";
    echo "seccion_id: $seccion_id <br>";
    echo "turno_id: $turno_id <br>";
    echo "profesor_id: $profesor_id <br>";
    echo "periodo_id: $periodo_id <br>";
    echo "nuevo inscripcion_id: $nuevo_id <br>";
    
    if (!$alumno_id || !$grado_id || !$seccion_id || !$turno_id || !$periodo_id) {
        echo "<b style='color:red'>⚠️ Falta alguno de los IDs requeridos (ver arriba)</b>";
    } else {
        echo "<b style='color:green'>✓ Todos los IDs disponibles para insertar</b>";
    }
} catch(Exception $e) { echo "<b>ERROR en prueba INSERT: " . $e->getMessage() . "</b><br>"; }
