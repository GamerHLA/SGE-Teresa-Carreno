<?php
/**
 * CONFIG.PHP
 * ==========
 * 
 * Archivo de configuración de conexión a la base de datos del sistema escolar.
 * 
 * FUNCIONALIDADES:
 * - Establece conexión PDO con MySQL
 * - Configura charset UTF-8 para soporte de caracteres especiales
 * - Activa modo de errores con excepciones para debugging
 * - Proporciona objeto $pdo global para consultas en todo el sistema
 * 
 * CONFIGURACIÓN:
 * - Host: localhost
 * - Base de datos: sistema_escolar
 * - Usuario: root
 * - Contraseña: (vacía en desarrollo local)
 */

$host = 'localhost';
$user = 'root';
$db = 'sistema_escolar';
$pass = '';

try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8', $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}