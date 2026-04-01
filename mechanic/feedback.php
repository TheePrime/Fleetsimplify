<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mechanic') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get mechanic ID
$stmt = $pdo->prepare("SELECT id FROM mechanics WHERE user_id = ?");
$stmt->execute([$user_id]);
$mechanic = $stmt->fetch();
$mechanic_id = $mechanic['id'];

// Fetch Feedback
$stmtFeedback = $pdo->prepare("
    SELECT r.*, u.name as driver_name, req.vehicle_type 
    FROM ratings r
    JOIN users u ON r.driver_id = u.id
    JOIN requests req ON r.request_id = req.id
    WHERE r.mechanic_id = ?
    ORDER BY r.created_at DESC
");
$stmtFeedback->execute([$mechanic_id]);
$feedbacks = $stmtFeedback->fetchAll();

// Calculate Average Rating
$avgRating = 0;
if (count($feedbacks) > 0) {
    $sum = array_sum(array_column($feedbacks, 'rating'));
    $avgRating = round($sum / count($feedbacks), 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Feedback - Roadside Assistance</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; margin:0; padding:0; color: #0f172a; }
        .navbar { background-color: white; padding: 1rem 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar h2 { margin: 0; color: #2563eb; }
        .navbar a { text-decoration: none; color: #ef4444; font-weight: 600; }
        
        .container { max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
        .summary-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); text-align: center; margin-bottom: 2rem; }
        .summary-card h3 { margin-top: 0; color: #64748b; }
        .average { font-size: 3rem; font-weight: 800; color: #eab308; margin: 0.5rem 0; }
        
        .feedback-item { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 1rem; border-left: 4px solid #3b82f6; }
        .feedback-header { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
        .stars { color: #eab308; font-weight: bold; }
        .driver-info { color: #475569; font-size: 0.9rem; }
        .comments { margin: 1rem 0 0 0; color: #1e293b; line-height: 1.5; }
        .repair-time { font-size: 0.85rem; color: #64748b; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>Customer Feedback</h2>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="dashboard.php" style="color: #2563eb;">Back to Dashboard</a>
            <span>|</span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="summary-card">
            <h3>Average Rating</h3>
            <div class="average">★ <?php echo $avgRating; ?></div>
            <p>Based on <?php echo count($feedbacks); ?> reviews</p>
        </div>

        <?php if (count($feedbacks) > 0): ?>
            <?php foreach($feedbacks as $fb): ?>
                <div class="feedback-item">
                    <div class="feedback-header">
                        <div class="stars">
                            <?php echo str_repeat('★', $fb['rating']) . str_repeat('☆', 5 - $fb['rating']); ?>
                        </div>
                        <div style="font-size: 0.8rem; color: #94a3b8;">
                            <?php echo date('M d, Y', strtotime($fb['created_at'])); ?>
                        </div>
                    </div>
                    <div class="driver-info">
                        <strong><?php echo htmlspecialchars($fb['driver_name']); ?></strong> (<?php echo htmlspecialchars($fb['vehicle_type']); ?>)
                    </div>
                    <p class="comments">"<?php echo nl2br(htmlspecialchars($fb['feedback'])); ?>"</p>
                    <?php if ($fb['repair_time_minutes']): ?>
                        <div class="repair-time">⏱️ Repair took: <?php echo htmlspecialchars($fb['repair_time_minutes']); ?> minutes</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #64748b;">You don't have any feedback yet. Complete jobs to receive ratings!</p>
        <?php endif; ?>
    </div>
</body>
</html>
