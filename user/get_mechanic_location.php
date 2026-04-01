<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$req_id = $_GET['req_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$req_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing Request ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT r.status, m.latitude as mech_lat, m.longitude as mech_lng 
        FROM requests r
        LEFT JOIN mechanics m ON r.mechanic_id = m.id
        WHERE r.id = ? AND r.driver_id = ?
    ");
    $stmt->execute([$req_id, $user_id]);
    $data = $stmt->fetch();

    if ($data) {
        echo json_encode(['status' => 'success', 'data' => [
            'status' => $data['status'],
            'mech_lat' => (float)$data['mech_lat'],
            'mech_lng' => (float)$data['mech_lng']
        ]]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Request not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
