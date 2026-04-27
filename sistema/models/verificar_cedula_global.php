<?php
require_once '../includes/config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Obtener datos
$cedula = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
$modulo = isset($_POST['modulo']) ? trim($_POST['modulo']) : ''; // 'alumno', 'profesor', 'representante'
$es_profesor = isset($_POST['es_profesor']) && ($_POST['es_profesor'] === '1' || $_POST['es_profesor'] === 'true' || filter_var($_POST['es_profesor'], FILTER_VALIDATE_BOOLEAN));

if (empty($cedula) || empty($modulo)) {
    echo json_encode(['status' => false, 'msg' => 'Datos incompletos']);
    exit;
}

try {
    $response = ['status' => true, 'existe' => false, 'msg' => '', 'data' => null];

    // 1. Buscar en ALUMNOS
    $sqlAlumno = "SELECT * FROM alumnos WHERE cedula = ?";
    $queryAlumno = $pdo->prepare($sqlAlumno);
    $queryAlumno->execute([$cedula]);
    $alumno = $queryAlumno->fetch(PDO::FETCH_ASSOC);

    // 2. Buscar en PROFESORES
    $sqlProfesor = "SELECT p.*, n.codigo as nacionalidad_codigo 
                    FROM profesor p 
                    INNER JOIN nacionalidades n ON p.id_nacionalidades = n.id 
                    WHERE p.cedula = ?";
    $queryProfesor = $pdo->prepare($sqlProfesor);
    $queryProfesor->execute([$cedula]);
    $profesor = $queryProfesor->fetch(PDO::FETCH_ASSOC);

    // 3. Buscar en REPRESENTANTES
    $sqlRepresentante = "SELECT r.*, n.codigo as nacionalidad_codigo 
                         FROM representantes r 
                         INNER JOIN nacionalidades n ON r.id_nacionalidades = n.id 
                         WHERE r.cedula = ?";
    $queryRepresentante = $pdo->prepare($sqlRepresentante);
    $queryRepresentante->execute([$cedula]);
    $representante = $queryRepresentante->fetch(PDO::FETCH_ASSOC);

    // Lógica de Validación según el Módulo
    if ($modulo == 'alumno') {
        if ($alumno) {
            $response['existe'] = true;
            $response['msg'] = ($alumno['estatus'] != 1) ? 'La cédula pertenece a un Alumno Inactivo.' : 'La cédula ya está registrada como Alumno.';
        } elseif ($profesor) {
            $response['existe'] = true;
            $response['msg'] = ($profesor['estatus'] != 1) ? 'La cédula pertenece a un Profesor Inactivo.' : 'La cédula ya está registrada como Profesor.';
        } elseif ($representante) {
            $response['existe'] = true;
            $response['msg'] = ($representante['estatus'] != 1) ? 'La cédula pertenece a un Representante Inactivo.' : 'La cédula ya está registrada como Representante.';
        }
    } elseif ($modulo == 'profesor') {
        if ($profesor) {
            $response['existe'] = true;
            $response['msg'] = ($profesor['estatus'] != 1) ? 'La cédula pertenece a un Profesor Inactivo.' : 'La cédula está registrada como Profesor.';
        } elseif ($alumno) {
            $response['existe'] = true;
            $response['msg'] = ($alumno['estatus'] != 1) ? 'La cédula pertenece a un Alumno Inactivo.' : 'La cédula registrada pertenece a un Alumno.';
        } elseif ($representante) {
            // Encontrado como representante
            if ($representante['estatus'] != 1) {
                 // Si está inactivo, BLOQUEAR y no permitir autocompletar
                $response['existe'] = true; // Se marca como "existe" para que el frontend lo trate como error/bloqueo
                $response['msg'] = 'La cédula pertenece a un Representante Inactivo. No se puede registrar como Profesor.';
            } else {
                 // Si está activo, permitir autocompletar
                $response['existe'] = false;
                $response['autofill'] = true;
                $response['msg'] = 'Cédula autocompletada con cédula de representante registrado';
                $response['data'] = [
                    'nombre' => $representante['nombre'],
                    'apellido' => $representante['apellido'],
                    'telefono' => $representante['telefono'],
                    'correo' => $representante['correo'],
                    'nacionalidad_codigo' => $representante['nacionalidad_codigo'],
                    'id_estado' => $representante['id_estado'],
                    'id_ciudad' => $representante['id_ciudad'],
                    'id_municipio' => $representante['id_municipio'],
                    'id_parroquia' => $representante['id_parroquia'],
                    'estatus' => $representante['estatus'],
                    'sexo' => $representante['sexo']
                ];
            }
        }
    } elseif ($modulo == 'representante') {
        if ($representante) {
            $response['existe'] = true;
            $response['msg'] = ($representante['estatus'] != 1) ? 'La cédula pertenece a un Representante Inactivo.' : 'La cédula está registrada como Representante.';
        } elseif ($alumno) {
            $response['existe'] = true;
            $response['msg'] = ($alumno['estatus'] != 1) ? 'La cédula pertenece a un Alumno Inactivo.' : 'La cédula registrada pertenece a un Alumno.';
        } elseif ($profesor) {
             // Encontrado como profesor
             if ($profesor['estatus'] != 1) {
                 // Si está inactivo, BLOQUEAR
                $response['existe'] = true;
                $response['msg'] = 'La cédula pertenece a un Profesor Inactivo. No se puede registrar como Representante.';
            } else {
                // Si está activo, permitir autocompletar
                $response['existe'] = false;
                $response['autofill'] = true;
                $response['msg'] = 'Cédula autocompletada con cédula de profesor registrado';
                $response['data'] = [
                    'nombre' => $profesor['nombre'],
                    'apellido' => $profesor['apellido'],
                    'telefono' => $profesor['telefono'],
                    'correo' => $profesor['correo'],
                    'nacionalidad_codigo' => $profesor['nacionalidad_codigo'],
                    'id_estado' => $profesor['id_estado'],
                    'id_ciudad' => $profesor['id_ciudad'],
                    'id_municipio' => $profesor['id_municipio'],
                    'id_parroquia' => $profesor['id_parroquia'],
                    'estatus' => $profesor['estatus'],
                    'sexo' => $profesor['sexo']
                ];
            }
        }
    }

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Error en verificar_cedula_global.php: " . $e->getMessage());
    echo json_encode(['status' => false, 'msg' => 'Error en el servidor']);
}
