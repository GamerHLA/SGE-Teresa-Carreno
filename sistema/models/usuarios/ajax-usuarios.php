<?php

require_once '../../includes/config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_POST)) {
    if (empty($_POST['txtNombre']) || empty($_POST['txtUsuario']) || empty($_POST['listRol']) || empty($_POST['listStatus'])) {
        $arrResponse = array('status' => false, 'msg' => 'Todos los campos son necesarios');
    } else {
        $idUser = intval($_POST['idUser']);
        $nombre = trim($_POST['txtNombre']);
        $usuario = trim($_POST['txtUsuario']);
        $rol = intval($_POST['listRol']);
        $profesor_id = !empty($_POST['listProfesor']) ? intval($_POST['listProfesor']) : null;
        $status = intval($_POST['listStatus']);
        $pass = isset($_POST['clave']) ? $_POST['clave'] : '';
        $changePass = isset($_POST['listChangePass']) ? $_POST['listChangePass'] : 'si';

        // Obtener persona asociada al profesor seleccionado
        $id_persona = null;
        if ($profesor_id !== null) {
            $sqlPersona = "SELECT p.id_persona FROM personas p WHERE p.profesores_id = ? LIMIT 1";
            $queryPersona = $pdo->prepare($sqlPersona);
            $queryPersona->execute([$profesor_id]);
            $profesorPersona = $queryPersona->fetch(PDO::FETCH_ASSOC);
            if ($profesorPersona) {
                $id_persona = $profesorPersona['id_persona'];
            } else {
                $arrResponse = array('status' => false, 'msg' => 'No se encontró la persona asociada al profesor seleccionado');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
        }

        // Verifica si el usuario ya existe
        $sql = "SELECT * FROM usuarios WHERE usuario = ? AND id_usuario != ?";
        $query = $pdo->prepare($sql);
        $query->execute(array($usuario, $idUser));
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $arrResponse = array('status' => false, 'msg' => 'El usuario ya existe');
        } else {
            // Verificar si el profesor ya tiene un usuario asignado (solo si se seleccionó un profesor)
            if ($profesor_id !== null && $id_persona !== null) {
                $sql_profesor = "SELECT u.id_usuario FROM usuarios u 
                                 INNER JOIN personas p ON u.id_persona = p.id_persona 
                                 WHERE p.profesores_id = ? AND u.id_usuario != ?";
                $query_profesor = $pdo->prepare($sql_profesor);
                $query_profesor->execute(array($profesor_id, $idUser));
                $result_profesor = $query_profesor->fetch(PDO::FETCH_ASSOC);

                if ($result_profesor) {
                    $arrResponse = array('status' => false, 'msg' => 'El profesor seleccionado ya tiene un usuario asignado');
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
            }

            if ($idUser == 0) {
                // Nuevo usuario
                $passHash = password_hash($pass, PASSWORD_DEFAULT);
                $sql_insert = "INSERT INTO usuarios (usuario,\"contraseña\",id_rol,id_persona,estatus) VALUES (?,?,?,?,?)";
                $query_insert = $pdo->prepare($sql_insert);
                $request = $query_insert->execute(array($usuario, $passHash, $rol, $id_persona, $status));
                $option = 1;
            } else {
                // Verificar si el usuario actual es administrator antes de actualizar
                $sql_check_admin = "SELECT id_rol, estatus FROM usuarios WHERE id_usuario = ?";
                $query_check_admin = $pdo->prepare($sql_check_admin);
                $query_check_admin->execute(array($idUser));
                $current_user_data = $query_check_admin->fetch(PDO::FETCH_ASSOC);

                if ($current_user_data['id_rol'] == 1 && $current_user_data['estatus'] == 1) {
                    // Si se intenta cambiar el rol o el estatus
                    if ($rol != 1 || $status != 1) {
                        $sql_count_admin = "SELECT COUNT(*) FROM usuarios WHERE id_rol = 1 AND estatus = 1";
                        $query_count_admin = $pdo->prepare($sql_count_admin);
                        $query_count_admin->execute();
                        $admin_count = $query_count_admin->fetchColumn();

                        if ($admin_count <= 1) {
                            $msg_error = ($rol != 1) ? 'No se puede cambiar el rol del único administrador del sistema' : 'No se puede inhabilitar al único administrador del sistema';
                            $arrResponse = array('status' => false, 'msg' => $msg_error);
                            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                            die();
                        }
                    }
                }

                // Actualizar usuario
                if ($changePass === 'no') {
                    // No cambiar contraseña
                    $sql_update = "UPDATE usuarios SET usuario = ?, id_rol = ?, id_persona = ?, estatus = ? WHERE id_usuario = ?";
                    $query_update = $pdo->prepare($sql_update);
                    $request = $query_update->execute(array($usuario, $rol, $id_persona, $status, $idUser));
                    $option = 2;
                } else {
                    // Cambiar contraseña
                    $passHash = password_hash($pass, PASSWORD_DEFAULT);
                    $sql_update = "UPDATE usuarios SET usuario = ?, \"contraseña\" = ?, id_rol = ?, id_persona = ?, estatus = ? WHERE id_usuario = ?";
                    $query_update = $pdo->prepare($sql_update);
                    $request = $query_update->execute(array($usuario, $passHash, $rol, $id_persona, $status, $idUser));
                    $option = 3;
                }
            }
            if ($request) {
                if ($option == 1) {
                    $arrResponse = array('status' => true, 'msg' => 'Usuario creado correctamente');
                } else {
                    // Si se actualizó el usuario actual, actualizar la sesión
                    if (isset($_SESSION['idUser']) && $_SESSION['idUser'] == $idUser) {
                        $_SESSION['nombre'] = $nombre;
                    }
                    $arrResponse = array('status' => true, 'msg' => 'Usuario actualizado correctamente', 'new_name' => ($idUser == $_SESSION['idUser'] ? $nombre : null));
                }
            } else {
                $arrResponse = array('status' => false, 'msg' => 'No se pudo guardar el usuario');
            }
        }
    }
    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    die();
}