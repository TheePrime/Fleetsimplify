<?php
session_start();
require_once '../../backend/config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$request_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$request_id) {
    echo "Invalid Request ID";
    exit();
}

$stmt = $pdo->prepare("
    SELECT r.*, u.name as mechanic_name 
    FROM requests r
    JOIN mechanics m ON r.mechanic_id = m.id
    JOIN users u ON m.user_id = u.id
    WHERE r.id = ? AND r.driver_id = ? AND r.status = 'Completed'
");
$stmt->execute([$request_id, $user_id]);
$requestData = $stmt->fetch();

if (!$requestData) {
    echo "Request not found or not completed.";
    exit();
}

// Check if already rated
$check = $pdo->prepare("SELECT id FROM ratings WHERE request_id = ?");
$check->execute([$request_id]);
if ($check->rowCount() > 0) {
    echo "<h2>You have already rated this service!</h2><a href='dashboard.php'>Back to Dashboard</a>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $feedback = $_POST['feedback'];
    $repair_time = (int)$_POST['repair_time_minutes'];

    if ($rating >= 1 && $rating <= 5) {
        $insert = $pdo->prepare("INSERT INTO ratings (request_id, driver_id, mechanic_id, rating, feedback, repair_time_minutes) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute([$request_id, $user_id, $requestData['mechanic_id'], $rating, $feedback, $repair_time]);
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Service - Roadside Assistance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', 'Inter', 'Roboto', sans-serif; background: #f8fafc; margin:0; padding:2rem; color: #0f172a; }
        .card { max-width: 500px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        select, textarea, input[type="number"] { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
        .btn { width: 100%; padding: 0.75rem; background-color: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn:hover { background-color: #059669; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Rate Service</h2>
        <p>Mechanic: <strong><?php echo htmlspecialchars($requestData['mechanic_name']); ?></strong></p>
        
        <form action="" method="POST">
            <div class="form-group">
                <label for="rating">Rating (1 - 5 Stars)</label>
                <select name="rating" id="rating" required>
                    <option value="">Select a rating</option>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3">3 - Average</option>
                    <option value="2">2 - Poor</option>
                    <option value="1">1 - Terrible</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="repair_time_minutes">Time taken for repair (Minutes)</label>
                <input type="number" name="repair_time_minutes" id="repair_time_minutes" min="1" required placeholder="e.g. 45">
            </div>

            <div class="form-group">
                <label for="feedback">Feedback (Optional)</label>
                <textarea name="feedback" id="feedback" rows="4" placeholder="How was the service?"></textarea>
            </div>
            
            <button type="submit" class="btn">Submit Review</button>
            <div style="text-align: center; margin-top: 1rem;">
                <a href="dashboard.php" style="color: #64748b; text-decoration: none;">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
