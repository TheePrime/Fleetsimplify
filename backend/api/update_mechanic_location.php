<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mechanic') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;

if ($lat === null || $lng === null) {
    echo json_encode(['status' => 'error', 'message' => 'Missing coordinates']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE mechanics SET latitude = ?, longitude = ? WHERE user_id = ?");
    $stmt->execute([$lat, $lng, $_SESSION['user_id']]);
    echo json_encode(['status' => 'success', 'message' => 'Location updated']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
