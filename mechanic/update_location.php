<?php
// Endpoint for AJAX polling to update mechanic's location
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mechanic') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $lat = $data['lat'] ?? null;
    $lng = $data['lng'] ?? null;
    $user_id = $_SESSION['user_id'];

    if ($lat !== null && $lng !== null) {
        try {
            $stmt = $pdo->prepare("UPDATE mechanics SET latitude = ?, longitude = ? WHERE user_id = ?");
            $stmt->execute([$lat, $lng, $user_id]);
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
