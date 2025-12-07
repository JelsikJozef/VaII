<?php

use App\Database;

require_once __DIR__ . '/../Framework/ClassLoader.php';

$pdo = Database::getConnection();

$stmt = $pdo->query('SELECT * FROM transactions');
$rows = $stmt->fetchAll();

echo '<pre>';
var_dump($rows);
echo '</pre>';