<?php
require_once '../../includes/config.php';
try {
    $q = $pdo->query("DESCRIBE profesor_niveles_estudio");
    echo "<pre>";
    print_r($q->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
