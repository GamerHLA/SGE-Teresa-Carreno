<?php
require_once '../../includes/config.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM observaciones LIKE 'profesor_id'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $sql = "ALTER TABLE observaciones ADD COLUMN profesor_id INT DEFAULT NULL AFTER representantes_id";
        $pdo->exec($sql);
        echo "Column profesor_id added successfully.";
    } else {
        echo "Column profesor_id already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
