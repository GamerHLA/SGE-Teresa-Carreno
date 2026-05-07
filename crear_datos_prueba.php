<?php
require_once 'sistema/includes/config.php';

try {
    $pdo->beginTransaction();

    echo "<h3>Generando datos de prueba...</h3>";

    // 1. Crear Rol
    $stmt = $pdo->query("SELECT id FROM rol WHERE id = 1");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO rol (id, nombre_rol) VALUES (1, 'Administrador')");
        $pdo->exec("INSERT INTO rol (id, nombre_rol) VALUES (2, 'Director')");
        $pdo->exec("INSERT INTO rol (id, nombre_rol) VALUES (3, 'Profesor')");
        echo "✅ Roles creados.<br>";
    }

    // 2. Crear Nacionalidad
    $stmt = $pdo->query("SELECT id_nacionalidades FROM nacionalidades WHERE id_nacionalidades = 1");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO nacionalidades (id_nacionalidades, codigo, descripcion) VALUES (1, 'V', 'Venezolano')");
        $pdo->exec("INSERT INTO nacionalidades (id_nacionalidades, codigo, descripcion) VALUES (2, 'E', 'Extranjero')");
        echo "✅ Nacionalidades creadas.<br>";
    }

    // 3. Crear Estado, Ciudad, Municipio, Parroquia
    $stmt = $pdo->query("SELECT id_estado FROM estados WHERE id_estado = 1");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO ciudad (id, descripcion) VALUES (1, 'Caracas')");
        $pdo->exec("INSERT INTO estados (id_estado, descripcion, ciudad_id) VALUES (1, 'Distrito Capital', 1)");
        $pdo->exec("UPDATE ciudad SET estado_id = 1 WHERE id = 1"); 
        
        $pdo->exec("INSERT INTO municipios (id_municipios, descripcion, id_estado) VALUES (1, 'Libertador', 1)");
        $pdo->exec("INSERT INTO parroquias (id_parroquias, municipios_id, descripcion) VALUES (1, 1, 'Sucre')");
        
        echo "✅ Datos geográficos básicos creados.<br>";
    }

    // 4. Crear Dirección
    $stmt = $pdo->query("SELECT id_direccion FROM direccion WHERE id_direccion = 1");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO direccion (id_direccion, calle, id_parroquias, id_municipios, id_estados, cog_postal) 
                    VALUES (1, 'Calle Principal, Casa 1', 1, 1, 1, 1000)");
        echo "✅ Dirección creada.<br>";
    }

    // 5. Crear Persona (El Administrador)
    $cedula_admin = 12345678;
    $stmt = $pdo->prepare("SELECT id_persona FROM personas WHERE cedula = ?");
    $stmt->execute([$cedula_admin]);
    if ($stmt->rowCount() == 0) {
        $sqlPersona = "INSERT INTO personas (id_persona, id_nacionalidades, id_direccion, cedula, nombres, apellidos, sexo, fecha_nacimiento, correo_electronico, telefono, alumnos_id, profesores_id, representantes_id) 
                       VALUES (1, 1, 1, ?, 'Admin', 'Sistema', 'M', '1990-01-01', 'admin@sistema.com', '04120000000', 0, 0, 0)";
        $stmtInsert = $pdo->prepare($sqlPersona);
        $stmtInsert->execute([$cedula_admin]);
        echo "✅ Persona Administrador creada.<br>";
    }

    // 6. Crear Usuario (Login)
    $usuario = 'admin';
    $password_claro = 'admin123';
    $password_hash = password_hash($password_claro, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    if ($stmt->rowCount() == 0) {
        $sqlUsuario = 'INSERT INTO usuarios (id_usuario, id_rol, id_cedula, usuario, "contraseña", fecha_crecion, estatus) 
                       VALUES (1, 1, ?, ?, ?, NOW(), 1)';
        $stmtUsuario = $pdo->prepare($sqlUsuario);
        $stmtUsuario->execute([$cedula_admin, $usuario, $password_hash]);
        
        echo "✅ Usuario creado exitosamente.<br><br>";
        echo "<b>Credenciales de acceso:</b><br>";
        echo "Usuario: <code>$usuario</code><br>";
        echo "Contraseña: <code>$password_claro</code><br>";
    } else {
        echo "ℹ️ El usuario 'admin' ya existe en la base de datos.<br>";
    }

    $pdo->commit();
    echo "<br><a href='index.php'>Ir al Login</a>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ ERROR: " . $e->getMessage();
}
?>
