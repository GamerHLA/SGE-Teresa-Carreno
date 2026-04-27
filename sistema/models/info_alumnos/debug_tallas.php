<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/config.php';

echo "<h1>Debug Tallas</h1>";

try {
    // Check columns
    echo "<h2>1. Checking Columns</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM alumnos");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Columns found in 'alumnos' table:<br>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";

    $hasCamisa = in_array('talla_camisa', $columns);
    $hasPantalon = in_array('talla_pantalon', $columns);

    echo "Has 'talla_camisa': " . ($hasCamisa ? 'YES' : 'NO') . "<br>";
    echo "Has 'talla_pantalon': " . ($hasPantalon ? 'YES' : 'NO') . "<br>";

    // Check Data
    echo "<h2>2. Checking Data (First 5 students)</h2>";
    $sql = "SELECT 
                alumno_id, 
                nombre, 
                apellido, 
                talla_camisa, 
                talla_pantalon 
            FROM alumnos 
            WHERE estatus != 0 
            LIMIT 5";
    
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Talla Camisa</th><th>Talla Pantalon</th></tr>";
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>" . $row['alumno_id'] . "</td>";
        echo "<td>" . $row['nombre'] . " " . $row['apellido'] . "</td>";
        echo "<td>[" . var_export($row['talla_camisa'], true) . "]</td>";
        echo "<td>[" . var_export($row['talla_pantalon'], true) . "]</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "<h3>Error: " . $e->getMessage() . "</h3>";
}
