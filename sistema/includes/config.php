<?php
/**
 * CONFIG.PHP
 * ==========
 * 
 * Archivo de configuración de conexión a la base de datos del sistema escolar.
 * 
 * FUNCIONALIDADES:
 * - Establece conexión PDO con PostgreSQL
 * - Activa modo de errores con excepciones para debugging
 * - Proporciona objeto $pdo global para consultas en todo el sistema
 * 
 * CONFIGURACIÓN:
 * - Host: localhost
 * - Base de datos: uedtcpost
 * - Usuario: postgres
 * - Contraseña: tu_password_aqui (Modificar según entorno local)
 */

$host = 'localhost';
$port = '5432';
$user = 'postgres';
$db = 'base';
$pass = '040278'; // IMPORTANTE: Cambia esta contraseña por la de tu servidor PostgreSQL

try {
    // Conexión a PostgreSQL usando PDO
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo 'ERROR DE CONEXIÓN: ' . $e->getMessage();
}