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
        $sql = "SELECT r.id_representates FROM representantes r INNER JOIN personas p ON r.id_representates = p.id_persona WHERE p.cedula = ? AND r.id_representates != ?";
        $query = $pdo->prepare($sql);
        $query->execute([$cedula, $idRepresentantes]);

        if ($query->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['status' => false, 'msg' => 'La cédula ya está registrada para otro representante']);
            exit;
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
                $fechaNac = '1970-01-01'; // Default
                $sqlPers = "INSERT INTO personas (id_persona, id_nacionalidades, id_direccion, cedula, nombres, apellidos, sexo, fecha_nacimiento, correo_electronico, telefono) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sqlPers)->execute([$idPersona, $idNacionalidad, $idDireccion, $cedula, $nombre, $apellido, $sexo, $fechaNac, $email, $telefono]);
            }

            // 2. Manejar Representante
            if ($idRepresentantes == 0) {
                $idRepresentantes = $idPersona;
                $sql = "INSERT INTO representantes (id_representates, estatus) VALUES (?, 1)";
                $pdo->prepare($sql)->execute([$idRepresentantes]);
                
                $pdo->prepare("UPDATE personas SET representantes_id = ? WHERE id_persona = ?")->execute([$idRepresentantes, $idPersona]);
                $msg = 'Representante creado correctamente';
            } else {
                $msg = 'Representante actualizado correctamente';
            }
            
            $pdo->commit();
            $success = true;
        } catch (Exception $ex) {
            $pdo->rollBack();
            $success = false;
            error_log("Error al guardar representante: " . $ex->getMessage());
        }

        if ($success) {
            
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