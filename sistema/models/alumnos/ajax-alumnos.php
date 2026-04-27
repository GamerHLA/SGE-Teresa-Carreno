<?php
require_once '../../includes/config.php'; // Incluye el archivo de configuración de la base de datos

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Establece código de respuesta HTTP 405 (Método no permitido)
    echo json_encode(['status' => false, 'msg' => 'Método no permitido']); // Retorna error en formato JSON
    exit; // Termina la ejecución del script
}

// Validar y procesar datos
if (!empty($_POST)) {
    $errors = []; // Array para almacenar errores de validación

    // Sanitizar y validar campos del formulario
    $idAlumno = isset($_POST['idAlumno']) ? intval($_POST['idAlumno']) : 0; // Convierte a entero, 0 para nuevo registro
    $nacionalidadCodigo = trim($_POST['listNacionalidadAlumno'] ?? ''); // Código de nacionalidad (V, E, P) - OPCIONAL
    $cedulaAlumno = trim($_POST['cedulaAlumno'] ?? ''); // Cédula del alumno - OPCIONAL
    $cedulaEscolar = trim($_POST['cedulaEscolar'] ?? ''); // Cédula escolar - OPCIONAL
    $cedulaRepresentante = trim($_POST['cedulaRepresentante'] ?? ''); // Cédula del representante - OBLIGATORIA
    $poseeCedula = trim($_POST['poseeCedula'] ?? ''); // Si posee cédula (SI/NO) - OBLIGATORIA
    $nombre = trim($_POST['txtNombre'] ?? ''); // Elimina espacios y asigna valor por defecto
    $apellido = trim($_POST['txtApellido'] ?? ''); // Elimina espacios y asigna valor por defecto
    $fechaNac = trim($_POST['fechaNac'] ?? ''); // Elimina espacios y asigna valor por defecto
    $sexo = trim($_POST['listSexo'] ?? ''); // Sexo (M/F)
    $edad = 0;
    if (!empty($fechaNac)) {
        try {
            $fechaNacObj = new DateTime($fechaNac);
            $hoy = new DateTime();
            $edad = $hoy->diff($fechaNacObj)->y;
        } catch (Exception $e) {
            $edad = 0;
        }
    }
    $idEstado = isset($_POST['listEstado']) ? intval($_POST['listEstado']) : null;
    $idCiudad = isset($_POST['listCiudad']) ? intval($_POST['listCiudad']) : null;
    $idMunicipio = isset($_POST['listMunicipio']) ? intval($_POST['listMunicipio']) : null;
    $idParroquia = isset($_POST['listParroquia']) ? intval($_POST['listParroquia']) : null;
    $parentescoId = isset($_POST['listParentesco']) ? intval($_POST['listParentesco']) : 0; // ID Parentesco

    // Validación de campos obligatorios
    if (empty($cedulaRepresentante)) {
        $errors[] = 'La cédula del representante es obligatoria';
    } elseif (!preg_match('/^[0-9]{7,10}$/', $cedulaRepresentante)) {
        $errors[] = 'La cédula del representante debe tener entre 7 y 10 dígitos numéricos';
    }

    if (empty($poseeCedula) || ($poseeCedula !== 'SI' && $poseeCedula !== 'NO')) {
        $errors[] = 'Debe seleccionar si el alumno posee cédula';
    }

    // Validación de nacionalidad (OPCIONAL - solo si tiene valor)
    $idNacionalidad = null;
    if (!empty($nacionalidadCodigo)) {
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

    // Validación de cédula del alumno (OPCIONAL - solo validar si tiene valor)
    // Si posee cédula, puede tener cédula del alumno (opcional); si no, debe tener cédula escolar
    $cedula = null; // Variable que se usará para guardar en BD (null si no hay cédula)
    if ($poseeCedula === 'SI') {
        // Si dice que tiene cédula, validar que tenga cédula del alumno (opcional pero si tiene valor, validar formato)
        if (!empty($cedulaAlumno)) {
            if (!preg_match('/^[0-9]{7,10}$/', $cedulaAlumno)) {
                $errors[] = 'La cédula del alumno debe tener entre 7 y 10 dígitos numéricos';
            } else {
                $cedula = $cedulaAlumno;
            }
        }
        // Si no tiene cédula del alumno pero dice que posee cédula, no es error (es opcional)
        // $cedula permanece como null
    } else if ($poseeCedula === 'NO') {
        // Si no posee cédula, usar cédula escolar
        if (empty($cedulaEscolar)) {
            $errors[] = 'La cédula escolar es obligatoria cuando el alumno no posee cédula';
        } else {
            $cedula = $cedulaEscolar;
        }
    }

    // Validaciones de campos obligatorios y formatos MEJORADAS
    if (empty($nombre))
        $errors[] = 'El nombre es obligatorio';
    if (empty($apellido))
        $errors[] = 'El apellido es obligatorio';

    if ($edad < 4 || $edad > 15)
        $errors[] = 'La edad debe ser entre 4 y 15 años';



    // Validación de sexo
    if (empty($sexo) || ($sexo != 'M' && $sexo != 'F')) {
        $errors[] = 'El sexo es obligatorio y debe ser M o F';
    }

    // Validación de fecha de nacimiento mejorada
    if (empty($fechaNac)) {
        $errors[] = 'La fecha de nacimiento es obligatoria';
    } else {
        $fecha = DateTime::createFromFormat('Y-m-d', $fechaNac);
        if (!$fecha || $fecha->format('Y-m-d') !== $fechaNac) {
            $errors[] = 'La fecha de nacimiento no es válida. Use el formato AAAA-MM-DD';
        }
    }

    // Validación de parentesco
    if ($parentescoId <= 0) {
        $errors[] = 'El parentesco es obligatorio';
    }

    // Si hay errores, retornarlos CON MEJOR INFORMACIÓN
    if (!empty($errors)) {
        error_log("Errores de validación: " . print_r($errors, true)); // Log para depuración
        echo json_encode([
            'status' => false,
            'msg' => 'Se encontraron ' . count($errors) . ' error(es) de validación',
            'errors' => $errors
        ]);
        exit;
    }

    try {
        // Verificar si la cédula ya existe (excepto para el alumno actual) - solo si hay cédula
        if (!empty($cedula) && $cedula !== null) {
            $sql = "SELECT alumno_id FROM alumnos WHERE cedula = ? AND alumno_id != ? AND cedula != ''";
            $query = $pdo->prepare($sql);
            $query->execute([$cedula, $idAlumno]);

            if ($query->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode(['status' => false, 'msg' => 'La cédula ya está registrada']);
                exit;
            }
        }

        // Obtener el ID del representante por su cédula
        $sqlRep = "SELECT representantes_id FROM representantes WHERE cedula = ? AND estatus = 1";
        $queryRep = $pdo->prepare($sqlRep);
        $queryRep->execute([$cedulaRepresentante]);
        $representante = $queryRep->fetch(PDO::FETCH_ASSOC);

        if (!$representante) {
            echo json_encode(['status' => false, 'msg' => 'El representante no existe o está inactivo']);
            exit;
        }

        $representanteId = intval($representante['representantes_id']);

        // Determinar si es inserción o actualización
        // Convertir null a cadena vacía para campos que podrían no aceptar NULL
        $cedulaFinal = ($cedula === null) ? '' : $cedula;

        // Iniciar transacción
        $pdo->beginTransaction();

        if ($idAlumno == 0) {
            // Consulta INSERT para nuevo alumno - Siempre con estatus = 1 (Activo)
            $sql = "INSERT INTO alumnos (id_nacionalidades, cedula, nombre, apellido, fecha_nac, sexo, edad, id_estado, id_ciudad, id_municipio, id_parroquia, estatus) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $params = [$idNacionalidad, $cedulaFinal, $nombre, $apellido, $fechaNac, $sexo, $edad, $idEstado, $idCiudad, $idMunicipio, $idParroquia];
            $query = $pdo->prepare($sql);
            $success = $query->execute($params);

            if ($success) {
                $idAlumno = $pdo->lastInsertId(); // Obtener el ID del alumno insertado
                $msg = 'Alumno creado correctamente';
            } else {
                throw new Exception('Error al insertar el alumno');
            }
        } else {
            // Consulta UPDATE para alumno existente - NO modifica estatus (se maneja con botones activar/desactivar)
            $sql = "UPDATE alumnos SET id_nacionalidades = ?, cedula = ?, nombre = ?, apellido = ?, 
                    fecha_nac = ?, sexo = ?, edad = ?, id_estado = ?, id_ciudad = ?, id_municipio = ?, id_parroquia = ? WHERE alumno_id = ?";
            $params = [$idNacionalidad, $cedulaFinal, $nombre, $apellido, $fechaNac, $sexo, $edad, $idEstado, $idCiudad, $idMunicipio, $idParroquia, $idAlumno];
            $query = $pdo->prepare($sql);
            $success = $query->execute($params);

            if (!$success) {
                throw new Exception('Error al actualizar el alumno');
            }
            $msg = 'Alumno actualizado correctamente';
        }

        // Guardar o actualizar la relación alumno-representante
        // NOTA: Requiere que exista la tabla alumno_representante
        // Si no existe, ejecutar el script: create_alumno_representante_table.sql
        try {
            // PRIMERO: Desactivar/quitar el es_principal de todos los representantes principales actuales de este alumno
            // Esto evita duplicaciones cuando se cambia el representante principal
            $sqlDesactivarPrincipal = "UPDATE alumno_representante SET es_principal = 0 WHERE alumno_id = ? AND es_principal = 1";
            $queryDesactivarPrincipal = $pdo->prepare($sqlDesactivarPrincipal);
            $queryDesactivarPrincipal->execute([$idAlumno]);

            // SEGUNDO: Verificar si ya existe una relación con este representante (puede estar inactiva o como secundario)
            $sqlRel = "SELECT relacion_id FROM alumno_representante WHERE alumno_id = ? AND representante_id = ?";
            $queryRel = $pdo->prepare($sqlRel);
            $queryRel->execute([$idAlumno, $representanteId]);
            $relacionExistente = $queryRel->fetch(PDO::FETCH_ASSOC);

            if ($relacionExistente) {
                // Actualizar relación existente: poner como principal y activo
                $sqlUpdRel = "UPDATE alumno_representante SET parentesco_id = ?, es_principal = 1, estatus = 1 
                             WHERE relacion_id = ?";
                $queryUpdRel = $pdo->prepare($sqlUpdRel);
                $queryUpdRel->execute([$parentescoId, $relacionExistente['relacion_id']]);
            } else {
                // Insertar nueva relación como principal
                $sqlInsRel = "INSERT INTO alumno_representante (alumno_id, representante_id, parentesco_id, es_principal, estatus) 
                             VALUES (?, ?, ?, 1, 1)";
                $queryInsRel = $pdo->prepare($sqlInsRel);
                $queryInsRel->execute([$idAlumno, $representanteId, $parentescoId]);
            }
        } catch (PDOException $e) {
            // Si la tabla no existe, lanzar un error más descriptivo
            if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Table") !== false) {
                throw new Exception('La tabla alumno_representante no existe. Por favor, ejecute el script SQL: create_alumno_representante_table.sql');
            }
            throw $e; // Re-lanzar otros errores
        }


        // Confirmar transacción (MOVED DOWN) - No, wait, I will insert the new logic BEFORE commit.

        // ---------------------------------------------------------
        // LOGICA PARA REPRESENTANTE 2 (OPCIONAL)
        // ---------------------------------------------------------
        $cedulaRepresentante2 = trim($_POST['cedulaRepresentante2'] ?? '');
        $parentescoId2 = isset($_POST['listParentesco2']) ? intval($_POST['listParentesco2']) : 0;

        // Validación básica de representante 2
        if (!empty($cedulaRepresentante2)) {
            if ($cedulaRepresentante2 === $cedulaRepresentante) {
                // Si es igual al principal, ignorar o lanzar error? 
                // Mejor lanzar excepción para que el frontend lo maneje
                throw new Exception("El representante 2 no puede ser el mismo que el principal.");
            }

            // Validar parentesco 2
            if ($parentescoId2 <= 0) {
                throw new Exception("El parentesco del representante 2 es obligatorio.");
            }
        }

        // 1. Desactivar todos los representantes secundarios actuales de este alumno
        // Esto maneja tanto el caso de "eliminar" (si no se envía cedula2) como el de "cambiar" (se desactiva el viejo y se activa el nuevo abajo)
        $sqlDeactivateSec = "UPDATE alumno_representante SET estatus = 0 WHERE alumno_id = ? AND es_principal = 0";
        $queryDeactivateSec = $pdo->prepare($sqlDeactivateSec);
        $queryDeactivateSec->execute([$idAlumno]);

        // 2. Si se envió un segundo representante, activarlo/insertarlo
        if (!empty($cedulaRepresentante2)) {
            // Buscar ID del rep 2
            $sqlRep2 = "SELECT representantes_id FROM representantes WHERE cedula = ? AND estatus = 1";
            $queryRep2 = $pdo->prepare($sqlRep2);
            $queryRep2->execute([$cedulaRepresentante2]);
            $representante2 = $queryRep2->fetch(PDO::FETCH_ASSOC);

            if (!$representante2) {
                throw new Exception("El representante 2 (Cédula: $cedulaRepresentante2) no existe o está inactivo.");
            }
            $representanteId2 = intval($representante2['representantes_id']);

            // Verificar si ya existe relación (incluso si estaba inactiva o era principal antes - aunque si era principal ahora será secundario)
            $sqlRel2 = "SELECT relacion_id FROM alumno_representante WHERE alumno_id = ? AND representante_id = ?";
            $queryRel2 = $pdo->prepare($sqlRel2);
            $queryRel2->execute([$idAlumno, $representanteId2]);
            $relacionExistente2 = $queryRel2->fetch(PDO::FETCH_ASSOC);

            if ($relacionExistente2) {
                // Actualizar: poner como secundario y activo
                $sqlUpdRel2 = "UPDATE alumno_representante SET parentesco_id = ?, es_principal = 0, estatus = 1
                             WHERE relacion_id = ?";
                $queryUpdRel2 = $pdo->prepare($sqlUpdRel2);
                $queryUpdRel2->execute([$parentescoId2, $relacionExistente2['relacion_id']]);
            } else {
                // Insertar nueva relación secundaria
                $sqlInsRel2 = "INSERT INTO alumno_representante (alumno_id, representante_id, parentesco_id, es_principal, estatus)
                             VALUES (?, ?, ?, 0, 1)";
                $queryInsRel2 = $pdo->prepare($sqlInsRel2);
                $queryInsRel2->execute([$idAlumno, $representanteId2, $parentescoId2]);
            }
        }
        $pdo->commit();

        echo json_encode(['status' => true, 'msg' => $msg]);

    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error en base de datos: " . $e->getMessage());
        echo json_encode(['status' => false, 'msg' => 'Error DB: ' . $e->getMessage()]);
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error: " . $e->getMessage());
        echo json_encode(['status' => false, 'msg' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => false, 'msg' => 'No se recibieron datos']);
}
?>