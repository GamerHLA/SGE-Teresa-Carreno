<?php
/**
 * INDEX.PHP
 * =========
 * 
 * Página principal (home) del sistema escolar.
 * Primera página que ve el usuario después de iniciar sesión.
 * 
 * FUNCIONALIDADES:
 * - Validación de sesión activa
 * - Redirección a login si no hay sesión
 * - Pantalla de bienvenida con diseño moderno
 * - Imagen de fondo con efecto blur
 * - Mensaje de bienvenida personalizado
 * - Nombre de la institución: U.E.D "Teresa Carreño"
 * 
 * DISEÑO:
 * - Fondo con imagen (entrada.jpg) con efecto blur
 * - Tarjeta de bienvenida con glassmorphism
 * - Animación fadeInUp al cargar
 * - Efecto hover en la tarjeta
 * 
 * DEPENDENCIAS:
 * - includes/session.php (validación de sesión)
 * - includes/header.php (encabezado HTML)
 * - includes/footer.php (scripts y cierre HTML)
 */
session_start();
if (empty($_SESSION['active'])) {
    header("Location: ../");
}
require_once 'includes/session.php';
require_once 'includes/header.php';
?>
<div style="display:flex; justify-content:flex-end;">

    <style>
        body {
            margin: 120;
            padding: 0;
            min-height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                linear-gradient(rgba(255, 255, 255, 0.15),
                    rgba(255, 255, 255, 0.08)),
                url('images/entrada.jpg');
            background-size: cover;
            background-position: left;
            background-repeat: no-repeat;
            filter: blur(2px) brightness(1.05);
            z-index: -1;
            transform: scale(1.02);
        }

        .app-content {
            flex: 0.4;
            display: absolute;
            align-items: center;
            justify-content: center;
            padding: 0px;
            margin: 30;
            position: flex;
            z-index: 1;
            min-height: calc(0vh - 0px);
        }

        .hero-content {
            background: rgba(255, 255, 255, 0.85);
            padding: 0px;
            border-radius: 10px;
            box-shadow:
                0 15px 35px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            animation: fadeInUp 0.8s ease-out;
            max-width: 100px;
            width: 100%;
            text-align: center;
        }

        .hero-heading {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 2.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .hero-text {
            color: #34495e;
            line-height: 1.7;
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        .hero-text h3 {
            color: #2980b9;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 600;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Efecto hover suave */
        .hero-content:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
            box-shadow:
                0 20px 45px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }
    </style>

    <main class="app-content" style="display:flex; justify-content:center;">
        <div class="hero-content" style="text-align:center; max-width:550px;">
            <h2>Bienvenido al sistema de inscripción</h2>
            <div class="hero-text">
                <h3>U.E.D "Teresa Carreño"</h3>
            </div>
        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>