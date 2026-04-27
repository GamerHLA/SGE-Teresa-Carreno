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

        // Verifica si el usuario ya existe
        $sql = "SELECT * FROM usuarios WHERE usuario = ? AND user_id != ?";
        $query = $pdo->prepare($sql);
        $query->execute(array($usuario, $idUser));
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $arrResponse = array('status' => false, 'msg' => 'El usuario ya existe');
        } else {
            // Verificar si el profesor ya tiene un usuario asignado (solo si se seleccionó un profesor)
            if ($profesor_id !== null) {
                $sql_profesor = "SELECT u.user_id FROM usuarios u WHERE u.profesor_id = ? AND u.user_id != ?";
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
                $sql_insert = "INSERT INTO usuarios (nombre,usuario,password,rol,profesor_id,estatus) VALUES (?,?,?,?,?,?)";
                $query_insert = $pdo->prepare($sql_insert);
                $request = $query_insert->execute(array($nombre, $usuario, $passHash, $rol, $profesor_id, $status));
                $option = 1;
            } else {
                // Verificar si el usuario actual es administrator antes de actualizar
                $sql_check_admin = "SELECT rol, estatus FROM usuarios WHERE user_id = ?";
                $query_check_admin = $pdo->prepare($sql_check_admin);
                $query_check_admin->execute(array($idUser));
                $current_user_data = $query_check_admin->fetch(PDO::FETCH_ASSOC);

                if ($current_user_data['rol'] == 1 && $current_user_data['estatus'] == 1) {
                    // Si se intenta cambiar el rol o el estatus
                    if ($rol != 1 || $status != 1) {
                        $sql_count_admin = "SELECT COUNT(*) FROM usuarios WHERE rol = 1 AND estatus = 1";
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
                    $sql_update = "UPDATE usuarios SET nombre = ?, usuario = ?, rol = ?, profesor_id = ?, estatus = ? WHERE user_id = ?";
                    $query_update = $pdo->prepare($sql_update);
                    $request = $query_update->execute(array($nombre, $usuario, $rol, $profesor_id, $status, $idUser));
                    $option = 2;
                } else {
                    // Cambiar contraseña
                    $passHash = password_hash($pass, PASSWORD_DEFAULT);
                    $sql_update = "UPDATE usuarios SET nombre = ?, usuario = ?, password = ?, rol = ?, profesor_id = ?, estatus = ? WHERE user_id = ?";
                    $query_update = $pdo->prepare($sql_update);
                    $request = $query_update->execute(array($nombre, $usuario, $passHash, $rol, $profesor_id, $status, $idUser));
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