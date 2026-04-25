<?php
session_start();
require_once '../../backend/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Fetch chats for this user
$chats = [];

if ($user_role === 'user') {
    // Driver: fetch requests they made that have a mechanic assigned
    $stmt = $pdo->prepare("
        SELECT r.id as request_id, r.vehicle_type, r.problem_description, r.status, r.updated_at,
               u.name as other_party_name, m.business_name
        FROM requests r
        JOIN mechanics m ON r.mechanic_id = m.id
        JOIN users u ON m.user_id = u.id
        WHERE r.driver_id = ? AND r.status != 'Pending'
        ORDER BY r.updated_at DESC
    ");
    $stmt->execute([$user_id]);
    $chats = $stmt->fetchAll();
} elseif ($user_role === 'mechanic') {
    // Mechanic: fetch requests assigned to them
    $stmtM = $pdo->prepare("SELECT id FROM mechanics WHERE user_id = ?");
    $stmtM->execute([$user_id]);
    $mech = $stmtM->fetch();
    
    if ($mech) {
        $stmt = $pdo->prepare("
            SELECT r.id as request_id, r.vehicle_type, r.problem_description, r.status, r.updated_at,
                   u.name as other_party_name
            FROM requests r
            JOIN users u ON r.driver_id = u.id
            WHERE r.mechanic_id = ? AND r.status != 'Pending'
            ORDER BY r.updated_at DESC
        ");
        $stmt->execute([$mech['id']]);
        $chats = $stmt->fetchAll();
    }
} else {
    // Admin does not have direct chats in this context
    header("Location: ../admin/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages - Roadside Assistance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #e2e8f0; margin: 0; padding: 0; display:flex; justify-content:center; align-items:center; height:100vh; }
        .list-container { width: 100%; max-width: 600px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display:flex; flex-direction:column; height: 85vh; overflow:hidden;}
        
        .list-header { background: #1e293b; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .list-header h3 { margin: 0; font-size: 1.1rem; }
        .back-btn { color: #cbd5e1; text-decoration: none; font-size: 0.9rem; }
        .back-btn:hover { color: white; }
        
        .chats-list { flex: 1; overflow-y: auto; background: #f8fafc; padding: 0; margin: 0; list-style: none; }
        
        .chat-item {
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .chat-item:hover {
            background: #f1f5f9;
        }
        
        .chat-info { flex: 1; }
        .chat-name { font-weight: 600; font-size: 1rem; color: #0f172a; margin-bottom: 4px; }
        .chat-desc { font-size: 0.85rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 350px; }
        
        .chat-meta { text-align: right; margin-left: 15px; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: bold; margin-bottom: 5px; }
        .status-Accepted { background: #dbeafe; color: #1d4ed8; }
        .status-InProgress { background: #fef08a; color: #854d0e; }
        .status-Completed { background: #dcfce3; color: #166534; }
        .status-Cancelled { background: #fee2e2; color: #991b1b; }
        
        .chat-time { font-size: 0.75rem; color: #94a3b8; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #64748b; }
    </style>
</head>
<body>

    <div class="list-container">
        <div class="list-header">
            <h3>My Messages</h3>
            <a href="../<?= $user_role === 'mechanic' ? 'mechanic' : 'user' ?>/dashboard.php" class="back-btn">← Dashboard</a>
        </div>
        
        <ul class="chats-list">
            <?php if (count($chats) > 0): ?>
                <?php foreach ($chats as $chat): ?>
                    <?php 
                        $statusClass = str_replace(' ', '', $chat['status']); 
                        $displayName = $chat['other_party_name'];
                        if (isset($chat['business_name']) && !empty($chat['business_name'])) {
                            $displayName = $chat['business_name'] . " (" . $chat['other_party_name'] . ")";
                        }
                    ?>
                    <a href="chat_ui.php?request_id=<?= $chat['request_id'] ?>" class="chat-item">
                        <div class="chat-info">
                            <div class="chat-name"><?= htmlspecialchars($displayName) ?></div>
                            <div class="chat-desc"><?= htmlspecialchars($chat['vehicle_type']) ?> - <?= htmlspecialchars($chat['problem_description']) ?></div>
                        </div>
                        <div class="chat-meta">
                            <div class="status-badge status-<?= $statusClass ?>"><?= htmlspecialchars($chat['status']) ?></div>
                            <div class="chat-time"><?= date('M d, h:i A', strtotime($chat['updated_at'])) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 3rem; margin-bottom: 10px;">💬</div>
                    <p>No active chats found.</p>
                    <p style="font-size: 0.85rem;">Chats will appear here once a mechanic accepts your request.</p>
                </div>
            <?php endif; ?>
        </ul>
    </div>

</body>
</html>
