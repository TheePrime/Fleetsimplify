<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mechanic') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check approval
$stmt = $pdo->prepare("SELECT approval_status FROM mechanics WHERE user_id = ?");
$stmt->execute([$user_id]);
$mech = $stmt->fetch();

if ($mech['approval_status'] !== 'APPROVED') {
    echo json_encode(['count' => 0]);
    exit();
}

// Count pending unassigned
$stmtPending = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending' AND mechanic_id IS NULL");
$count = $stmtPending->fetchColumn();

echo json_encode(['count' => (int)$count]);
