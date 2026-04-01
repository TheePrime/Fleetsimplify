<?php
// config/db.php
$host = '127.0.0.1';
$db   = 'roadside_assistance';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Basic error handling for pure PHP
    // Ensure we don't output anything if this file is included before headers are sent
    die("Database connection failed: " . $e->getMessage());
}
?>
