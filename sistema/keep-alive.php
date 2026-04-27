<?php
/**
 * KEEP-ALIVE.PHP
 * ==============
 * 
 * Endpoint AJAX para mantener la sesión activa del sistema escolar.
 * Llamado periódicamente por session-timeout.js para prevenir cierre automático.
 * 
 * FUNCIONALIDADES:
 * - Verifica validez de la sesión actual
 * - Actualiza timestamp de última actividad ($_SESSION['tiempo'])
 * - Retorna respuesta JSON con estado de la sesión
 * 
 * RESPUESTAS:
 * - Success: Sesión válida y actualizada
 * - Error: Sesión expirada o inválida
 * 
 * DEPENDENCIAS:
 * - Sesión iniciada con variable $_SESSION['active']
 * - Llamado desde session-timeout.js cada cierto intervalo
 */
session_start();

header('Content-Type: application/json');

// Check if session is still valid
if (isset($_SESSION['active']) && $_SESSION['active']) {
    // Update session timestamp
    $_SESSION['tiempo'] = time();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Session kept alive'
    ]);
} else {
    // Session expired or invalid
    echo json_encode([
        'status' => 'error',
        'message' => 'Session expired'
    ]);
}
?>
