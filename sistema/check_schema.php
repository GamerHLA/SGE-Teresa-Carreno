<?php
require 'includes/config.php';
$stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'personas'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
