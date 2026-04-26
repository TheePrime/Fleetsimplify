<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mechanic') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$nearbyOnly = (($_GET['nearby'] ?? '0') === '1');
$radiusKm = (float)env_value('NOTIFICATION_RADIUS_KM', '25');
if ($radiusKm <= 0) {
    $radiusKm = 25;
}

// Check approval
$stmt = $pdo->prepare("SELECT id, approval_status, latitude, longitude FROM mechanics WHERE user_id = ?");
$stmt->execute([$user_id]);
$mech = $stmt->fetch();

if (!$mech) {
    http_response_code(404);
    echo json_encode(['error' => 'Mechanic profile not found']);
    exit();
}

$mechanicId = (int)$mech['id'];
$approvalStatus = $mech['approval_status'] ?? 'PENDING APPROVAL';
$lat = isset($mech['latitude']) ? (float)$mech['latitude'] : null;
$lng = isset($mech['longitude']) ? (float)$mech['longitude'] : null;

if ($approvalStatus !== 'APPROVED') {
    echo json_encode([
        'count' => 0,
        'active_task_count' => 0,
        'paid_task_count' => 0,
        'task_updates' => [],
        'approval_status' => $approvalStatus,
        'nearby_filter_enabled' => false,
        'server_time' => date('c'),
    ]);
    exit();
}

// Count pending unassigned requests, optionally filtered by mechanic proximity.
if ($nearbyOnly && $lat !== null && $lng !== null) {
    $stmtPending = $pdo->prepare("\n        SELECT COUNT(*)\n        FROM requests r\n        WHERE r.status = 'Pending'\n          AND r.mechanic_id IS NULL\n          AND r.location_lat IS NOT NULL\n          AND r.location_lng IS NOT NULL\n          AND (\n                6371 * ACOS(\n                    COS(RADIANS(:lat)) * COS(RADIANS(r.location_lat))\n                    * COS(RADIANS(r.location_lng) - RADIANS(:lng))\n                    + SIN(RADIANS(:lat)) * SIN(RADIANS(r.location_lat))\n                )\n              ) <= :radius\n    ");
    $stmtPending->bindValue(':lat', $lat);
    $stmtPending->bindValue(':lng', $lng);
    $stmtPending->bindValue(':radius', $radiusKm);
    $stmtPending->execute();
} else {
    $stmtPending = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending' AND mechanic_id IS NULL");
}

$count = (int)$stmtPending->fetchColumn();

$stmtTasks = $pdo->prepare("\n    SELECT id, status, COALESCE(payment_status, 'Unpaid') AS payment_status, updated_at\n    FROM requests\n    WHERE mechanic_id = ?\n    ORDER BY updated_at DESC\n    LIMIT 100\n");
$stmtTasks->execute([$mechanicId]);
$taskUpdates = $stmtTasks->fetchAll();

$activeTaskCount = 0;
$paidTaskCount = 0;
foreach ($taskUpdates as $task) {
    $status = $task['status'] ?? '';
    if (!in_array($status, ['Completed', 'Cancelled'], true)) {
        $activeTaskCount++;
    }
    if (($task['payment_status'] ?? 'Unpaid') === 'Paid') {
        $paidTaskCount++;
    }
}

echo json_encode([
    'count' => $count,
    'active_task_count' => $activeTaskCount,
    'paid_task_count' => $paidTaskCount,
    'task_updates' => $taskUpdates,
    'approval_status' => $approvalStatus,
    'nearby_filter_enabled' => ($nearbyOnly && $lat !== null && $lng !== null),
    'server_time' => date('c'),
]);
