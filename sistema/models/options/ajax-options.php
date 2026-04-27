<?php
require_once '../../includes/config.php';
error_reporting(0);

if (!empty($_POST)) {
    if ($_POST['action'] == 'getEstados') {
        $sql = "SELECT * FROM estados ORDER BY estado ASC";
        $query = $pdo->prepare($sql);
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
    }

    if ($_POST['action'] == 'getCiudades') {
        $idEstado = intval($_POST['id_estado']);
        $sql = "SELECT * FROM ciudades WHERE id_estado = ? ORDER BY ciudad ASC";
        $query = $pdo->prepare($sql);
        $query->execute([$idEstado]);
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
    }

    if ($_POST['action'] == 'getMunicipios') {
        $idEstado = intval($_POST['id_estado']);
        $sql = "SELECT * FROM municipios WHERE id_estado = ? ORDER BY municipio ASC";
        $query = $pdo->prepare($sql);
        $query->execute([$idEstado]);
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
    }

    if ($_POST['action'] == 'getParroquias') {
        $idMunicipio = intval($_POST['id_municipio']);
        $sql = "SELECT * FROM parroquias WHERE id_municipio = ? ORDER BY parroquia ASC";
        $query = $pdo->prepare($sql);
        $query->execute([$idMunicipio]);
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
    }

    if ($_POST['action'] == 'getAlumnos') {
        // Obtener solo alumnos activos
        $sql = "SELECT alumno_id, cedula, nombre, apellido FROM alumnos WHERE estatus != 0 ORDER BY nombre ASC";
        $query = $pdo->prepare($sql);
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
    }

    if ($_POST['action'] == 'getCursos') {
        // Obtener cursos activos
        // Usamos LEFT JOIN para que no filtre si falta turno o el periodo tiene problema
        $sql = "SELECT c.curso_id, c.grado, c.seccion, c.turno_id, t.tipo_turno, pe.periodo_id, pe.anio_inicio, pe.anio_fin 
                FROM curso c
                INNER JOIN periodo_escolar pe ON c.periodo_id = pe.periodo_id
                LEFT JOIN turno t ON c.turno_id = t.turno_id
                WHERE c.estatus = 1
                ORDER BY c.grado ASC, c.seccion ASC";
        $query = $pdo->prepare($sql);
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
    }

    if ($_POST['action'] == 'getAlumnoInfo') {
        // Obtener información detallada para el modal (representante, etc)
        // Usando la lógica de edit_alumnos.php que es la correcta (tabla intermedia alumno_representante)
        $idAlumno = intval($_POST['idAlumno']);
        $sql = "SELECT 
                    a.alumno_id, 
                    a.nombre, 
                    a.apellido, 
                    a.cedula,
                    r.nombre as nombre_rep, 
                    r.apellido as apellido_rep, 
                    r.cedula as cedula_rep,
                    ar.parentesco
                FROM alumnos a
                LEFT JOIN alumno_representante ar ON a.alumno_id = ar.alumno_id AND ar.estatus = 1 AND ar.es_principal = 1
                LEFT JOIN representantes r ON ar.representante_id = r.representantes_id AND r.estatus = 1
                WHERE a.alumno_id = ?";
        $query = $pdo->prepare($sql);
        $query->execute([$idAlumno]);
        $data = $query->fetch(PDO::FETCH_ASSOC);
        echo json_encode($data);
    }
    if ($_POST['action'] == 'getParentescos') {
        $sql = "SELECT * FROM parentesco WHERE activo = 1 ORDER BY parentesco ASC";
        $query = $pdo->prepare($sql);
        $query->execute();
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($data);
    }

    if ($_POST['action'] == 'addParentesco') {
        $parentesco = trim($_POST['nombre'] ?? '');
        if (empty($parentesco)) {
            echo json_encode(['status' => false, 'msg' => 'El nombre del parentesco es obligatorio']);
            exit;
        }

        // Verificar si ya existe
        $sqlCheck = "SELECT id_parentesco FROM parentesco WHERE parentesco = ? AND activo = 1";
        $qCheck = $pdo->prepare($sqlCheck);
        $qCheck->execute([$parentesco]);
        if ($qCheck->fetch()) {
            echo json_encode(['status' => false, 'msg' => 'Este parentesco ya existe']);
            exit;
        }

        try {
            $sqlInsert = "INSERT INTO parentesco (parentesco, activo) VALUES (?, 1)";
            $queryInsert = $pdo->prepare($sqlInsert);
            $queryInsert->execute([$parentesco]);
            $id = $pdo->lastInsertId();

            echo json_encode(['status' => true, 'msg' => 'Parentesco agregado', 'id' => $id, 'nombre' => $parentesco]);
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($_POST['action'] == 'deleteParentesco') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => false, 'msg' => 'ID no válido']);
            exit;
        }

        try {
            $sql = "UPDATE parentesco SET activo = 0 WHERE id_parentesco = ?";
            $query = $pdo->prepare($sql);
            if ($query->execute([$id])) {
                echo json_encode(['status' => true, 'msg' => 'Parentesco eliminado']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'No se pudo eliminar']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($_POST['action'] == 'reactivateParentesco') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => false, 'msg' => 'ID no válido']);
            exit;
        }

        try {
            $sql = "UPDATE parentesco SET activo = 1 WHERE id_parentesco = ?";
            $query = $pdo->prepare($sql);
            if ($query->execute([$id])) {
                echo json_encode(['status' => true, 'msg' => 'Parentesco reactivado correctamente']);
            } else {
                echo json_encode(['status' => false, 'msg' => 'No se pudo reactivar']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>