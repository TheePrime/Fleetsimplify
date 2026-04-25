<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$request_id = $_GET['req_id'] ?? null;

if (!$request_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing request ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT r.status, r.location_lat as driver_lat, r.location_lng as driver_lng, 
               m.latitude as mech_lat, m.longitude as mech_lng
        FROM requests r
        LEFT JOIN mechanics m ON r.mechanic_id = m.id
        WHERE r.id = ?
    ");
    $stmt->execute([$request_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Request not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
