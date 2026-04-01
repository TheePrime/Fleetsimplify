<?php
session_start();
require_once '../config/db.php';

// Protect route
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch user's request history
$stmt = $pdo->prepare("
    SELECT r.*, m.name as mechanic_name, m.phone as mechanic_phone, mech.business_name 
    FROM requests r 
    LEFT JOIN mechanics mech ON r.mechanic_id = mech.id
    LEFT JOIN users m ON mech.user_id = m.id
    WHERE r.driver_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Roadside Assistance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-color: #f8fafc;
            --form-bg: #ffffff;
            --text-color: #0f172a;
            --border-color: #e2e8f0;
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
        }

        body {
            font-family: 'Poppins', 'Inter', 'Roboto', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            color: var(--text-color);
        }

        .navbar {
            background: #0A2540;           /* Deep Navy Blue */
            color: white;
            padding: 1rem 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.6rem;
            font-weight: 700;
            color: #FF6B00;               /* Orange accent */
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: #E0E7FF;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #FF6B00;
            color: white;
            transform: translateY(-2px);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .welcome {
            color: #A5B4FC;
            font-size: 1rem;
            font-weight: 500;
        }

        .logout-btn {
            background: #E53935;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #C62828;
            transform: translateY(-2px);
        }

        /* Active link styling */
        .nav-links a.active {
            background: #FF6B00;
            color: white;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1rem 5%;
                flex-wrap: wrap;
            }
            .nav-links {
                gap: 1rem;
                margin-top: 1rem;
                width: 100%;
            }
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .card {
            background-color: var(--form-bg);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .card h3 {
            margin-top: 0;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="text"], textarea, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            box-sizing: border-box;
            background-color: #fafafa;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn:hover { background-color: var(--primary-hover); }
        .btn:disabled { background-color: #94a3b8; cursor: not-allowed; }

        .request-item {
            border: 1px solid var(--border-color);
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            background-color: #f8fafc;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-Pending { background-color: #fef3c7; color: #d97706; }
        .status-Accepted { background-color: #e0e7ff; color: #4338ca; }
        .status-InProgress { background-color: #dbeafe; color: #1d4ed8; }
        .status-Completed { background-color: #d1fae5; color: #059669; }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .alert-success { background-color: #ecfdf5; color: #059669; border: 1px solid #6ee7b7; }
        .alert-error { background-color: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }

        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="navbar">
        <!-- Left Side: Logo + Title -->
        <div class="logo">
            <span>🚛</span>
            Nyamato
        </div>

        <!-- Center Navigation Links -->
        <ul class="nav-links">
            <li><a href="dashboard.php" class="active">Find Services</a></li>
            <li><a href="dashboard.php">My Requests</a></li>
            <li><a href="payment.php">Payment</a></li>
            <li><a href="profile.php">My Profile</a></li>
        </ul>

        <!-- Right Side: Welcome + Logout -->
        <div class="nav-right">
            <span class="welcome">
                Welcome, <strong><?php echo htmlspecialchars($user_name); ?></strong>
            </span>
            <button onclick="window.location.href='../logout.php'" class="logout-btn">
                Logout
            </button>
        </div>
    </div>

    <div class="container">
        
        <!-- Request Form -->
        <div class="card">
            <h3>Report a Breakdown</h3>
            
            <?php
            if (isset($_SESSION['success'])) {
                echo "<div class='alert alert-success'>" . $_SESSION['success'] . "</div>";
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo "<div class='alert alert-error'>" . $_SESSION['error'] . "</div>";
                unset($_SESSION['error']);
            }
            ?>

            <form action="request_action.php" method="POST" id="requestForm">
                <input type="hidden" name="location_lat" id="location_lat">
                <input type="hidden" name="location_lng" id="location_lng">

                <div class="form-group">
                    <label for="vehicle_type">Vehicle Type</label>
                    <select id="vehicle_type" name="vehicle_type" required>
                        <option value="">Select Vehicle</option>
                        <option value="Sedan">Sedan</option>
                        <option value="SUV">SUV</option>
                        <option value="Truck">Truck</option>
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="Van">Van</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location_address">Location Address (Be specific)</label>
                    <input type="text" id="location_address" name="location_address" required placeholder="e.g. 1-95 North, Exit 4">
                </div>

                <div class="form-group">
                    <label for="problem_description">Describe the Problem</label>
                    <textarea id="problem_description" name="problem_description" rows="4" required placeholder="e.g. Flat tire on the front left."></textarea>
                </div>

                <div class="form-group" style="text-align: center;">
                    <button type="button" id="btnLocation" class="btn" style="background-color: var(--warning); margin-bottom: 10px;">
                        <span id="locStatus">📍 Get My GPS Location</span>
                    </button>
                    <small style="display:block; color: #64748b; margin-bottom: 10px;">Please share location before submitting.</small>
                </div>

                <button type="submit" id="submitBtn" class="btn" disabled>Submit Request</button>
            </form>
        </div>

        <!-- Request History -->
        <div class="card">
            <h3>Your Requests</h3>
            
            <?php if (count($requests) > 0): ?>
                <div style="max-height: 500px; overflow-y: auto;">
                    <?php foreach($requests as $req): ?>
                        <div class="request-item">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <strong><?php echo htmlspecialchars($req['vehicle_type']); ?></strong>
                                <?php 
                                    $statusClass = str_replace(' ', '', $req['status']); 
                                ?>
                                <span class="status-badge status-<?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($req['status']); ?>
                                </span>
                            </div>
                            <p style="margin: 0.5rem 0; font-size: 0.9rem; color: #475569;">
                                📍 <?php echo htmlspecialchars($req['location_address']); ?>
                            </p>
                            <p style="margin: 0.5rem 0; font-size: 0.9rem;">
                                🔧 Problem: <?php echo htmlspecialchars($req['problem_description']); ?>
                            </p>
                            
                            <?php if ($req['mechanic_name']): ?>
                                <div style="background: #e0f2fe; padding: 0.5rem; border-radius: 4px; font-size: 0.85rem; margin-top: 0.5rem;">
                                    <strong>Assigned Mechanic:</strong> 
                                    <?php 
                                        if (!empty($req['business_name'])) {
                                            echo htmlspecialchars($req['business_name']) . " (" . htmlspecialchars($req['mechanic_name']) . ")";
                                        } else {
                                            echo htmlspecialchars($req['mechanic_name']);
                                        }
                                    ?><br>
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($req['mechanic_phone']); ?>
                                </div>
                                <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                                    <?php if ($req['status'] === 'Completed'): ?>
                                        <a href="rate.php?id=<?php echo $req['id']; ?>" class="btn" style="background-color: var(--success); padding: 0.25rem 0.5rem; font-size: 0.85rem; text-align: center; text-decoration: none;">Rate Service</a>
                                    <?php else: ?>
                                        <a href="track.php?id=<?php echo $req['id']; ?>" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.85rem; text-align: center; text-decoration: none;">Track Mechanic</a>
                                    <?php endif; ?>
                                    <a href="../chat/chat_ui.php?request_id=<?php echo $req['id']; ?>" class="btn" style="background-color: #64748b; padding: 0.25rem 0.5rem; font-size: 0.85rem; text-align: center; text-decoration: none;">Chat</a>
                                </div>
                            <?php else: ?>
                                <small style="color: var(--warning);">Searching for nearby mechanics...</small>
                            <?php endif; ?>
                            
                            <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #94a3b8; text-align: right;">
                                <?php echo date('M d, Y h:i A', strtotime($req['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--secondary);">You have no previous requests.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Geolocation Script -->
    <script>
        const btnLocation = document.getElementById('btnLocation');
        const locStatus = document.getElementById('locStatus');
        const submitBtn = document.getElementById('submitBtn');
        const latInput = document.getElementById('location_lat');
        const lngInput = document.getElementById('location_lng');

        btnLocation.addEventListener('click', function() {
            if ("geolocation" in navigator) {
                locStatus.innerText = "Locating...";
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        latInput.value = position.coords.latitude;
                        lngInput.value = position.coords.longitude;
                        
                        btnLocation.style.backgroundColor = "var(--success)";
                        locStatus.innerText = "✅ Location Captured!";
                        submitBtn.removeAttribute('disabled'); // Enable form
                    },
                    function(error) {
                        locStatus.innerText = "❌ Location failed. Please enable GPS.";
                        console.error("Error getting location:", error);
                        // Optional fallback: allow submission without GPS
                        // submitBtn.removeAttribute('disabled'); 
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            } else {
                locStatus.innerText = "Geolocation not supported.";
            }
        });
    </script>
</body>
</html>
