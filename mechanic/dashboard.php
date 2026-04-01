<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mechanic') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get mechanic's internal ID
$stmt = $pdo->prepare("SELECT id, availability, approval_status FROM mechanics WHERE user_id = ?");
$stmt->execute([$user_id]);
$mechanic = $stmt->fetch();
$mechanic_id = $mechanic['id'];
$availability = $mechanic['availability'];
$approval_status = $mechanic['approval_status'];
$mechanic_id = $mechanic['id'];
$availability = $mechanic['availability'];

// Fetch Pending requests that have NO assigned mechanic
$stmtPending = $pdo->query("
    SELECT r.*, u.name as driver_name, u.phone as driver_phone 
    FROM requests r
    JOIN users u ON r.driver_id = u.id
    WHERE r.status = 'Pending' AND r.mechanic_id IS NULL
    ORDER BY r.created_at DESC
");
$pendingRequests = $stmtPending->fetchAll();

// Fetch Mechanic's active/completed tasks
$stmtTasks = $pdo->prepare("
    SELECT r.*, u.name as driver_name, u.phone as driver_phone 
    FROM requests r
    JOIN users u ON r.driver_id = u.id
    WHERE r.mechanic_id = ? 
    ORDER BY r.created_at DESC
");
$stmtTasks->execute([$mechanic_id]);
$myTasks = $stmtTasks->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mechanic Dashboard - Roadside Assistance</title>
    <style>
        :root {
            --primary: #2563eb;
            --bg-color: #f8fafc;
            --form-bg: #ffffff;
            --text-color: #0f172a;
            --border-color: #e2e8f0;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); margin: 0; color: var(--text-color); }
        .navbar { background-color: var(--form-bg); padding: 1rem 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar h2 { margin: 0; color: var(--primary); }
        .navbar a { text-decoration: none; color: var(--danger); font-weight: 600; }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        @media (max-width: 768px) { .container { grid-template-columns: 1fr; } }

        .card { background-color: var(--form-bg); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card h3 { margin-top: 0; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; display:flex; justify-content:space-between; align-items:center;}

        .req-box { border: 1px solid var(--border-color); padding: 1rem; border-radius: 6px; margin-bottom: 1rem; background-color: #fafafa; }
        .req-box h4 { margin: 0 0 0.5rem 0; color: var(--primary); }
        
        .btn { padding: 0.5rem 1rem; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.85rem;}
        .btn-success { background-color: var(--success); }
        .btn-info { background-color: var(--primary); }
        
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-success { background-color: #ecfdf5; color: #059669; border: 1px solid #6ee7b7; }
        .alert-error { background-color: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }
        
        .status-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .status-Pending { background-color: #fef3c7; color: #d97706; }
        .status-Accepted { background-color: #e0e7ff; color: #4338ca; }
        .status-InProgress { background-color: #dbeafe; color: #1d4ed8; }
        .status-Completed { background-color: #d1fae5; color: #059669; }
    </style>
</head>
<body>

    <div class="navbar">
        <h2>Mechanic Dashboard</h2>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="dashboard.php" style="color:var(--primary);">Dashboard</a>
            <a href="feedback.php" style="color:var(--primary);">Feedback</a>
            <a href="profile.php" style="color:var(--primary);">Business Profile</a>
            <span>|</span>
            <span>Welcome, <?php echo htmlspecialchars($user_name); ?></span>
            <span>| Status: <strong><?php echo $mechanic['approval_status'] === 'APPROVED' ? $availability : 'PENDING APPROVAL'; ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        
        <!-- Left Column: Pending Requests (Marketplace) -->
        <div class="card">
            <h3>New Breakdown Requests <span id="reqCount" style="background:var(--danger);color:white;padding:2px 8px;border-radius:10px;font-size:0.8rem;"><?php echo count($pendingRequests); ?></span></h3>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if ($approval_status !== 'APPROVED'): ?>
                <div class="alert alert-error">
                    Your business is currently <strong>PENDING APPROVAL</strong> by the admin. You cannot view or accept new requests until approved.
                </div>
            <?php else: ?>
                <?php if (count($pendingRequests) > 0): ?>
                    <?php foreach($pendingRequests as $req): ?>
                        <div class="req-box">
                            <h4>🚗 <?php echo htmlspecialchars($req['vehicle_type']); ?></h4>
                            <p><strong>Driver:</strong> <?php echo htmlspecialchars($req['driver_name']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($req['location_address']); ?></p>
                            <p><strong>Problem:</strong> <?php echo htmlspecialchars($req['problem_description']); ?></p>
                            
                            <form action="accept_request.php" method="POST" style="margin-top: 1rem;">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <button type="submit" class="btn btn-success">Accept Job</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No new requests at the moment.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Right Column: My Tasks -->
        <div class="card">
            <h3>My Assignments</h3>
            <?php if (count($myTasks) > 0): ?>
                <?php foreach($myTasks as $task): ?>
                    <div class="req-box">
                        <div style="display:flex; justify-content:space-between;">
                            <h4>Order #<?php echo $task['id']; ?></h4>
                            <?php $sClass = str_replace(' ', '', $task['status']); ?>
                            <span class="status-badge status-<?php echo $sClass; ?>"><?php echo htmlspecialchars($task['status']); ?></span>
                        </div>
                        <p><strong>Driver:</strong> <?php echo htmlspecialchars($task['driver_name']); ?> (<?php echo htmlspecialchars($task['driver_phone']); ?>)</p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($task['location_address']); ?></p>
                        
                        <?php if($task['status'] !== 'Completed' && $task['status'] !== 'Cancelled'): ?>
                            <form action="update_task.php" method="POST" style="margin-top: 1rem; display: flex; gap: 0.5rem; align-items: center;">
                                <input type="hidden" name="request_id" value="<?php echo $task['id']; ?>">
                                <select name="status" style="padding: 0.4rem; border-radius:4px; border:1px solid #ccc;">
                                    <option value="Accepted" <?php echo $task['status'] == 'Accepted' ? 'selected' : ''; ?>>Accepted (On the way)</option>
                                    <option value="In Progress" <?php echo $task['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress (Repairing)</option>
                                    <option value="Completed">Completed</option>
                                </select>
                                <button type="submit" class="btn btn-info">Update Status</button>
                                <a href="../chat/chat_ui.php?request_id=<?php echo $task['id']; ?>" class="btn" style="background-color: #64748b;">Chat</a>
                            </form>
                        <?php else: ?>
                            <div style="margin-top: 1rem;">
                                <a href="../chat/chat_ui.php?request_id=<?php echo $task['id']; ?>" class="btn" style="background-color: #64748b;">View Chat History</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>You have no assigned tasks.</p>
            <?php endif; ?>
        </div>

    </div>

    <script>
        // Ringtone / Notification System
        let lastCount = <?php echo count($pendingRequests); ?>;
        const approvalStatus = "<?php echo $approval_status; ?>";

        function playRingtone() {
            // Use Web Audio API for a synthetic ringtone
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gainNode = ctx.createGain();
            
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime); // A5
            osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.1);
            
            gainNode.gain.setValueAtTime(0.5, ctx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
            
            osc.connect(gainNode);
            gainNode.connect(ctx.destination);
            
            osc.start();
            osc.stop(ctx.currentTime + 0.5);
        }

        function checkNewRequests() {
            if (approvalStatus !== 'APPROVED') return;
            
            fetch('api_pending_requests.php')
                .then(res => res.json())
                .then(data => {
                    if (data.count > lastCount) {
                        playRingtone();
                        document.getElementById('reqCount').innerText = data.count;
                        lastCount = data.count;
                        
                        // Show a browser alert or reload page for the mechanic to see the new request
                        // For a better UX we just reload the page to fetch the new HTML block
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else if (data.count < lastCount) {
                        lastCount = data.count;
                        document.getElementById('reqCount').innerText = data.count;
                    }
                })
                .catch(err => console.error("Polling error:", err));
        }

        // Poll every 5 seconds
        setInterval(checkNewRequests, 5000);
    </script>
</body>
</html>
