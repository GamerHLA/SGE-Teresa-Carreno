<?php
$alert = "";
session_start();

// Verifica si ya existe una sesión activa
if (!empty($_SESSION['active'])) {
    header('Location: sistema/');
    exit();
}

// Verifica si se envió el formulario
if (!empty($_POST)) {
    // Valida que los campos no estén vacíos
    if (empty($_POST['usuario']) || empty($_POST['pass'])) {
        $alert = 'Todos los campos son necesarios';
    } else {
        // Conexión a la base de datos
        require_once 'sistema/includes/config.php';
        
        $usuario = trim($_POST['usuario']);
        $pass = $_POST['pass'];
        
        try {
            // Consulta simplificada y corregida para PostgreSQL
            $sql = "SELECT 
                        u.id_usuario, 
                        p.nombres, 
                        p.apellidos,
                        u.usuario, 
                        u.\"contraseña\" AS contraseña,
                        u.estatus, 
                        r.id as rol_id, 
                        r.nombre_rol 
                    FROM usuarios u 
                    INNER JOIN rol r ON u.id_rol = r.id 
                    INNER JOIN personas p ON u.id_persona = p.id_persona
                    WHERE u.usuario = :usuario";
            
            $query = $pdo->prepare($sql);
            $query->execute([':usuario' => $usuario]);
            $data = $query->fetch(PDO::FETCH_ASSOC);
            
            // Verificar si el usuario existe
            if (!$data) {
                $alert = 'El usuario o la clave son incorrectos';
                error_log("Login fallido: Usuario no existe - " . $usuario);
                session_destroy();
            }
            // Verificar si el usuario está activo
            elseif ($data['estatus'] != 1) {
                $alert = 'El usuario se encuentra inactivo';
                error_log("Login fallido: Usuario inactivo - " . $usuario);
                session_destroy();
            }
            else {
                // Obtener la contraseña almacenada
                $stored_password = $data['contraseña'];
                $login_success = false;
                
                // Verificar si la contraseña está hasheada (formato Bcrypt: empieza con $2y$ o $2a$)
                if (preg_match('/^\$2[ay]\$/', $stored_password)) {
                    // Contraseña hasheada - usar password_verify
                    $login_success = password_verify($pass, $stored_password);
                } else {
                    // Contraseña en texto plano - comparación directa
                    $login_success = ($pass == $stored_password);
                }
                
                if ($login_success) {
                    // Iniciar sesión
                    $_SESSION['active'] = true;
                    $_SESSION['idUser'] = $data['id_usuario'];
                    $_SESSION['nombre'] = $data['nombres'] . ' ' . $data['apellidos'];
                    $_SESSION['user'] = $data['usuario'];
                    $_SESSION['rol'] = $data['rol_id'];
                    $_SESSION['rol_name'] = $data['nombre_rol'];
                    $_SESSION['tiempo'] = time();
                    
                    error_log("Login exitoso: " . $usuario);
                    header("Location: sistema/");
                    exit();
                } else {
                    $alert = 'El usuario o la clave son incorrectos';
                    error_log("Login fallido: Contraseña incorrecta - " . $usuario);
                    session_destroy();
                }
            }
        } catch (PDOException $e) {
            $alert = 'Error en el sistema. Contacte al administrador.';
            error_log("Error de BD en login: " . $e->getMessage());
            session_destroy();
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

        body {
            background: linear-gradient(135deg, #0a3866 0%, #000000 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="10" cy="10" r="2" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="20" r="3" fill="rgba(255,255,255,0.05)"/><circle cx="50" cy="85" r="4" fill="rgba(255,255,255,0.05)"/><circle cx="30" cy="50" r="2" fill="rgba(255,255,255,0.05)"/><circle cx="80" cy="70" r="3" fill="rgba(255,255,255,0.05)"/></svg>');
            background-repeat: repeat;
            opacity: 0.5;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        .login-container {
            display: flex;
            max-width: 1100px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6);
        }

        .login-left {
            flex: 1.2;
            background: linear-gradient(135deg, #0a3866 0%, #000a45 100%);
            color: white;
            padding: 60px 40px;
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
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .school-logo {
            width: 110px;
            height: 110px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 35px;
            border: 4px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            position: relative;
            z-index: 1;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }

        .school-logo:hover {
            transform: scale(1.05);
        }

        .school-logo i {
            font-size: 50px;
            color: white;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3));
        }

        .login-left h1 {
            font-size: 34px;
            margin-bottom: 20px;
            font-weight: 800;
            position: relative;
            z-index: 1;
            background: linear-gradient(135deg, #fff, #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            gap: 18px;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .feature:hover {
            transform: translateX(10px);
            background: rgba(255,255,255,0.2);
        }

        .feature i {
            color: #015e18;
            font-size: 32px;
            background: rgba(255,255,255,0.2);
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }

        .feature:hover i {
            transform: rotate(360deg) scale(1.1);
        }

        .feature span {
            font-size: 15px;
            font-weight: 500;
        }

        .login-right {
            flex: 1;
            padding: 60px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }

        .login-header {
            text-align: center;
            margin-bottom: 45px;
        }

        .login-header h2 {
            color: #0a3866;
            font-size: 32px;
            margin-bottom: 12px;
            font-weight: 800;
            position: relative;
            display: inline-block;
        }

        .login-header h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #dc2626, #b91c1c);
            border-radius: 3px;
        }

        .login-header p {
            color: #64748b;
            font-size: 15px;
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 28px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 18px;
            transition: color 0.3s ease;
            z-index: 1;
        }

        .form-control {
            width: 100%;
            padding: 15px 18px 15px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background-color: #f8fafc;
            font-weight: 500;
        }

        .form-control:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
            outline: none;
            background-color: white;
        }

        .form-control:focus + i {
            color: #dc2626;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #0a3866 0%, #000a45 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 5px 15px rgba(10, 56, 102, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(10, 56, 102, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-error {
            background: linear-gradient(135deg, #fee 0%, #ffe6e6 100%);
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .alert i {
            font-size: 20px;
        }

        .forgot-password {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
        }

        .forgot-password a {
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
        }

        .forgot-password a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #dc2626;
            transition: width 0.3s;
        }

        .forgot-password a:hover::after {
            width: 100%;
        }

        .forgot-password a:hover {
            color: #b91c1c;
        }

        .copyright {
            text-align: center;
            margin-top: 35px;
            font-size: 12px;
            color: #94a3b8;
            font-weight: 500;
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

            .feature {
                width: 100%;
                justify-content: center;
            }

            .feature:hover {
                transform: translateX(0) scale(1.02);
            }

            .login-header h2 {
                font-size: 28px;
            }

            .animated-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    overflow: hidden;
    background: linear-gradient(135deg, #0a3866 0%, #000000 100%);
}

.animated-bg .waves {
    position: absolute;
    width: 100%;
    height: 100%;
    bottom: 0;
    left: 0;
}

.animated-bg .wave {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 100px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 100% 100% 0 0;
    animation: wave 10s linear infinite;
}

.animated-bg .wave:nth-child(2) {
    bottom: 10px;
    background: rgba(255, 255, 255, 0.03);
    animation: wave 15s linear infinite reverse;
    opacity: 0.5;
}

.animated-bg .wave:nth-child(3) {
    bottom: 20px;
    background: rgba(220, 38, 38, 0.02);
    animation: wave 20s linear infinite;
    opacity: 0.3;
}

@keyframes wave {
    0% {
        transform: translateX(0) translateY(0) scaleX(1);
    }
    50% {
        transform: translateX(-25%) translateY(10px) scaleX(1.2);
    }
    100% {
        transform: translateX(-50%) translateY(0) scaleX(1);
    }
}

.animated-bg .stars {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.animated-bg .star {
    position: absolute;
    background: white;
    border-radius: 50%;
    opacity: 0;
    animation: twinkle 3s infinite;
}

@keyframes twinkle {
    0%, 100% {
        opacity: 0;
        transform: scale(0.5);
    }
    50% {
        opacity: 0.6;
        transform: scale(1);
    }
}

.animated-bg .dot {
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: float 8s infinite ease-in-out;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) translateX(0);
    }
    25% {
        transform: translateY(-20px) translateX(10px);
    }
    50% {
        transform: translateY(0) translateX(20px);
    }
    75% {
        transform: translateY(20px) translateX(10px);
    }
}
        }
    </style>
</head>

<body>

    <div class="animated-bg">
    <div class="waves">
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
    </div>
    <div class="stars" id="stars"></div>
    <div class="dots" id="dots"></div>
</div>

    <div class="login-container">
        <div class="login-left">
            <div class="school-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>SISTEMA DE REGISTRO Y CONTROL ACADÉMICO</h1>
            <p>Acceda a la plataforma educativa con sus credenciales para su gestion.</p>

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
                    <span>Reportes y listados</span>
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
                &copy; 2025 Sistema de Registros Académicos. Todos los derechos reservados.
            </div>
        </div>
    </div>

    <script>
    // Animación de entrada para los elementos del formulario
    document.addEventListener('DOMContentLoaded', function() {
        const formElements = document.querySelectorAll('.form-group, .btn-login, .alert');
        formElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 100);
        });
    });

    // Generar estrellas y puntos flotantes
function createStars() {
    const starsContainer = document.getElementById('stars');
    for (let i = 0; i < 100; i++) {
        const star = document.createElement('div');
        star.classList.add('star');
        const size = Math.random() * 3 + 1;
        star.style.width = size + 'px';
        star.style.height = size + 'px';
        star.style.left = Math.random() * 100 + '%';
        star.style.top = Math.random() * 100 + '%';
        star.style.animationDelay = Math.random() * 5 + 's';
        star.style.animationDuration = (Math.random() * 3 + 2) + 's';
        starsContainer.appendChild(star);
    }
}

function createDots() {
    const dotsContainer = document.getElementById('dots');
    if (!dotsContainer) {
        const container = document.createElement('div');
        container.className = 'dots';
        container.id = 'dots';
        container.style.position = 'absolute';
        container.style.width = '100%';
        container.style.height = '100%';
        document.querySelector('.animated-bg').appendChild(container);
    }
    
    const dots = document.querySelector('#dots');
    for (let i = 0; i < 30; i++) {
        const dot = document.createElement('div');
        dot.classList.add('dot');
        const size = Math.random() * 6 + 2;
        dot.style.width = size + 'px';
        dot.style.height = size + 'px';
        dot.style.left = Math.random() * 100 + '%';
        dot.style.top = Math.random() * 100 + '%';
        dot.style.animationDelay = Math.random() * 5 + 's';
        dot.style.animationDuration = (Math.random() * 5 + 4) + 's';
        dots.appendChild(dot);
    }
}

// Ejecutar cuando cargue la página
document.addEventListener('DOMContentLoaded', function() {
    createStars();
    createDots();
});

    
</script>

</body>

</html>