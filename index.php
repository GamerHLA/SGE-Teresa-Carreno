<?php
$alert = "";
session_start();

// Verifica si ya existe una sesión activa
if (!empty($_SESSION['active'])) {
    header('Location: sistema/');
    exit();
} else {
    // Verifica si se envió el formulario (método POST)
    if (!empty($_POST)) {
        // Valida que los campos no estén vacíos
        if (empty($_POST['usuario']) || empty($_POST['pass'])) {
            $alert = 'Todos los campos son necesarios';
        } else {
            // Conexión a la base de datos
            require_once 'sistema/includes/config.php';
            $usuario = $_POST['usuario'];
            $pass = $_POST['pass'];

            // Consulta SQL para verificar credenciales
            $sql = "SELECT u.user_id,u.nombre,u.usuario,u.password,u.estatus,r.rol_id,r.nombre_rol 
                    FROM usuarios as u 
                    INNER JOIN rol as r ON u.rol = r.rol_id 
                    WHERE u.usuario = ?";
            $query = $pdo->prepare($sql);
            $query->execute(array($usuario));
            $data = $query->fetch();

            // Verifica si el usuario existe y si la contraseña coincide
            if($data && password_verify($pass, $data['password'])) {
                if($data['estatus'] == 1){
                    // Si las credenciales son correctas, crea las variables de sesión
                    $_SESSION['active'] = true;
                    $_SESSION['idUser'] = $data['user_id'];
                    $_SESSION['nombre'] = $data['nombre'];
                    $_SESSION['user'] = $data['usuario'];
                    $_SESSION['rol'] = $data['rol_id'];
                    $_SESSION['rol_name'] = $data['nombre_rol'];
                    $_SESSION['tiempo'] = time();

                    header("Location: sistema/");
                    exit();
                } else {
                    $alert = 'El usuario se encuentra inactivo';
                    session_destroy();
                }
            } else {
                $alert = 'El usuario o la clave son incorrectos';
                session_destroy();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="css/vendor/fontawesome/css/all.min.css">
    <title>SISTEMA ESCOLAR - Inscripciones</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #0a3866ff;
            /* Azul marino oscuro */
            --secondary: #000a45ff;
            /* Verde azulado */
            --accent: #dc2626;
            /* Rojo para acentos */
            --light: #fcf8f8ff;
            --dark: #1e293b;
            --gray: #64748b;
            --light-gray: #e2e8f0;
        }

        body {
            background: linear-gradient(135deg, var(--primary) 0%, #000000ff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 1);
            position: relative;
        }

        .login-left {
            flex: 1.2;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: cover;
        }

        .school-logo {
            width: 100px;
            height: 100px;
            background: rgba(226, 12, 12, 0.72);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            border: 2px solid rgba(255, 255, 255, 1);
            backdrop-filter: blur(5px);
            position: relative;
            z-index: 1;
        }

        .school-logo i {
            font-size: 45px;
            color: white;
        }

        .login-left h1 {
            font-size: 32px;
            margin-bottom: 20px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .login-left p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .features {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feature i {
            color: var(--secondary);
            font-size: 18px;
            background: rgba(59, 208, 45, 0.77);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature span {
            font-size: 14px;
        }

        .login-right {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            color: var(--primary);
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            color: var(--gray);
            font-size: 15px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
            font-size: 14px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
        }

        .form-control {
            width: 100%;
            padding: 14px 15px 14px 50px;
            border: 1px solid var(--light-gray);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: var(--light);
        }

        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(252, 252, 252, 0.2);
            outline: none;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0, 31, 63, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 31, 63, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .alert-error {
            background-color: rgba(220, 38, 38, 0.1);
            color: var(--accent);
            border: 1px solid rgba(220, 38, 38, 0.3);
        }

        .alert i {
            font-size: 18px;
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .forgot-password a {
            color: var(--secondary);
            text-decoration: none;
            transition: color 0.3s;
        }

        .forgot-password a:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        .copyright {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: var(--gray);
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-left,
            .login-right {
                padding: 40px 30px;
            }

            .login-left {
                text-align: center;
                align-items: center;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-left">
            <div class="school-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>SISTEMA DE INSCRIPCIÓN ESCOLAR</h1>
            <p>Acceda a la plataforma educativa con sus credenciales para gestionar el proceso de inscripción de
                estudiantes.</p>

            <div class="features">
                <div class="feature">
                    <i class="fas fa-check-circle"></i>
                    <span>Gestión completa de inscripciones</span>
                </div>
                <div class="feature">
                    <i class="fas fa-check-circle"></i>
                    <span>Control de documentos estudiantiles</span>
                </div>
                <div class="feature">
                    <i class="fas fa-check-circle"></i>
                    <span>Reportes y listados </span>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Iniciar Sesión</h2>
                <p>Ingrese sus credenciales para acceder al sistema</p>
            </div>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="usuario" id="usuario" class="form-control"
                            placeholder="Ingrese su usuario" required
                            value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="pass">Contraseña</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="pass" id="pass" class="form-control"
                            placeholder="Ingrese su contraseña" required>
                    </div>
                </div>

                <?php if (isset($alert) && !empty($alert)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $alert; ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    INICIAR SESIÓN
                </button>
            </form>

            <div class="copyright">
                &copy; 2025 Sistema de Inscripción Escolar. Todos los derechos reservados.
            </div>
        </div>
    </div>
</body>

</html>