<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$request_id = $_POST['request_id'] ?? null;
$message = trim($_POST['message'] ?? '');

$sender_id = $_SESSION['user_id'];
$sender_role = $_SESSION['user_role'] === 'mechanic' ? 'mechanic' : 'user';

if (!$request_id || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit();
}

try {
    // Validate request existence and participant
    $stmtReq = $pdo->prepare("SELECT driver_id, mechanic_id, status FROM requests WHERE id = ?");
    $stmtReq->execute([$request_id]);
    $req = $stmtReq->fetch();

    if (!$req || ($req['status'] !== 'Accepted' && $req['status'] !== 'In Progress' && $req['status'] !== 'Completed')) {
        echo json_encode(['status' => 'error', 'message' => 'Chat is unavailable for this request.']);
        exit();
    }

    $is_authorized = false;
    if ($sender_role === 'user' && $req['driver_id'] == $sender_id) $is_authorized = true;
    
    if ($sender_role === 'mechanic') {
        $stmtM = $pdo->prepare("SELECT id FROM mechanics WHERE user_id = ?");
        $stmtM->execute([$sender_id]);
        $m = $stmtM->fetch();
        if ($m && $req['mechanic_id'] == $m['id']) $is_authorized = true;
    }

    if (!$is_authorized) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized sender.']);
        exit();
    }

    // Insert message into database correctly mapped to sender_role
    $stmt = $pdo->prepare("INSERT INTO messages (request_id, sender_id, sender_role, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$request_id, $sender_id, $sender_role, $message]);
    
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
