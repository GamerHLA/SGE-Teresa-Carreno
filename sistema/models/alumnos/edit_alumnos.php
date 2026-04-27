<?php
require_once '../../includes/config.php'; // Incluye el archivo de configuración de la base de datos
error_reporting(0);

if ($_GET) { // Verifica si se recibió una solicitud GET (parámetros en la URL)
    $idAlumno = $_GET['id']; // Obtiene el ID del alumno desde el parámetro GET 'id' en la URL

    try {
        // Consulta SQL para buscar un alumno específico por su ID
        // Incluye JOIN con alumno_representante y representantes para obtener datos del representante
        // NOTA: Si la tabla alumno_representante no existe, ejecutar: create_alumno_representante_table.sql
        $sql = "SELECT 
                    a.*,
                    r.representantes_id,
                    r.cedula as rep_cedula,
                    ar.parentesco_id,
                    p_rel.parentesco,
                    r2.representantes_id as rep2_id,
                    r2.cedula as rep2_cedula,
                    ar2.parentesco_id as parentesco_id2,
                    p_rel2.parentesco as parentesco2
                FROM alumnos a
                LEFT JOIN alumno_representante ar ON a.alumno_id = ar.alumno_id AND ar.estatus = 1 AND ar.es_principal = 1
                LEFT JOIN representantes r ON ar.representante_id = r.representantes_id AND r.estatus = 1
                LEFT JOIN parentesco p_rel ON ar.parentesco_id = p_rel.id_parentesco
                LEFT JOIN alumno_representante ar2 ON a.alumno_id = ar2.alumno_id AND ar2.estatus = 1 AND ar2.es_principal = 0
                LEFT JOIN representantes r2 ON ar2.representante_id = r2.representantes_id AND r2.estatus = 1
                LEFT JOIN parentesco p_rel2 ON ar2.parentesco_id = p_rel2.id_parentesco
                WHERE a.alumno_id = ?"; // El ? es un marcador de posición para prevenir inyecciones SQL

        $query = $pdo->prepare($sql); // Prepara la consulta SQL para su ejecución segura
        $query->execute(array($idAlumno)); // Ejecuta la consulta pasando el ID del alumno como parámetro
        $data = $query->fetch(PDO::FETCH_ASSOC); // Obtiene un solo registro como array asociativo (clave => valor)

        // Procesar Representante 2
        if ($data && !empty($data['rep2_cedula'])) {
            $rep2 = [
                'cedula' => $data['rep2_cedula'],
                'parentesco_id' => $data['parentesco_id2'], // Usar ID
                'parentesco' => $data['parentesco2'],
                'parentesco_otros' => ''
            ];
            $data['representante2'] = $rep2;
        }
    } catch (PDOException $e) {
        // Si la tabla no existe, ejecutar consulta sin JOIN
        if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Table") !== false) {
            error_log("Tabla alumno_representante no existe. Ejecutar: create_alumno_representante_table.sql");
            // Consulta alternativa sin JOIN
            $sql = "SELECT * FROM alumnos WHERE alumno_id = ?";
            $query = $pdo->prepare($sql);
            $query->execute(array($idAlumno));
            $data = $query->fetch(PDO::FETCH_ASSOC);
        } else {
            error_log("Error en edit_alumnos.php: " . $e->getMessage());
            $data = null;
        }
    }

    if (empty($data)) { // Verifica si no se encontraron datos (array vacío)
        $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados'); // Prepara respuesta de error
    } else { // Si se encontraron datos del alumno
        $arrResponse = array('status' => true, 'data' => $data); // Prepara respuesta exitosa con los datos
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE); // Convierte el array a formato JSON y lo imprime (manteniendo caracteres especiales)
}