<?php
// Archivo para procesar las operaciones CRUD de representantes (Crear y Actualizar)
// Este archivo maneja las solicitudes AJAX para crear nuevos representantes y actualizar existentes

// Incluir archivo de configuración de base de datos
require_once '../../includes/config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar que la solicitud sea POST (por seguridad)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Código de error: Método no permitido
    echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
    exit; // Terminar ejecución
}

// Validar y procesar datos recibidos del formulario
if (!empty($_POST)) {
    $errors = []; // Array para almacenar errores de validación

    // Sanitizar y validar campos del formulario
    $idRepresentantes = isset($_POST['idRepresentantes']) ? intval($_POST['idRepresentantes']) : 0; // ID del representante (0 = nuevo, >0 = actualizar)
    $nacionalidadCodigo = trim($_POST['listNacionalidadRepresentante'] ?? ''); // Código de nacionalidad (V, E, P)
    $nombre = trim($_POST['txtNombre'] ?? ''); // Nombre del representante (eliminar espacios)
    $apellido = trim($_POST['txtApellido'] ?? ''); // Apellido del representante (eliminar espacios)
    $sexo = trim($_POST['listSexo'] ?? ''); // Sexo (M/F)
    $idEstado = isset($_POST['listEstado']) ? intval($_POST['listEstado']) : null;
    $idCiudad = isset($_POST['listCiudad']) ? intval($_POST['listCiudad']) : null;
    $idMunicipio = isset($_POST['listMunicipio']) ? intval($_POST['listMunicipio']) : null;
    $idParroquia = isset($_POST['listParroquia']) ? intval($_POST['listParroquia']) : null;
    $cedula = trim($_POST['cedula'] ?? ''); // Cédula del representante (eliminar espacios)
    $telefono = $_POST['telefono'] ?? ''; // Teléfono del representante
    $email = trim($_POST['email'] ?? ''); // Correo electrónico (eliminar espacios)

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

    // Validaciones de campos obligatorios y formato
    if (empty($nombre))
        $errors[] = 'El nombre es obligatorio'; // Verificar que el nombre no esté vacío
    if (empty($apellido))
        $errors[] = 'El apellido es obligatorio'; // Verificar que el apellido no esté vacío
 // Verificar que la dirección no esté vacía

    // Validación de sexo
    if (empty($sexo) || ($sexo != 'M' && $sexo != 'F')) {
        $errors[] = 'El sexo es obligatorio y debe ser M o F';
    }
    if (empty($cedula) || !preg_match('/^[0-9]{7,10}$/', $cedula))
        $errors[] = 'La cédula debe tener entre 7 y 10 dígitos'; // Validar formato de cédula
    if (empty($telefono) || !preg_match('/^[0-9]{7,15}$/', $telefono))
        $errors[] = 'El teléfono debe contener solo números (7-15 dígitos)'; // Validar formato de teléfono
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'El email no es válido'; // Validar formato de email

    // Si hay errores de validación, retornarlos al cliente
    if (!empty($errors)) {
        echo json_encode(['status' => false, 'msg' => 'Errores de validación', 'errors' => $errors]); // Enviar errores en formato JSON
        exit; // Terminar ejecución
    }

    try {
        // Verificar si la cédula ya existe en la base de datos (excepto para el representante actual)
        $sql = "SELECT representantes_id FROM representantes WHERE cedula = ? AND representantes_id != ?"; // Consulta para verificar cédula duplicada
        $query = $pdo->prepare($sql); // Preparar consulta para evitar SQL injection
        $query->execute([$cedula, $idRepresentantes]); // Ejecutar consulta con parámetros

        if ($query->fetch(PDO::FETCH_ASSOC)) { // Si se encuentra un registro con la misma cédula
            echo json_encode(['status' => false, 'msg' => 'La cédula ya está registrada']); // Enviar error
            exit; // Terminar ejecución
        }

        // Determinar si es inserción (nuevo) o actualización (existente)
        if ($idRepresentantes == 0) { // Si ID es 0, es un nuevo representante
            $sql = "INSERT INTO representantes (id_nacionalidades, nombre, apellido, sexo, id_estado, id_ciudad, id_municipio, id_parroquia, cedula, telefono, correo, estatus) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"; // Consulta INSERT siempre con estatus = 1 (Activo)
            $params = [$idNacionalidad, $nombre, $apellido, $sexo, $idEstado, $idCiudad, $idMunicipio, $idParroquia, $cedula, $telefono, $email]; // Parámetros para INSERT
            $msg = 'Representante creado correctamente'; // Mensaje de éxito para nuevo registro
        } else { // Si ID > 0, es una actualización
            $sql = "UPDATE representantes SET id_nacionalidades = ?, nombre = ?, apellido = ?, sexo = ?, id_estado = ?, id_ciudad = ?, id_municipio = ?, id_parroquia = ?, cedula = ?, 
                    telefono = ?, correo = ? WHERE representantes_id = ?"; // Consulta UPDATE - NO modifica estatus
            $params = [$idNacionalidad, $nombre, $apellido, $sexo, $idEstado, $idCiudad, $idMunicipio, $idParroquia, $cedula, $telefono, $email, $idRepresentantes]; // Parámetros para UPDATE
            $msg = 'Representante actualizado correctamente'; // Mensaje de éxito para actualización
        }

        // Ejecutar la consulta preparada
        $query = $pdo->prepare($sql); // Preparar consulta
        $success = $query->execute($params); // Ejecutar consulta con parámetros

        if ($success) { // Si la consulta se ejecutó correctamente
            
            // SINCRONIZACIÓN CON PROFESOR: Si es actualización y existe un profesor con la misma cédula, sincronizar datos
            if ($idRepresentantes > 0) {
                try {
                    // Verificar si existe un profesor con la misma cédula
                    $sqlCheckProf = "SELECT profesor_id FROM profesor WHERE cedula = ?";
                    $queryCheckProf = $pdo->prepare($sqlCheckProf);
                    $queryCheckProf->execute([$cedula]);
                    $profesorExistente = $queryCheckProf->fetch(PDO::FETCH_ASSOC);
                    
                    if ($profesorExistente) {
                        // Sincronizar datos del representante al profesor (SIN modificar estatus del profesor)
                        $sqlSyncProf = "UPDATE profesor SET 
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
                                        WHERE cedula = ?";
                        $paramsSyncProf = [$idNacionalidad, $nombre, $apellido, $sexo, $idEstado, $idCiudad, $idMunicipio, $idParroquia, $telefono, $email, $cedula];
                        $querySyncProf = $pdo->prepare($sqlSyncProf);
                        $querySyncProf->execute($paramsSyncProf);
                        
                        error_log("Sincronización representante->profesor exitosa para cédula: " . $cedula);

                        // Sincronización con Usuarios (Vinculado al Profesor)
                        try {
                            // Buscar si este profesor tiene un usuario asociado
                            $sqlUser = "SELECT user_id FROM usuarios WHERE profesor_id = ?";
                            $queryUser = $pdo->prepare($sqlUser);
                            $queryUser->execute([$profesorExistente['profesor_id']]);
                            $usuario = $queryUser->fetch(PDO::FETCH_ASSOC);

                            if ($usuario) {
                                // Actualizar nombre del usuario
                                $nombreCompleto = $nombre . ' ' . $apellido;
                                $sqlUpdateUser = "UPDATE usuarios SET nombre = ? WHERE user_id = ?";
                                $queryUpdateUser = $pdo->prepare($sqlUpdateUser);
                                $queryUpdateUser->execute([$nombreCompleto, $usuario['user_id']]);
                                error_log("Sincronización representante->usuario exitosa para user_id: " . $usuario['user_id']);

                                // Actualizar sesión si es el usuario actual
                                if (isset($_SESSION['idUser']) && $_SESSION['idUser'] == $usuario['user_id']) {
                                    $_SESSION['nombre'] = $nombreCompleto;
                                    $new_user_name_for_ui = $nombreCompleto;
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Error sincronizando usuario desde representante: " . $e->getMessage());
                        }
                    }
                } catch (PDOException $e) {
                    // Log del error pero no afecta la operación principal
                    error_log("Error en sincronización representante->profesor: " . $e->getMessage());
                }
            }
            
            $response = ['status' => true, 'msg' => $msg];
            if (isset($new_user_name_for_ui)) {
                $response['new_user_name'] = $new_user_name_for_ui;
            }
            echo json_encode($response);
        } else { // Si hubo error en la ejecución
            echo json_encode(['status' => false, 'msg' => 'Error al guardar en la base de datos']); // Enviar error
        }

    } catch (PDOException $e) { // Capturar errores de base de datos
        error_log("Error en base de datos: " . $e->getMessage()); // Registrar error en log
        echo json_encode(['status' => false, 'msg' => 'Error del sistema. Por favor, intente más tarde.']); // Enviar error genérico al cliente
    }
} else { // Si no se recibieron datos POST
    echo json_encode(['status' => false, 'msg' => 'No se recibieron datos']); // Enviar error
}