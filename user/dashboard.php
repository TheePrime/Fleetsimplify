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

// Fetch user data
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$userData = $userStmt->fetch();

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

// Determine active tab from query string
$activeTab = $_GET['tab'] ?? 'home';

// Count requests
$totalRequests  = count($requests);
$pendingCount   = count(array_filter($requests, fn($r) => $r['status'] === 'Pending'));
$completedCount = count(array_filter($requests, fn($r) => $r['status'] === 'Completed'));

// Member since
$memberSince = isset($userData['created_at']) ? date('M Y', strtotime($userData['created_at'])) : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Nyamato Roadside</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:      #0A2540;
            --orange:    #FF6B00;
            --orange-lt: #fff3e8;
            --teal:      #0d9488;
            --teal-dk:   #0f766e;
            --bg:        #f1f5f9;
            --white:     #ffffff;
            --text:      #0f172a;
            --muted:     #64748b;
            --border:    #e2e8f0;
            --success:   #10b981;
            --warning:   #f59e0b;
            --danger:    #ef4444;
            --sidebar-w: 220px;
        }

        body {
            font-family: 'Poppins', 'Inter', 'Roboto', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--white);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            box-shadow: 2px 0 8px rgba(0,0,0,0.04);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 1.4rem 1.5rem;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--orange);
            border-bottom: 1px solid var(--border);
            text-decoration: none;
        }
        .sidebar-logo span { font-size: 1.6rem; }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1.5rem;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            cursor: pointer;
        }

        .nav-item:hover {
            background: var(--orange-lt);
            color: var(--orange);
            border-left-color: var(--orange);
        }

        .nav-item.active {
            background: var(--orange-lt);
            color: var(--orange);
            border-left-color: var(--orange);
            font-weight: 600;
        }

        .nav-item svg {
            width: 18px; height: 18px;
            flex-shrink: 0;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .sidebar-bottom {
            padding: 1rem 0;
            border-top: 1px solid var(--border);
        }

        /* ===== MAIN ===== */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .avatar-sm {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--teal), var(--teal-dk));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 0.85rem;
            text-transform: uppercase;
        }

        .badge-notif {
            background: var(--orange);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 9999px;
        }

        /* ===== PROFILE HEADER ===== */
        .profile-header {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            margin: 1.5rem 2rem 0;
        }

        .profile-cover {
            height: 160px;
            background: linear-gradient(135deg, #0d9488 0%, #0891b2 50%, #1d4ed8 100%);
            position: relative;
        }

        .profile-info-row {
            display: flex;
            align-items: flex-end;
            gap: 1.25rem;
            padding: 0 1.75rem 1.25rem;
            position: relative;
        }

        .profile-avatar {
            width: 90px; height: 90px;
            border-radius: 50%;
            border: 4px solid var(--white);
            background: linear-gradient(135deg, var(--teal), var(--navy));
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 700; color: white;
            text-transform: uppercase;
            margin-top: -45px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .profile-meta {
            flex: 1;
            padding-top: 0.5rem;
        }

        .profile-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text);
        }

        .profile-sub {
            font-size: 0.82rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .profile-badges {
            display: flex;
            gap: 0.6rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 9999px;
        }

        .badge-teal   { background: #ccfbf1; color: #0f766e; }
        .badge-orange { background: var(--orange-lt); color: var(--orange); }
        .badge-navy   { background: #e0e7ff; color: #3730a3; }

        /* ===== TABS ===== */
        .profile-tabs {
            display: flex;
            gap: 0;
            border-top: 1px solid var(--border);
            margin: 0 1.75rem;
        }

        .tab-btn {
            padding: 0.85rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--muted);
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .tab-btn:hover { color: var(--text); }
        .tab-btn.active {
            color: var(--text);
            border-bottom-color: var(--text);
            font-weight: 600;
        }

        /* ===== CONTENT AREA ===== */
        .content-area {
            padding: 1.5rem 2rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        /* ===== CARDS ===== */
        .card {
            background: var(--white);
            border-radius: 14px;
            padding: 1.5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title svg {
            width: 18px; height: 18px;
            stroke: var(--orange);
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ===== STATS ROW ===== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }

        .stat-icon.teal   { background: #ccfbf1; }
        .stat-icon.orange { background: var(--orange-lt); }
        .stat-icon.navy   { background: #e0e7ff; }

        .stat-num {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.78rem;
            color: var(--muted);
            margin-top: 3px;
        }

        /* ===== FORM ===== */
        .form-group { margin-bottom: 1rem; }

        label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--muted);
        }

        input[type="text"], textarea, select {
            width: 100%;
            padding: 0.7rem 0.9rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            background: #fafafa;
            color: var(--text);
            transition: border-color 0.2s;
        }

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--teal);
            background: var(--white);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 0.7rem 1.4rem;
            border-radius: 8px;
            border: none;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--navy);
            color: white;
            width: 100%;
        }
        .btn-primary:hover { background: #0f3460; }
        .btn-primary:disabled { background: #94a3b8; cursor: not-allowed; }

        .btn-warning {
            background: var(--warning);
            color: white;
            width: 100%;
        }
        .btn-warning:hover { background: #d97706; }

        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.78rem;
            border-radius: 6px;
        }

        .btn-success { background: var(--success); color: white; }
        .btn-info    { background: #0ea5e9; color: white; }
        .btn-gray    { background: #94a3b8; color: white; }

        /* ===== REQUEST ITEMS ===== */
        .request-item {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem 1.1rem;
            margin-bottom: 0.85rem;
            transition: box-shadow 0.2s;
        }

        .request-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.07); }

        .request-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .status-Pending    { background: #fef3c7; color: #d97706; }
        .status-Accepted   { background: #e0e7ff; color: #4338ca; }
        .status-InProgress { background: #dbeafe; color: #1d4ed8; }
        .status-Completed  { background: #d1fae5; color: #059669; }
        .status-Cancelled  { background: #fee2e2; color: #dc2626; }

        .request-detail {
            font-size: 0.82rem;
            color: var(--muted);
            margin: 2px 0;
        }

        .mechanic-box {
            background: #e0f2fe;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.82rem;
            margin-top: 0.6rem;
        }

        .request-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.7rem;
            flex-wrap: wrap;
        }

        .request-time {
            font-size: 0.73rem;
            color: #94a3b8;
            text-align: right;
            margin-top: 0.5rem;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .alert-success { background: #ecfdf5; color: #059669; border: 1px solid #6ee7b7; }
        .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 2.5rem;
            color: var(--muted);
        }
        .empty-state .emoji { font-size: 2.5rem; margin-bottom: 0.75rem; }
        .empty-state p { font-size: 0.9rem; }

        /* ===== HIDDEN TABS ===== */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .content-grid { grid-template-columns: 1fr; }
            .stats-row    { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .stats-row { grid-template-columns: 1fr; }
            .profile-header { margin: 1rem; }
            .content-area { padding: 1rem; }
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar">
    <a href="dashboard.php" class="sidebar-logo">
        <span>🚛</span> Nyamato
    </a>

    <nav class="sidebar-nav">
        <a href="dashboard.php?tab=home" class="nav-item <?= $activeTab === 'home' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z"/><path d="M9 21V12h6v9"/></svg>
            Home
        </a>
        <a href="dashboard.php?tab=requests" class="nav-item <?= $activeTab === 'requests' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
            My Requests
            <?php if($pendingCount > 0): ?>
                <span class="badge-notif"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
        <a href="payment.php" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            Payment
        </a>
        <a href="profile.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            My Profile
        </a>
        <a href="../chat/chat_ui.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            Messages
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="../logout.php" class="nav-item" style="color: #ef4444;">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            Logout
        </a>
    </div>
</aside>

<!-- ===== MAIN ===== -->
<main class="main">

    <!-- Topbar -->
    <div class="topbar">
        <span class="topbar-title">Dashboard</span>
        <div class="topbar-right">
            <?php if($pendingCount > 0): ?>
                <span class="badge-notif"><?= $pendingCount ?> pending</span>
            <?php endif; ?>
            <div class="avatar-sm"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
        </div>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-cover"></div>
        <div class="profile-info-row">
            <div class="profile-avatar"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
            <div class="profile-meta">
                <div class="profile-name"><?= htmlspecialchars($user_name) ?></div>
                <div class="profile-sub"><?= htmlspecialchars($userData['email'] ?? '') ?></div>
                <div class="profile-badges">
                    <span class="badge badge-teal">🗓 Member since <?= $memberSince ?></span>
                    <span class="badge badge-orange">🚗 Driver</span>
                    <span class="badge badge-navy">✅ <?= $completedCount ?> Completed</span>
                </div>
            </div>
        </div>
        <div class="profile-tabs">
            <a href="dashboard.php?tab=home"
               class="tab-btn <?= $activeTab === 'home' ? 'active' : '' ?>">Home</a>
            <a href="dashboard.php?tab=requests"
               class="tab-btn <?= $activeTab === 'requests' ? 'active' : '' ?>">My Requests</a>
            <a href="payment.php" class="tab-btn">Payment</a>
            <a href="profile.php" class="tab-btn">My Profile</a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon teal">📋</div>
                <div>
                    <div class="stat-num"><?= $totalRequests ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">⏳</div>
                <div>
                    <div class="stat-num"><?= $pendingCount ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon navy">✅</div>
                <div>
                    <div class="stat-num"><?= $completedCount ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
        </div>

        <!-- HOME TAB -->
        <?php if($activeTab === 'home'): ?>
        <div class="content-grid">

            <!-- Report Breakdown Form -->
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    Report a Breakdown
                </div>

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
                        <label for="location_address">Location Address</label>
                        <input type="text" id="location_address" name="location_address" required
                               placeholder="e.g. Mombasa Road, near Total Petrol Station">
                    </div>

                    <div class="form-group">
                        <label for="problem_description">Describe the Problem</label>
                        <textarea id="problem_description" name="problem_description" rows="3" required
                                  placeholder="e.g. Flat tyre on front left side."></textarea>
                    </div>

                    <div class="form-group">
                        <button type="button" id="btnLocation" class="btn btn-warning" style="margin-bottom: 8px;">
                            <span id="locStatus">📍 Get My GPS Location</span>
                        </button>
                        <small style="display:block; color: var(--muted); font-size: 0.78rem;">Share your GPS location before submitting.</small>
                    </div>

                    <button type="submit" id="submitBtn" class="btn btn-primary" disabled>Submit Request</button>
                </form>
            </div>

            <!-- Recent Requests (latest 3) -->
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                    Recent Requests
                </div>

                <?php
                $recent = array_slice($requests, 0, 3);
                if (count($recent) > 0):
                    foreach($recent as $req):
                        $statusClass = str_replace(' ', '', $req['status']);
                ?>
                    <div class="request-item">
                        <div class="request-header">
                            <strong style="font-size: 0.9rem;"><?= htmlspecialchars($req['vehicle_type']) ?></strong>
                            <span class="status-badge status-<?= $statusClass ?>"><?= htmlspecialchars($req['status']) ?></span>
                        </div>
                        <div class="request-detail">📍 <?= htmlspecialchars($req['location_address']) ?></div>
                        <div class="request-detail">🔧 <?= htmlspecialchars(substr($req['problem_description'], 0, 60)) ?>...</div>
                        <div class="request-time"><?= date('M d, Y h:i A', strtotime($req['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>

                <a href="dashboard.php?tab=requests" class="btn btn-sm btn-gray" style="margin-top: 0.5rem; text-decoration: none;">View All Requests →</a>

                <?php else: ?>
                    <div class="empty-state">
                        <div class="emoji">🔧</div>
                        <p>No requests yet. Report a breakdown to get started.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        <?php endif; ?>

        <!-- MY REQUESTS TAB -->
        <?php if($activeTab === 'requests'): ?>
        <div class="card">
            <div class="card-title">
                <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                All My Requests
            </div>

            <?php if (count($requests) > 0): ?>
                <?php foreach($requests as $req):
                    $statusClass = str_replace(' ', '', $req['status']);
                ?>
                    <div class="request-item">
                        <div class="request-header">
                            <strong style="font-size: 0.9rem;"><?= htmlspecialchars($req['vehicle_type']) ?></strong>
                            <span class="status-badge status-<?= $statusClass ?>"><?= htmlspecialchars($req['status']) ?></span>
                        </div>
                        <div class="request-detail">📍 <?= htmlspecialchars($req['location_address']) ?></div>
                        <div class="request-detail">🔧 <?= htmlspecialchars($req['problem_description']) ?></div>

                        <?php if ($req['mechanic_name']): ?>
                            <div class="mechanic-box">
                                <strong>Mechanic:</strong>
                                <?php
                                if (!empty($req['business_name'])) {
                                    echo htmlspecialchars($req['business_name']) . " (" . htmlspecialchars($req['mechanic_name']) . ")";
                                } else {
                                    echo htmlspecialchars($req['mechanic_name']);
                                }
                                ?><br>
                                <strong>Phone:</strong> <?= htmlspecialchars($req['mechanic_phone']) ?>
                            </div>
                            <div class="request-actions">
                                <?php if ($req['status'] === 'Completed'): ?>
                                    <a href="rate.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-success" style="text-decoration: none;">⭐ Rate Service</a>
                                <?php else: ?>
                                    <a href="track.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-info" style="text-decoration: none;">📍 Track Mechanic</a>
                                <?php endif; ?>
                                <a href="../chat/chat_ui.php?request_id=<?= $req['id'] ?>" class="btn btn-sm btn-gray" style="text-decoration: none;">💬 Chat</a>
                            </div>
                        <?php else: ?>
                            <div class="request-detail" style="color: var(--warning); margin-top: 0.5rem;">⏳ Searching for nearby mechanics...</div>
                        <?php endif; ?>

                        <div class="request-time"><?= date('M d, Y h:i A', strtotime($req['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="empty-state">
                    <div class="emoji">📋</div>
                    <p>You have no previous requests.</p>
                    <a href="dashboard.php?tab=home" class="btn btn-sm btn-primary" style="margin-top: 1rem; text-decoration: none;">Report a Breakdown</a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /content-area -->
</main>

<script>
    const btnLocation = document.getElementById('btnLocation');
    const locStatus   = document.getElementById('locStatus');
    const submitBtn   = document.getElementById('submitBtn');
    const latInput    = document.getElementById('location_lat');
    const lngInput    = document.getElementById('location_lng');

    if (btnLocation) {
        btnLocation.addEventListener('click', function () {
            if ("geolocation" in navigator) {
                locStatus.innerText = "Locating...";
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        latInput.value = position.coords.latitude;
                        lngInput.value = position.coords.longitude;
                        btnLocation.style.background = "#10b981";
                        locStatus.innerText = "✅ Location Captured!";
                        submitBtn.removeAttribute('disabled');
                    },
                    function (error) {
                        locStatus.innerText = "❌ Location failed. Enable GPS.";
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            } else {
                locStatus.innerText = "Geolocation not supported.";
            }
        });
    }
</script>
</body>
</html>
