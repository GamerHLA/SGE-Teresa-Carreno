<?php
/**
 * LOGOUT.PHP
 * ==========
 * 
 * Script de cierre de sesión del sistema escolar.
 * 
 * FUNCIONALIDADES:
 * - Inicia la sesión para poder destruirla
 * - Elimina todas las variables de sesión (session_unset)
 * - Destruye la sesión completamente (session_destroy)
 * - Redirige al usuario a la página de login (../)
 * 
 * SEGURIDAD:
 * - Limpia completamente la sesión del usuario
 * - Previene acceso no autorizado después del logout
 */

session_start();

session_unset();

session_destroy();

header("Location: ../");