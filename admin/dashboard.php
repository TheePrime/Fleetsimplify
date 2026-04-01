<?php
session_start();
require_once '../config/db.php';

// Protect route
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch pending mechanics
$stmtPending = $pdo->query("
    SELECT m.*, u.name, u.email, u.phone 
    FROM mechanics m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.approval_status = 'PENDING APPROVAL'
");
$pendingMechanics = $stmtPending->fetchAll();

// Fetch all requests
$stmtReqs = $pdo->query("
    SELECT r.*, du.name as driver_name, mu.name as mechanic_name 
    FROM requests r
    JOIN users du ON r.driver_id = du.id
    LEFT JOIN mechanics m ON r.mechanic_id = m.id
    LEFT JOIN users mu ON m.user_id = mu.id
    ORDER BY r.created_at DESC
    LIMIT 20
");
$recentRequests = $stmtReqs->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Roadside Assistance</title>
    <style>
        :root {
            --primary: #2563eb;
            --bg-color: #f8fafc;
            --form-bg: #ffffff;
            --text-color: #0f172a;
            --border-color: #e2e8f0;
            --danger: #ef4444;
            --success: #10b981;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); margin: 0; color: var(--text-color); }
        .navbar { background-color: var(--form-bg); padding: 1rem 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar h2 { margin: 0; color: var(--primary); }
        .navbar a { text-decoration: none; color: var(--danger); font-weight: 600; }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; display: grid; gap: 2rem; }
        .card { background-color: var(--form-bg); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card h3 { margin-top: 0; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; }

        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { background-color: #f1f5f9; font-weight: 600; }
        
        .btn { padding: 0.5rem 1rem; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.85rem;}
        .btn-approve { background-color: var(--success); }
        .btn-reject { background-color: var(--danger); }
        
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-success { background-color: #ecfdf5; color: #059669; border: 1px solid #6ee7b7; }
        
        .status-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .status-Pending { background-color: #fef3c7; color: #d97706; }
        .status-Accepted { background-color: #e0e7ff; color: #4338ca; }
        .status-InProgress { background-color: #dbeafe; color: #1d4ed8; }
        .status-Completed { background-color: #d1fae5; color: #059669; }
    </style>
</head>
<body>

    <div class="navbar">
        <h2>Admin Dashboard</h2>
        <div>
            Welcome, Admin | <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Pending Mechanic Approvals</h3>
            <?php if (count($pendingMechanics) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th>License</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pendingMechanics as $m): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['name']); ?></td>
                                <td><?php echo htmlspecialchars($m['email']); ?></td>
                                <td><?php echo htmlspecialchars($m['phone']); ?></td>
                                <td><?php echo htmlspecialchars($m['service_location']); ?></td>
                                <td><?php echo htmlspecialchars($m['license_number']); ?></td>
                                <td>
                                    <form action="approve_mechanic.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="mechanic_id" value="<?php echo $m['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-approve">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No pending mechanics found.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Recent Breakdown Requests</h3>
            <table>
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th>Vehicle</th>
                        <th>Problem</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recentRequests as $req): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($req['driver_name']); ?></td>
                            <td><?php echo htmlspecialchars($req['vehicle_type']); ?></td>
                            <td><?php echo htmlspecialchars(substr($req['problem_description'], 0, 30)) . '...'; ?></td>
                            <td>
                                <?php $sClass = str_replace(' ', '', $req['status']); ?>
                                <span class="status-badge status-<?php echo $sClass; ?>"><?php echo htmlspecialchars($req['status']); ?></span>
                            </td>
                            <td><?php echo $req['mechanic_name'] ? htmlspecialchars($req['mechanic_name']) : 'Unassigned'; ?></td>
                            <td><?php echo date('M d', strtotime($req['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
