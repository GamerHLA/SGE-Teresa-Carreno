<?php
/**
 * SESSION.PHP
 * ===========
 * 
 * Control de tiempo de sesión del sistema escolar.
 * Incluido en todas las páginas para validar tiempo de inactividad.
 * 
 * FUNCIONALIDADES:
 * - Define tiempo máximo de inactividad (20 minutos / 1200 segundos)
 * - Calcula tiempo transcurrido desde última actividad
 * - Destruye sesión y redirige a login si excede tiempo límite
 * - Actualiza timestamp de última actividad en cada petición
 * 
 * SEGURIDAD:
 * - Previene sesiones abandonadas activas
 * - Protege contra acceso no autorizado por sesiones antiguas
 * 
 * DEPENDENCIAS:
 * - Requiere sesión iniciada previamente
 * - Variable $_SESSION['tiempo'] para tracking
 */
 
    // Session timeout: 20 minutes (1200 seconds)
    $inactivo = 1200;
 
    if(isset($_SESSION['tiempo']) ) {
    $vida_session = time() - $_SESSION['tiempo'];
        if($vida_session > $inactivo)
        {
            session_destroy();
            header("Location: ../"); 
            exit();
        }
    }
 
    $_SESSION['tiempo'] = time();
?>