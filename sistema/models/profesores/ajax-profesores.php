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
        $sqlUpdateStatus = "UPDATE profesor SET es_director = 2 WHERE es_director = 1 AND director_fecha_fin IS NOT NULL AND director_fecha_fin < ?";
        $queryUpdate = $pdo->prepare($sqlUpdateStatus);
        $queryUpdate->execute([$fechaActual]);
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
        $sql = "SELECT profesor_id FROM profesor WHERE cedula = ? AND profesor_id != ?";
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
                $sqlGetStatus = "SELECT estatus FROM profesor WHERE profesor_id = ?";
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
            $sql_directores = "SELECT nombre, apellido, director_fecha_inicio, director_fecha_fin FROM profesor 
                               WHERE es_director IN (1, 2) AND profesor_id != ?";
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
        // Determinar si es inserción o actualización
        if ($idProfesor == 0) {
            $sql = "INSERT INTO profesor (id_nacionalidades, nombre, apellido, sexo, id_estado, id_ciudad, id_municipio, id_parroquia, cedula, telefono, correo, estatus, es_director, director_fecha_inicio, director_fecha_fin) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)"; // Siempre estatus = 1 para nuevos
            $params = [$idNacionalidad, $nombre, $apellido, $sexo, $idEstado, $idCiudad, $idMunicipio, $idParroquia, $cedula, $telefono, $email, $es_director, $director_fecha_inicio, $director_fecha_fin];
            $msg = 'Profesor creado correctamente';
        } else {
            $sql = "UPDATE profesor SET id_nacionalidades = ?, nombre = ?, apellido = ?, sexo = ?, id_estado = ?, id_ciudad = ?, id_municipio = ?, id_parroquia = ?, cedula = ?, 
                    telefono = ?, correo = ?, es_director = ?, director_fecha_inicio = ?, director_fecha_fin = ?, estatus = ? WHERE profesor_id = ?";
            $params = [$idNacionalidad, $nombre, $apellido, $sexo, $idEstado, $idCiudad, $idMunicipio, $idParroquia, $cedula, $telefono, $email, $es_director, $director_fecha_inicio, $director_fecha_fin, $estatus, $idProfesor];
            $msg = 'Profesor actualizado correctamente';
        }

        // Ejecutar la consulta
        $query = $pdo->prepare($sql);
        $success = $query->execute($params);

        if ($success) {
            if ($idProfesor == 0) {
                // For insertion, get the last inserted ID
                $idProfesor = $pdo->lastInsertId();
            } else {
                // Sincronización con Representantes (por Cédula)
                // Si se actualiza el profesor, buscar si existe un representante con la misma cédula y actualizarlo
                try {
                    $sqlRep = "SELECT representantes_id FROM representantes WHERE cedula = ?";
                    $queryRep = $pdo->prepare($sqlRep);
                    $queryRep->execute([$cedula]);
                    $representante = $queryRep->fetch(PDO::FETCH_ASSOC);

                    if ($representante) {
                        // Actualizar TODOS los datos personales coincidentes en el representante
                        $sqlUpdateRep = "UPDATE representantes SET 
                                        id_nacionalidades = ?,
                                        nombre = ?, 
                                        apellido = ?, 
                                        sexo = ?,
                                        id_estado = ?,
                                        id_ciudad = ?,
                                        id_municipio = ?,
                                        id_parroquia = ?,
                                        telefono = ?, 
                                        correo = ? 
                                        WHERE representantes_id = ?";
                        $queryUpdateRep = $pdo->prepare($sqlUpdateRep);
                        $queryUpdateRep->execute([
                            $idNacionalidad, 
                            $nombre, 
                            $apellido, 
                            $sexo,
                            $idEstado,
                            $idCiudad,
                            $idMunicipio,
                            $idParroquia,
                            $telefono, 
                            $email, 
                            $representante['representantes_id']
                        ]);
                    }
                } catch (Exception $e) {
                    error_log("Error sincronizando representante: " . $e->getMessage());
                }

                // Sincronización con Usuarios (por ID de Profesor)
                // Buscar si este profesor tiene un usuario asociado
                try {
                    $sqlUser = "SELECT user_id FROM usuarios WHERE profesor_id = ?";
                    $queryUser = $pdo->prepare($sqlUser);
                    $queryUser->execute([$idProfesor]);
                    $usuario = $queryUser->fetch(PDO::FETCH_ASSOC);

                    if ($usuario) {
                        // Actualizar nombre del usuario
                        $nombreCompleto = $nombre . ' ' . $apellido;
                        $sqlUpdateUser = "UPDATE usuarios SET nombre = ? WHERE user_id = ?";
                        $queryUpdateUser = $pdo->prepare($sqlUpdateUser);
                        $queryUpdateUser->execute([$nombreCompleto, $usuario['user_id']]);

                        // Actualizar variable de sesión si el usuario actualizado es el actual
                        if (isset($_SESSION['idUser']) && $_SESSION['idUser'] == $usuario['user_id']) {
                            $_SESSION['nombre'] = $nombreCompleto;
                            // Add flag to response to update UI
                            $new_user_name_for_ui = $nombreCompleto;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error sincronizando usuario: " . $e->getMessage());
                }
            }
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