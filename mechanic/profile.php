<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mechanic') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmtUser = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

// Fetch mechanic data
$stmtMech = $pdo->prepare("SELECT * FROM mechanics WHERE user_id = ?");
$stmtMech->execute([$user_id]);
$mechanic = $stmtMech->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect variables
    $mechanic_name = $_POST['mechanic_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $business_name = $_POST['business_name'] ?? '';
    $operating_hours = $_POST['operating_hours'] ?? '';
    $service_location = $_POST['service_location'] ?? ''; // Address
    $city = $_POST['city'] ?? '';
    $services_offered = $_POST['services_offered'] ?? '';

    if (!empty($mechanic_name) && !empty($phone)) {
        // Update users table
        $updateUser = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $updateUser->execute([$mechanic_name, $phone, $user_id]);
        $user['name'] = $mechanic_name;
        $user['phone'] = $phone;
        $_SESSION['user_name'] = $mechanic_name; // update session name
    }

    // Update mechanics table
    $updateMech = $pdo->prepare("UPDATE mechanics SET business_name = ?, operating_hours = ?, service_location = ?, city = ?, services_offered = ? WHERE user_id = ?");
    $updateMech->execute([$business_name, $operating_hours, $service_location, $city, $services_offered, $user_id]);
    
    // Refresh mechanic data
    $stmtMech->execute([$user_id]);
    $mechanic = $stmtMech->fetch();
    
    $success = "Business Profile updated successfully.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Business Profile - Roadside Assistance</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; margin:0; padding:0; color: #0f172a; }
        .navbar { background-color: white; padding: 1rem 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand img { height: 56px; max-width: 190px; width: auto; display: block; }
        .navbar a { text-decoration: none; color: #ef4444; font-weight: 600; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group.full-width { grid-column: span 2; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        input[type="text"], input[type="email"], textarea { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; background-color: #fafafa; }
        input:disabled { background-color: #e2e8f0; color: #64748b; }
        .btn { padding: 0.75rem 1.5rem; background-color: #2563eb; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background-color: #1d4ed8; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-success { background-color: #ecfdf5; color: #059669; border: 1px solid #6ee7b7; }
        
        .status-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .status-PendingApproval { background-color: #fef3c7; color: #d97706; }
        .status-Approved { background-color: #d1fae5; color: #059669; }
        .status-Rejected { background-color: #fef2f2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand"><img src="../Images/logo.png" alt="FleetSimplify logo"></div>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="dashboard.php" style="color: #2563eb;">Back to Dashboard</a>
            <span>|</span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        
        <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem;">
            <h3 style="margin: 0;">Update Business Details</h3>
            <div>
                Account Status: 
                <?php 
                    $statClass = str_replace(' ', '', ucwords(strtolower($mechanic['approval_status'])));
                    $friendlyStat = ucwords(strtolower($mechanic['approval_status']));
                ?>
                <span class="status-badge status-<?php echo $statClass; ?>"><?php echo $friendlyStat; ?></span>
            </div>
        </div>

        <form action="" method="POST" class="form-grid">
            <div class="form-group">
                <label>Business Name</label>
                <input type="text" name="business_name" value="<?php echo htmlspecialchars((string)$mechanic['business_name']); ?>" placeholder="e.g. Mike's Auto Repair">
            </div>
            <div class="form-group">
                <label>Mechanic Name</label>
                <input type="text" name="mechanic_name" value="<?php echo htmlspecialchars((string)$user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Mobile</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars((string)$user['phone']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" value="<?php echo htmlspecialchars((string)$user['email']); ?>" disabled>
            </div>

            <div class="form-group full-width">
                <label>Service Description (e.g. Towing, Tire services, Brake repairs...)</label>
                <input type="text" name="services_offered" value="<?php echo htmlspecialchars((string)$mechanic['services_offered']); ?>" required placeholder="What services do you provide?">
            </div>

            <div class="form-group">
                <label>Available (Timelines)</label>
                <input type="text" name="operating_hours" value="<?php echo htmlspecialchars((string)$mechanic['operating_hours']); ?>" placeholder="e.g. 24/7 or 8 AM - 6 PM">
            </div>
            <div class="form-group">
                <label>License Number</label>
                <input type="text" value="<?php echo htmlspecialchars((string)$mechanic['license_number']); ?>" disabled>
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" name="service_location" value="<?php echo htmlspecialchars((string)$mechanic['service_location']); ?>" placeholder="e.g. 123 Main St">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars((string)$mechanic['city']); ?>" placeholder="e.g. Nairobi">
            </div>
            
            <div class="form-group full-width" style="margin-top: 1rem;">
                <button type="submit" class="btn">Update Profile</button>
            </div>
        </form>
    </div>
</body>
</html>
