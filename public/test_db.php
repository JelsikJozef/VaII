<?php

use App\Database;

require __DIR__ . '/../App/Database.php';

$pdo = Database::getConnection();

$stmt = $pdo->query('SELECT * FROM transactions');
$rows = $stmt->fetchAll();

echo '<pre>';
var_dump($rows);
echo '</pre>';