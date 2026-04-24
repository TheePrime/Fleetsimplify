<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$request_id = $_GET['request_id'] ?? null;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] === 'mechanic' ? 'mechanic' : 'user';

if (!$request_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing Request ID']);
    exit();
}

try {
    // Select messages with sender_role 
    $stmt = $pdo->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.request_id = ? 
        ORDER BY m.timestamp ASC
    ");
    $stmt->execute([$request_id]);
    $messages = $stmt->fetchAll();
    
    $formatted = array_map(function($m) use ($user_id, $user_role) {
        return [
            'id' => $m['id'],
            'sender' => $m['sender_name'],
            'sender_role' => $m['sender_role'],
            'message' => $m['message'],
            'time' => date('h:i A', strtotime($m['timestamp'])),
            // Left = user, Right = mechanic depending on who is requesting
            // But let's simplify based on the prompt: Left = user, Right = mechanic
            // The prompt says "(left = user, right = mechanic)".
            // Actually, usually "Right" means my own message. But if they specifically asked for left=user, right=mechanic:
            'is_user' => ($m['sender_role'] === 'user'),
            'is_mechanic' => ($m['sender_role'] === 'mechanic'),
            'is_mine' => ($m['sender_id'] == $user_id)
        ];
    }, $messages);

    echo json_encode(['status' => 'success', 'messages' => $formatted]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
