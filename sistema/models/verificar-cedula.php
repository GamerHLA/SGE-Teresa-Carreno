<?php
require_once '../includes/config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Obtener datos
$cedula = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
$nacionalidadCodigo = isset($_POST['nacionalidad']) ? trim($_POST['nacionalidad']) : '';
$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : ''; // 'profesor' o 'representante'

// Solo validamos que exista cédula y tipo
if (empty($cedula) || empty($tipo)) {
    echo json_encode(['status' => false, 'msg' => 'Datos incompletos']);
    exit;
}

try {
    // Buscar en la tabla contraria SOLO por número de cédula (ignorando nacionalidad)
    if ($tipo == 'profesor') {
        // Si estamos en el formulario de profesor, buscamos en REPRESENTANTES usando personas
        $sql = "SELECT r.*, p.nombres AS nombre, p.apellidos AS apellido, p.telefono, p.correo_electronico AS correo, 
                       p.fecha_nacimiento AS fecha_nac, p.sexo, p.cedula, n.codigo as nacionalidad_codigo 
                FROM personas p 
                INNER JOIN representantes r ON p.representantes_id = r.id_representates 
                LEFT JOIN nacionalidades n ON p.id_nacionalidades = n.id 
                WHERE p.cedula = ? AND r.estatus != 0";
        $query = $pdo->prepare($sql);
        $query->execute([$cedula]);
        $datosPersona = $query->fetch(PDO::FETCH_ASSOC);
        
    } else if ($tipo == 'representante') {
        // Si estamos en el formulario de representante, buscamos en PROFESORES usando personas
        $sql = "SELECT pr.*, p.nombres AS nombre, p.apellidos AS apellido, p.telefono, p.correo_electronico AS correo, 
                       p.fecha_nacimiento AS fecha_nac, p.sexo, p.cedula, n.codigo as nacionalidad_codigo 
                FROM personas p 
                INNER JOIN profesores pr ON p.profesores_id = pr.profesor_id 
                LEFT JOIN nacionalidades n ON p.id_nacionalidades = n.id 
                WHERE p.cedula = ? AND pr.status != 0";
        $query = $pdo->prepare($sql);
        $query->execute([$cedula]);
        $datosPersona = $query->fetch(PDO::FETCH_ASSOC);
    }

    if ($datosPersona) {
        // Preparamos los datos comunes para devolver
        $response = [
            'status' => true,
            'existe' => true,
            'datos' => [
                'id' => isset($datosPersona['representantes_id']) ? $datosPersona['representantes_id'] : (isset($datosPersona['profesor_id']) ? $datosPersona['profesor_id'] : null),
                'nacionalidad_codigo' => $datosPersona['nacionalidad_codigo'], // Usamos el código de la BD
                'cedula' => $datosPersona['cedula'],
                'nombre' => $datosPersona['nombre'],
                'apellido' => $datosPersona['apellido'],
                'direccion' => $datosPersona['direccion'],
                'telefono' => $datosPersona['telefono'],
                'correo' => $datosPersona['correo'],
                'id_estado' => $datosPersona['id_estado'],
                'id_ciudad' => $datosPersona['id_ciudad'],
                'id_municipio' => $datosPersona['id_municipio'],
                'id_parroquia' => $datosPersona['id_parroquia'],
                'estatus' => $datosPersona['estatus'],
                // Campos específicos que podrían ser útiles si existen
                'nivel_est' => isset($datosPersona['nivel_est']) ? $datosPersona['nivel_est'] : '',
                'parentesco' => isset($datosPersona['parentesco']) ? $datosPersona['parentesco'] : ''
            ]
        ];
        echo json_encode($response);
    } else {
        echo json_encode(['status' => true, 'existe' => false]);
    }

} catch (PDOException $e) {
    error_log("Error en verificar-cedula.php: " . $e->getMessage());
    echo json_encode(['status' => false, 'msg' => 'Error en el servidor']);
}
