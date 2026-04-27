<?php
ob_start();
require_once '../../includes/config.php';
// Buffer started to catch config output or whitespace



if (!empty($_POST)) {
    if (!empty($_POST['action'])) {
        file_put_contents('debug_post_log.txt', print_r($_POST, true)); // DEBUG LOG
        $action = trim($_POST['action']);

        // 1. OBTENER OPCIONES PARA SELECTS
        if ($action == 'get_medical_options') {
            $enfermedades = $pdo->query("SELECT id_enfermedad_cronica as id, enfermedades as nombre FROM enfermedad WHERE activo = 1 ORDER BY enfermedades COLLATE utf8mb4_spanish_ci")->fetchAll(PDO::FETCH_ASSOC);
            $discapacidades = $pdo->query("SELECT id_discapacidad as id, discapacidad as nombre FROM discapacidad WHERE activo = 1 ORDER BY discapacidad COLLATE utf8mb4_spanish_ci")->fetchAll(PDO::FETCH_ASSOC);
            $vacunas = $pdo->query("SELECT id_vacuna_infantil as id, nombre FROM vacunas_infantiles WHERE activo = 1 ORDER BY nombre COLLATE utf8mb4_spanish_ci")->fetchAll(PDO::FETCH_ASSOC);
            $gruposSanguineos = $pdo->query("SELECT id_grupo_sanguineo as id, grupo_sanguineo as nombre FROM grupo_sanguineo WHERE activo = 1 ORDER BY grupo_sanguineo")->fetchAll(PDO::FETCH_ASSOC);
            $parentescos = $pdo->query("SELECT id_parentesco as id, parentesco as nombre FROM parentesco WHERE activo = 1 ORDER BY parentesco")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                'status' => true,
                'enfermedades' => $enfermedades,
                'discapacidades' => $discapacidades,
                'vacunas' => $vacunas,
                'gruposSanguineos' => $gruposSanguineos,
                'parentescos' => $parentescos
            ]);
            exit;
        }

        // ============================================
        // NUEVOS HANDLERS: ADD MEDICAL OPTIONS (MOVED TO TOP)
        // ============================================
        if ($action == 'add_disease' || $action == 'add_disability' || $action == 'add_vaccine') {
            if (session_status() == PHP_SESSION_NONE)
                session_start();
            $rol = $_SESSION['rol'];

            // SOLO ADMINISTRADOR (Rol 1)
            if ($rol != 1) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'Acceso denegado. Solo administrador.']);
                exit;
            }

            if ($action == 'add_disease') {
                if (empty($_POST['nombre'])) {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'El nombre es requerido.']);
                    exit;
                }
                $nombre = trim($_POST['nombre']);
                try {
                    $exists = $pdo->prepare("SELECT id_enfermedad_cronica FROM enfermedad WHERE enfermedades = ?");
                    $exists->execute([$nombre]);
                    if ($exists->fetch()) {
                        ob_end_clean();
                        echo json_encode(['status' => false, 'msg' => 'La enfermedad ya existe.']);
                        exit;
                    }

                    $sql = "INSERT INTO enfermedad (enfermedades) VALUES (?)";
                    $insert = $pdo->prepare($sql);
                    if ($insert->execute([$nombre])) {
                        ob_end_clean();
                        echo json_encode(['status' => true, 'id' => $pdo->lastInsertId(), 'nombre' => $nombre]);
                    } else {
                        ob_end_clean();
                        echo json_encode(['status' => false, 'msg' => 'Error al registrar enfermedad']);
                    }
                    exit;
                } catch (Exception $e) {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
                    exit;
                }
            }

            if ($action == 'add_disability') {
                if (empty($_POST['nombre'])) {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'El nombre es requerido.']);
                    exit;
                }
                $nombre = trim($_POST['nombre']);
                try {
                    $exists = $pdo->prepare("SELECT id_discapacidad FROM discapacidad WHERE discapacidad = ?");
                    $exists->execute([$nombre]);
                    if ($exists->fetch()) {
                        ob_end_clean();
                        echo json_encode(['status' => false, 'msg' => 'La discapacidad ya existe.']);
                        exit;
                    }

                    $sql = "INSERT INTO discapacidad (discapacidad) VALUES (?)";
                    $insert = $pdo->prepare($sql);
                    if ($insert->execute([$nombre])) {
                        ob_end_clean();
                        echo json_encode(['status' => true, 'id' => $pdo->lastInsertId(), 'nombre' => $nombre]);
                    } else {
                        ob_end_clean();
                        echo json_encode(['status' => false, 'msg' => 'Error al registrar discapacidad']);
                    }
                    exit;
                } catch (Exception $e) {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
                    exit;
                }
            }

            if ($action == 'add_vaccine') {
                if (empty($_POST['nombre'])) {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'El nombre es requerido.']);
                    exit;
                }
                $nombre = trim($_POST['nombre']);
                try {
                    $exists = $pdo->prepare("SELECT id_vacuna_infantil FROM vacunas_infantiles WHERE nombre = ?");
                    $exists->execute([$nombre]);
                    if ($exists->fetch()) {
                        ob_end_clean();
                        echo json_encode(['status' => false, 'msg' => 'La vacuna ya existe.']);
                        exit;
                    }

                    $sql = "INSERT INTO vacunas_infantiles (nombre) VALUES (?)";
                    $insert = $pdo->prepare($sql);
                    if ($insert->execute([$nombre])) {
                        ob_end_clean();
                        echo json_encode(['status' => true, 'id' => $pdo->lastInsertId(), 'nombre' => $nombre]);
                    } else {
                        ob_end_clean();
                        echo json_encode(['status' => false, 'msg' => 'Error al registrar vacuna']);
                    }
                    exit;
                } catch (Exception $e) {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
                    exit;
                }
            }

        }

        // --- DELETE ACTIONS (SOFT DELETE) ---
        if ($action == 'delete_disease') {
            if (empty($_POST['id'])) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'ID requerido.']);
                exit;
            }
            $id = intval($_POST['id']);
            try {
                $sql = "UPDATE enfermedad SET activo = 0 WHERE id_enfermedad_cronica = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$id])) {
                    ob_end_clean();
                    echo json_encode(['status' => true, 'msg' => 'Enfermedad deshabilitada correctamente.']);
                } else {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'Error al deshabilitar enfermedad.']);
                }
                exit;
            } catch (PDOException $e) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'Error de base de datos: ' . $e->getMessage()]);
                exit;
            }
        }

        if ($action == 'delete_disability') {
            if (empty($_POST['id'])) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'ID requerido.']);
                exit;
            }
            $id = intval($_POST['id']);
            try {
                $sql = "UPDATE discapacidad SET activo = 0 WHERE id_discapacidad = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$id])) {
                    ob_end_clean();
                    echo json_encode(['status' => true, 'msg' => 'Discapacidad deshabilitada correctamente.']);
                } else {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'Error al deshabilitar discapacidad.']);
                }
                exit;
            } catch (PDOException $e) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'Error de base de datos: ' . $e->getMessage()]);
                exit;
            }
        }

        if ($action == 'delete_vaccine') {
            if (empty($_POST['id'])) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'ID requerido.']);
                exit;
            }
            $id = intval($_POST['id']);
            try {
                $sql = "UPDATE vacunas_infantiles SET activo = 0 WHERE id_vacuna_infantil = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$id])) {
                    ob_end_clean();
                    echo json_encode(['status' => true, 'msg' => 'Vacuna deshabilitada correctamente.']);
                } else {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'Error al deshabilitar vacuna.']);
                }
                exit;
            } catch (PDOException $e) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'Error de base de datos: ' . $e->getMessage()]);
                exit;
            }
        }

        // --- REACTIVATE ACTIONS ---
        if ($action == 'reactivate_disease') {
            if (empty($_POST['id'])) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'ID requerido.']);
                exit;
            }
            $id = intval($_POST['id']);
            try {
                $sql = "UPDATE enfermedad SET activo = 1 WHERE id_enfermedad_cronica = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$id])) {
                    ob_end_clean();
                    echo json_encode(['status' => true, 'msg' => 'Enfermedad reactivada correctamente.']);
                } else {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'Error al reactivar enfermedad.']);
                }
                exit;
            } catch (PDOException $e) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'Error de base de datos: ' . $e->getMessage()]);
                exit;
            }
        }

        if ($action == 'reactivate_disability') {
            if (empty($_POST['id'])) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'ID requerido.']);
                exit;
            }
            $id = intval($_POST['id']);
            try {
                $sql = "UPDATE discapacidad SET activo = 1 WHERE id_discapacidad = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$id])) {
                    ob_end_clean();
                    echo json_encode(['status' => true, 'msg' => 'Discapacidad reactivada correctamente.']);
                } else {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'Error al reactivar discapacidad.']);
                }
                exit;
            } catch (PDOException $e) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'Error de base de datos: ' . $e->getMessage()]);
                exit;
            }
        }

        if ($action == 'reactivate_vaccine') {
            if (empty($_POST['id'])) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'ID requerido.']);
                exit;
            }
            $id = intval($_POST['id']);
            try {
                $sql = "UPDATE vacunas_infantiles SET activo = 1 WHERE id_vacuna_infantil = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$id])) {
                    ob_end_clean();
                    echo json_encode(['status' => true, 'msg' => 'Vacuna reactivada correctamente.']);
                } else {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'Error al reactivar vacuna.']);
                }
                exit;
            } catch (PDOException $e) {
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'Error de base de datos: ' . $e->getMessage()]);
                exit;
            }
        }

        // 2. OBTENER DATOS DEL ALUMNO (CARGA AL MODAL)
        if ($action == 'get_alumno') {
            if (!empty($_POST['id'])) {
                $id = intval($_POST['id']);

                try {
                    $sql = "SELECT 
                            a.alumno_id,
                            a.cedula,
                            a.nombre,
                            a.apellido,
                            a.talla_camisa,
                            a.talla_pantalon,
                            a.actividad_extra,
                            a.id_nacionalidades,
                            a.fecha_nac
                        FROM alumnos a
                        WHERE a.alumno_id = ?";
                    $query = $pdo->prepare($sql);
                    $query->execute([$id]);
                    $data = $query->fetch(PDO::FETCH_ASSOC);

                    if ($data) {
                        // Fetch generic health info/conditions from informacion_medica (previously salud_escolar)
                        $sqlHealth = "SELECT 
                                    se.*,
                                    ef.enfermedades as nombre_enfermedad,
                                    di.discapacidad as nombre_discapacidad,
                                    p.parentesco as nombre_parentesco
                                  FROM informacion_medica se
                                  LEFT JOIN enfermedad ef ON se.enfermedad_id = ef.id_enfermedad_cronica
                                  LEFT JOIN discapacidad di ON se.discapacidad_id = di.id_discapacidad
                                  LEFT JOIN parentesco p ON se.parentesco_id = p.id_parentesco
                                  WHERE se.alumno_id = ?";
                        $queryHealth = $pdo->prepare($sqlHealth);
                        $queryHealth->execute([$id]);
                        $conditions = $queryHealth->fetchAll(PDO::FETCH_ASSOC);

                        // Attach Emergency info from the first row found (assuming it's replicated or we take the first one)
                        $data['emergencia_contacto_nombre'] = '';
                        $data['emergencia_contacto_telefono'] = '';
                        $data['emergencia_contacto_parentesco'] = '';
                        // Also pass the ID for the select
                        $data['emergencia_contacto_parentesco_id'] = '';

                        foreach ($conditions as $c) {
                            if (!empty($c['contacto_emergencia'])) {
                                $data['emergencia_contacto_nombre'] = $c['contacto_emergencia'];
                                $data['emergencia_contacto_telefono'] = $c['telefono_emergencia'];
                                $data['emergencia_contacto_parentesco'] = $c['nombre_parentesco'];
                                $data['emergencia_contacto_parentesco_id'] = $c['parentesco_id'];
                            }
                            if (!empty($c['grupo_sanguineo_id'])) {
                                $data['grupo_sanguineo_id'] = $c['grupo_sanguineo_id'];
                            }
                        }

                        // Fetch Vaccines from linking table
                        $sqlVac = "SELECT v.id_vacuna_infantil as id, v.nombre 
                                  FROM alumno_vacunas av
                                  INNER JOIN vacunas_infantiles v ON av.vacuna_id = v.id_vacuna_infantil
                                  WHERE av.alumno_id = ?";
                        $queryVac = $pdo->prepare($sqlVac);
                        $queryVac->execute([$id]);
                        $data['vacunas_list'] = $queryVac->fetchAll(PDO::FETCH_ASSOC);
                        // For legacy/pill compatibility as string
                        $data['vacunas'] = implode(', ', array_column($data['vacunas_list'], 'nombre'));

                        $data['condiciones'] = $conditions;

                        // Fetch Representatives Associated with the student
                        $sqlReps = "SELECT 
                                    r.nombre, 
                                    r.apellido, 
                                    r.telefono, 
                                    p.parentesco,
                                    ar.parentesco_id
                                FROM alumno_representante ar
                                INNER JOIN representantes r ON ar.representante_id = r.representantes_id
                                LEFT JOIN parentesco p ON ar.parentesco_id = p.id_parentesco
                                WHERE ar.alumno_id = ? AND ar.estatus = 1";
                        $queryReps = $pdo->prepare($sqlReps);
                        $queryReps->execute([$id]);
                        $data['representantes'] = $queryReps->fetchAll(PDO::FETCH_ASSOC);

                        // Fetch Medical Attention Details
                        $sqlAtt = "SELECT tipo_atencion, nombre_doctor, telefono FROM alumnos_atencion_medica WHERE alumno_id = ?";
                        $queryAtt = $pdo->prepare($sqlAtt);
                        $queryAtt->execute([$id]);
                        $data['atencion_medica'] = $queryAtt->fetchAll(PDO::FETCH_ASSOC);

                        ob_end_clean();
                        echo json_encode(['status' => true, 'data' => $data]);
                        exit;
                    } else {
                        ob_end_clean();
                        echo json_encode(['status' => false, 'msg' => 'Datos no encontrados']);
                        exit;
                    }
                } catch (Exception $e) {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
                    exit;
                }
            }

        }
    } else {
        // 3. GUARDAR / ACTUALIZAR DATOS
        if (!empty($_POST['idAlumnoInfo'])) {
            $id = intval($_POST['idAlumnoInfo']);

            // --- DATOS ALUMNO ---
            $talla_camisa = $_POST['tallaCamisaInfo'] ?? '';
            $talla_pantalon = $_POST['tallaPantalonInfo'] ?? '';
            $actividad = $_POST['actividadExtraInfo'] ?? '';

            // --- INFO MÉDICA (TABLA 1:1) ---
            $atencionMedica = $_POST['atencionMedica'] ?? 'NO'; // SI/NO
            $checkMedico = isset($_POST['infMedico']) ? 1 : 0; // Checkboxes mapping
            $checkPsico = isset($_POST['infPsico']) ? 1 : 0; // actually ID is 'checkPsico' in HTML but name is 'infPsico' won't work if I didn't set name?
            // Check HTML: <input ... name="infMedico" id="checkMedico"> -> Correct.

            // Doctores (Nuevos campos)
            // Array structure: type => [doctor_name, phone]
            $medical_attention_data = [
                'Médico' => [$_POST['docMedicoNombre'] ?? '', $_POST['docMedicoTelf'] ?? ''],
                'Psicológico' => [$_POST['docPsicoNombre'] ?? '', $_POST['docPsicoTelf'] ?? ''],
                'Neurológico' => [$_POST['docNeuroNombre'] ?? '', $_POST['docNeuroTelf'] ?? ''],
                'Psicopedagógico' => [$_POST['docPsicopedNombre'] ?? '', $_POST['docPsicopedTelf'] ?? '']
            ];

            // Grupo Sanguineo & Vacunas
            $grupoSang = $_POST['grupoSanguineo'] ?? '';
            $vacunas = $_POST['vacunasInfo'] ?? '';

            // --- CONDITIONS & EMERGENCY (TABLA N:M salud_escolar) ---
            $jsonEnf = $_POST['jsonEnfermedades'] ?? '[]';
            $jsonDisc = $_POST['jsonDiscapacidades'] ?? '[]';

            $listEnf = json_decode($jsonEnf, true);
            $listDisc = json_decode($jsonDisc, true);

            // Emergencia
            $emNombre = $_POST['emergenciaNombre'] ?? '';
            $emTelf = $_POST['emergenciaTelefono'] ?? '';
            $emParent = $_POST['emergenciaParentesco'] ?? '';
            if ($emParent === 'Otros')
                $emParent = $_POST['otrosParentesco'] ?? 'Otros';


            try {
                $pdo->beginTransaction();

                // A. UPDATE ALUMNOS
                $sqlAlumnos = "UPDATE alumnos SET talla_camisa=?, talla_pantalon=?, actividad_extra=? WHERE alumno_id=?";
                $pdo->prepare($sqlAlumnos)->execute([$talla_camisa, $talla_pantalon, $actividad, $id]);

                // B. UPDATE INFORMACION_MEDICA (Previously Salud Escolar)
                $pdo->prepare("DELETE FROM informacion_medica WHERE alumno_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM alumno_vacunas WHERE alumno_id = ?")->execute([$id]);

                // 1. Save Vaccines
                $vacunasIds = $_POST['vacunasIds'] ?? '[]';
                $listVacIds = json_decode($vacunasIds, true);
                if (is_array($listVacIds)) {
                    $sqlVac = "INSERT INTO alumno_vacunas (alumno_id, vacuna_id) VALUES (?, ?)";
                    $stmtVac = $pdo->prepare($sqlVac);
                    foreach ($listVacIds as $vId) {
                        $stmtVac->execute([$id, $vId]);
                    }
                }

                // 2. Save Conditions & Emergency
                $allConditions = array_merge(
                    is_array($listEnf) ? $listEnf : [],
                    is_array($listDisc) ? $listDisc : []
                );

                $grupoSangId = (!empty($_POST['grupoSanguineo']) && is_numeric($_POST['grupoSanguineo'])) ? $_POST['grupoSanguineo'] : null;

                if (count($allConditions) > 0) {
                    $first = true;
                    foreach ($allConditions as $cond) {
                        $enfermedad_id = ($cond['tipo'] == 'enfermedad') ? $cond['id_ref'] : null;
                        $discapacidad_id = ($cond['tipo'] == 'discapacidad') ? $cond['id_ref'] : null;

                        $c_emNombre = $first ? $emNombre : null;
                        $c_emTelf = $first ? $emTelf : null;
                        $c_parent = $first ? $emParent : null;

                        $sqlIn = "INSERT INTO informacion_medica (alumno_id, enfermedad_id, discapacidad_id, diagnostico, fecha_diagnostico, tratamiento, restricciones, alergias, contacto_emergencia, telefono_emergencia, parentesco_id, grupo_sanguineo_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $pdo->prepare($sqlIn)->execute([
                            $id,
                            $enfermedad_id,
                            $discapacidad_id,
                            $cond['diagnostico'],
                            !empty($cond['fecha']) ? $cond['fecha'] : null,
                            $cond['tratamiento'],
                            $cond['restricciones'],
                            $cond['alergias'] ?? null,
                            $c_emNombre,
                            $c_emTelf,
                            (int) $c_parent,
                            $grupoSangId
                        ]);
                        $first = false;
                    }
                } else {
                    if (!empty($emNombre) || !empty($grupoSangId)) {
                        $sqlIn = "INSERT INTO informacion_medica (alumno_id, contacto_emergencia, telefono_emergencia, parentesco_id, grupo_sanguineo_id) VALUES (?, ?, ?, ?, ?)";
                        $pdo->prepare($sqlIn)->execute([$id, $emNombre, $emTelf, (int) $emParent, $grupoSangId]);
                    }
                }

                // 3. Save Medical Attention (Doctors)
                // First delete existing
                $pdo->prepare("DELETE FROM alumnos_atencion_medica WHERE alumno_id = ?")->execute([$id]);

                if ($atencionMedica === 'SI') {
                    $stmtAtt = $pdo->prepare("INSERT INTO alumnos_atencion_medica (alumno_id, tipo_atencion, nombre_doctor, telefono) VALUES (?, ?, ?, ?)");

                    if ($checkMedico && !empty($medical_attention_data['Médico'][0])) {
                        $stmtAtt->execute([$id, 'Médico', $medical_attention_data['Médico'][0], $medical_attention_data['Médico'][1]]);
                    }
                    if ($checkPsico && !empty($medical_attention_data['Psicológico'][0])) {
                        $stmtAtt->execute([$id, 'Psicológico', $medical_attention_data['Psicológico'][0], $medical_attention_data['Psicológico'][1]]);
                    }
                    if ($checkNeuro && !empty($medical_attention_data['Neurológico'][0])) {
                        $stmtAtt->execute([$id, 'Neurológico', $medical_attention_data['Neurológico'][0], $medical_attention_data['Neurológico'][1]]);
                    }
                    // For Psicopedagogo mapping
                    $checkPsicoped = isset($_POST['infPsicoPed']) ? 1 : 0;
                    if ($checkPsicoped && !empty($medical_attention_data['Psicopedagógico'][0])) {
                        $stmtAtt->execute([$id, 'Psicopedagógico', $medical_attention_data['Psicopedagógico'][0], $medical_attention_data['Psicopedagógico'][1]]);
                    }
                }

                $pdo->commit();
                ob_end_clean();
                echo json_encode(['status' => true, 'msg' => 'Información actualizada correctamente']);
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                ob_end_clean();
                echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
                exit;
            }
        }
    }
}

$output = ob_get_contents();
ob_end_clean();
if (empty($output)) {
    // Debug Information
    $reqAction = $_POST['action'] ?? 'N/A';
    $sanitized = isset($action) ? $action : 'N/A';
    $hex = bin2hex($reqAction);

    echo json_encode([
        'status' => false,
        'msg' => 'No se ejecutó ninguna acción válida.',
        'debug_post' => $_POST,
        'debug_action' => $reqAction,
        'debug_hex' => $hex,
        'debug_sanitized' => $sanitized
    ]);
} else {
    // If there was output but we didn't exit, something is wrong.
    echo json_encode(['status' => false, 'msg' => 'Salida inesperada: ' . $output]);
}
