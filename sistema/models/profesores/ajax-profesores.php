<?php
require_once '../../includes/config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Validar y procesar datos
if (!empty($_POST)) {
    // Actualizar estados de directores vencidos globalmente antes de procesar
    try {
        $fechaActual = date('Y-m-d');
            $sqlUpdateStatus = "UPDATE profesores SET es_director = 2 WHERE es_director = 1 AND director_fecha_fin IS NOT NULL AND director_fecha_fin < ?";
    } catch (Exception $e) {
        // Silently fail or log if needed, operation can continue
        error_log("Error actualizando estados de directores: " . $e->getMessage());
    }

    $errors = [];

    // Sanitizar y validar campos
    $idProfesor = isset($_POST['idProfesor']) ? intval($_POST['idProfesor']) : 0;
    $nacionalidadCodigo = trim($_POST['listNacionalidadProfesor'] ?? ''); // Código de nacionalidad (V, E, P)
    $nombre = trim($_POST['txtNombre'] ?? '');
    $apellido = trim($_POST['txtApellido'] ?? '');
    $sexo = trim($_POST['listSexo'] ?? ''); // Sexo (M/F)
    $idEstado = isset($_POST['listEstado']) ? intval($_POST['listEstado']) : null;
    $idCiudad = isset($_POST['listCiudad']) ? intval($_POST['listCiudad']) : null;
    $idMunicipio = isset($_POST['listMunicipio']) ? intval($_POST['listMunicipio']) : null;
    $idParroquia = isset($_POST['listParroquia']) ? intval($_POST['listParroquia']) : null;
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = $_POST['telefono'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $nivelEst = trim($_POST['nivelEst'] ?? '');
    $estatus = isset($_POST['listStatus']) ? intval($_POST['listStatus']) : 1;
    $es_director = isset($_POST['checkDirector']) && $_POST['checkDirector'] == '1' ? 1 : 0;
    $director_fecha_inicio = !empty($_POST['director_fecha_inicio']) ? $_POST['director_fecha_inicio'] : null;
    $director_fecha_fin = !empty($_POST['director_fecha_fin']) ? $_POST['director_fecha_fin'] : null;

    // Validación de nacionalidad
    $idNacionalidad = null;
    if (empty($nacionalidadCodigo)) {
        $errors[] = 'La nacionalidad es obligatoria';
    } else {
        // Validar que sea una opción válida y obtener el ID
        $opciones_validas = ['V', 'E', 'P'];
        if (!in_array($nacionalidadCodigo, $opciones_validas)) {
            $errors[] = 'La nacionalidad seleccionada no es válida';
        } else {
            // Obtener el ID de la nacionalidad desde la base de datos
            try {
                $sqlNac = "SELECT id FROM nacionalidades WHERE codigo = ?";
                $queryNac = $pdo->prepare($sqlNac);
                $queryNac->execute([$nacionalidadCodigo]);
                $nacionalidad = $queryNac->fetch(PDO::FETCH_ASSOC);
                if ($nacionalidad) {
                    $idNacionalidad = intval($nacionalidad['id']);
                } else {
                    $errors[] = 'No se encontró la nacionalidad en la base de datos';
                }
            } catch (PDOException $e) {
                $errors[] = 'Error al validar la nacionalidad';
            }
        }
    }

    // Validaciones
    if (empty($nombre))
        $errors[] = 'El nombre es obligatorio';
    if (empty($apellido))
        $errors[] = 'El apellido es obligatorio';


    // Validación de sexo
    if (empty($sexo) || ($sexo != 'M' && $sexo != 'F')) {
        $errors[] = 'El sexo es obligatorio y debe ser M o F';
    }

    if (empty($cedula) || !preg_match('/^[0-9]{7,10}$/', $cedula))
        $errors[] = 'La cédula debe tener entre 7 y 10 dígitos';
    if (empty($telefono) || !preg_match('/^[0-9]{7,15}$/', $telefono))
        $errors[] = 'El teléfono debe contener solo números (7-15 dígitos)';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'El email no es válido';

    // Nota: El nivel de estudios ahora se maneja en la tabla profesor_niveles_estudio
    // No se valida ni se guarda en el campo nivel_est de la tabla profesor


    // Si hay errores, retornarlos
    if (!empty($errors)) {
        echo json_encode(['status' => false, 'msg' => 'Errores de validación', 'errors' => $errors]);
        exit;
    }

    try {
        // Verificar si la cédula ya existe (excepto para el profesor actual)
        $sql = "SELECT pr.profesor_id FROM profesores pr 
                INNER JOIN personas p ON p.profesores_id = pr.profesor_id 
                WHERE p.cedula = ? AND pr.profesor_id != ?";
        $query = $pdo->prepare($sql);
        $query->execute([$cedula, $idProfesor]);

        if ($query->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['status' => false, 'msg' => 'La cédula ya está registrada']);
            exit;
        }

        // Verificar si ya existe un director (solo si se está marcando como director)
        if ($es_director == 1) {
            // Obtener el estatus actual del profesor si es UPDATE
            $statusToUse = 1; // Por defecto para INSERT
            if ($idProfesor > 0) {
                $sqlGetStatus = "SELECT status FROM profesores WHERE profesor_id = ?";
                $queryGetStatus = $pdo->prepare($sqlGetStatus);
                $queryGetStatus->execute([$idProfesor]);
                $currentData = $queryGetStatus->fetch(PDO::FETCH_ASSOC);
                if ($currentData) {
                    $statusToUse = $currentData['estatus'];
                }
            }

            if ($statusToUse == 2 && empty($director_fecha_fin)) {
                echo json_encode(['status' => false, 'msg' => 'Si el director pasa a inactivo, debe establecer una fecha de fin']);
                exit;
            }

            // Validar fechas
            if (empty($director_fecha_inicio)) {
                echo json_encode(['status' => false, 'msg' => 'La fecha de inicio es obligatoria para el director']);
                exit;
            }

            $fechaActual = date('Y-m-d');
            if ($director_fecha_inicio > $fechaActual) {
                echo json_encode(['status' => false, 'msg' => 'La fecha de inicio no puede ser posterior a la fecha actual']);
                exit;
            }

            if (!empty($director_fecha_fin) && $director_fecha_fin < $director_fecha_inicio) {
                echo json_encode(['status' => false, 'msg' => 'La fecha de fin no puede ser menor a la fecha de inicio']);
                exit;
            }

            // Determinar valor de es_director (1: Activo, 2: Ex-Director)
            if (!empty($director_fecha_fin) && $director_fecha_fin < $fechaActual) {
                $es_director = 2; // Ex-Director
            } else {
                $es_director = 1; // Director Activo
            }

            // Validar superposición de fechas con otros directores (Activos y Ex-Directores)
            $sql_directores = "SELECT p.nombres AS nombre, p.apellidos AS apellido, pr.director_fecha_inicio, pr.director_fecha_fin 
                               FROM profesores pr 
                               INNER JOIN personas p ON p.profesores_id = pr.profesor_id 
                               WHERE pr.es_director IN (1, 2) AND pr.profesor_id != ?";
            $query_directores = $pdo->prepare($sql_directores);
            $query_directores->execute(array($idProfesor));
            $otros_directores = $query_directores->fetchAll(PDO::FETCH_ASSOC);

            $fechaInicioNew = $director_fecha_inicio;
            $fechaFinNew = !empty($director_fecha_fin) ? $director_fecha_fin : '9999-12-31';

            foreach ($otros_directores as $director) {
                $fechaInicioExist = $director['director_fecha_inicio'];
                $fechaFinExist = !empty($director['director_fecha_fin']) ? $director['director_fecha_fin'] : '9999-12-31';

                // Lógica de superposición: (StartA < EndB) && (EndA > StartB)
                // Esto permite que StartA == EndB (empalme exacto permitido)
                if ($fechaInicioNew < $fechaFinExist && $fechaFinNew > $fechaInicioExist) {
                    echo json_encode(['status' => false, 'msg' => 'El rango de fechas coincide con el director ' . $director['nombre'] . ' ' . $director['apellido'] . ' (' . $director['director_fecha_inicio'] . ' - ' . ($director['director_fecha_fin'] ?? 'Actualidad') . ')']);
                    exit;
                }
            }


        }
        $pdo->beginTransaction();

        try {
            // 1. Manejar la Persona y Dirección
            $idPersona = 0;
            $idDireccion = 0;

            // Buscar si la persona ya existe por cédula
            $sqlCheck = "SELECT id_persona, id_direccion FROM personas WHERE cedula = ?";
            $qCheck = $pdo->prepare($sqlCheck);
            $qCheck->execute([$cedula]);
            $personaExistente = $qCheck->fetch(PDO::FETCH_ASSOC);

            if ($personaExistente) {
                $idPersona = $personaExistente['id_persona'];
                $idDireccion = $personaExistente['id_direccion'];
                
                // Actualizar Persona
                $sqlPersUpdate = "UPDATE personas SET id_nacionalidades = ?, nombres = ?, apellidos = ?, sexo = ?, correo_electronico = ?, telefono = ? WHERE id_persona = ?";
                $pdo->prepare($sqlPersUpdate)->execute([$idNacionalidad, $nombre, $apellido, $sexo, $email, $telefono, $idPersona]);
                
                // Actualizar Direccion
                $sqlDirUpdate = "UPDATE direccion SET id_estados = ?, id_municipios = ?, id_parroquias = ? WHERE id_direccion = ?";
                $pdo->prepare($sqlDirUpdate)->execute([$idEstado, $idMunicipio, $idParroquia, $idDireccion]);
            } else {
                // Generar IDs
                $idDireccion = $pdo->query("SELECT COALESCE(MAX(id_direccion), 0) + 1 FROM direccion")->fetchColumn();
                
                // Insertar Dirección
                $sqlDir = "INSERT INTO direccion (id_direccion, id_estados, id_municipios, id_parroquias, calle, cog_postal) VALUES (?, ?, ?, ?, '', 0)";
                $pdo->prepare($sqlDir)->execute([$idDireccion, $idEstado, $idMunicipio, $idParroquia]);
                
                $idPersona = $pdo->query("SELECT COALESCE(MAX(id_persona), 0) + 1 FROM personas")->fetchColumn();
                
                // Insertar Persona
                $fechaNac = '1970-01-01'; // Valor por defecto si no se pide fecha_nacimiento
                $sqlPers = "INSERT INTO personas (id_persona, id_nacionalidades, id_direccion, cedula, nombres, apellidos, sexo, fecha_nacimiento, correo_electronico, telefono) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sqlPers)->execute([$idPersona, $idNacionalidad, $idDireccion, $cedula, $nombre, $apellido, $sexo, $fechaNac, $email, $telefono]);
            }

            // 2. Manejar Profesor
            if ($idProfesor == 0) {
                $idProfesor = $idPersona;
                $sql = "INSERT INTO profesores (profesor_id, turno_id, status, es_director, director_fecha_inicio, director_fecha_fin) 
                        VALUES (?, 1, ?, ?, ?, ?)"; // turno_id = 1 por defecto
                $params = [$idProfesor, $estatus, $es_director, $director_fecha_inicio, $director_fecha_fin];
                $pdo->prepare($sql)->execute($params);
                
                $pdo->prepare("UPDATE personas SET profesores_id = ? WHERE id_persona = ?")->execute([$idProfesor, $idPersona]);
                $msg = 'Profesor creado correctamente';
            } else {
                $sql = "UPDATE profesores SET status = ?, es_director = ?, director_fecha_inicio = ?, director_fecha_fin = ? WHERE profesor_id = ?";
                $params = [$estatus, $es_director, $director_fecha_inicio, $director_fecha_fin, $idProfesor];
                $pdo->prepare($sql)->execute($params);
                $msg = 'Profesor actualizado correctamente';
            }
            
            $pdo->commit();
            $success = true;
        } catch (Exception $ex) {
            $pdo->rollBack();
            $success = false;
            error_log("Error al guardar profesor: " . $ex->getMessage());
        }

        if ($success) {
            $response = ['status' => true, 'msg' => $msg, 'profesor_id' => intval($idProfesor)];
            if (isset($new_user_name_for_ui)) {
                $response['new_user_name'] = $new_user_name_for_ui;
            }
            echo json_encode($response);
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