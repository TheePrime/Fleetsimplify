<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mechanic') {
    header("Location: ../login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get mechanic's internal ID & status
$stmt = $pdo->prepare("SELECT id, availability, approval_status FROM mechanics WHERE user_id = ?");
$stmt->execute([$user_id]);
$mechanic        = $stmt->fetch();
$mechanic_id     = $mechanic['id'];
$availability    = $mechanic['availability'];
$approval_status = $mechanic['approval_status'];

// Fetch mechanic profile details from users table
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$userData = $userStmt->fetch();
$memberSince = isset($userData['created_at']) ? date('M Y', strtotime($userData['created_at'])) : 'N/A';

// Fetch Pending requests that have NO assigned mechanic
$stmtPending = $pdo->query("
    SELECT r.*, u.name as driver_name, u.phone as driver_phone
    FROM requests r
    JOIN users u ON r.driver_id = u.id
    WHERE r.status = 'Pending' AND r.mechanic_id IS NULL
    ORDER BY r.created_at DESC
");
$pendingRequests = $stmtPending->fetchAll();

// Fetch Mechanic's tasks
$stmtTasks = $pdo->prepare("
    SELECT r.*, u.name as driver_name, u.phone as driver_phone
    FROM requests r
    JOIN users u ON r.driver_id = u.id
    WHERE r.mechanic_id = ?
    ORDER BY r.created_at DESC
");
$stmtTasks->execute([$mechanic_id]);
$myTasks = $stmtTasks->fetchAll();

// Stats
$activeTasksCount    = count(array_filter($myTasks, fn($t) => !in_array($t['status'], ['Completed', 'Cancelled'])));
$completedTasksCount = count(array_filter($myTasks, fn($t) => $t['status'] === 'Completed'));
$newRequestsCount    = count($pendingRequests);

// Active tab
$activeTab = $_GET['tab'] ?? 'requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mechanic Dashboard – Nyamato Roadside</title>
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
            background: linear-gradient(135deg, #0f766e 0%, #0369a1 50%, #1e40af 100%);
            position: relative;
        }

        /* wrench icon watermark on cover */
        .profile-cover::after {
            content: "🔧";
            position: absolute;
            right: 2rem;
            bottom: 1rem;
            font-size: 4rem;
            opacity: 0.15;
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

        .profile-meta { flex: 1; padding-top: 0.5rem; }

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
        .badge-green  { background: #d1fae5; color: #059669; }
        .badge-red    { background: #fee2e2; color: #dc2626; }

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

        .count-pill {
            background: var(--danger);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 9999px;
            margin-left: auto;
        }

        /* ===== REQUEST / TASK ITEMS ===== */
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

        .request-time {
            font-size: 0.73rem;
            color: #94a3b8;
            text-align: right;
            margin-top: 0.5rem;
        }

        .request-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.7rem;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ===== FORM ELEMENTS ===== */
        select {
            padding: 0.55rem 0.8rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.85rem;
            background: #fafafa;
            color: var(--text);
            transition: border-color 0.2s;
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: var(--teal);
            background: var(--white);
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 0.55rem 1.1rem;
            border-radius: 8px;
            border: none;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary { background: var(--navy);    color: white; }
        .btn-primary:hover { background: #0f3460; }

        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }

        .btn-info    { background: #0ea5e9;        color: white; }
        .btn-info:hover { background: #0284c7; }

        .btn-gray    { background: #94a3b8;        color: white; }
        .btn-gray:hover { background: #64748b; }

        .btn-orange  { background: var(--orange);  color: white; }
        .btn-orange:hover { background: #e05a00; }

        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 6px;
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
        .alert-warning { background: #fffbeb; color: #d97706; border: 1px solid #fcd34d; }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 2.5rem;
            color: var(--muted);
        }
        .empty-state .emoji { font-size: 2.5rem; margin-bottom: 0.75rem; }
        .empty-state p { font-size: 0.9rem; }

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
        <span>🔧</span> Nyamato
    </a>

    <nav class="sidebar-nav">
        <a href="dashboard.php?tab=requests" class="nav-item <?= $activeTab === 'requests' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
            New Requests
            <?php if($newRequestsCount > 0): ?>
                <span class="badge-notif" id="reqCount"><?= $newRequestsCount ?></span>
            <?php else: ?>
                <span class="badge-notif" id="reqCount" style="display:none;"><?= $newRequestsCount ?></span>
            <?php endif; ?>
        </a>
        <a href="dashboard.php?tab=tasks" class="nav-item <?= $activeTab === 'tasks' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
            My Tasks
            <?php if($activeTasksCount > 0): ?>
                <span class="badge-notif"><?= $activeTasksCount ?></span>
            <?php endif; ?>
        </a>
        <a href="feedback.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
            Feedback
        </a>
        <a href="profile.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Business Profile
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
        <span class="topbar-title">Mechanic Dashboard</span>
        <div class="topbar-right">
            <?php if($newRequestsCount > 0): ?>
                <span class="badge-notif"><?= $newRequestsCount ?> new</span>
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
                    <span class="badge badge-orange">🔧 Mechanic</span>
                    <?php if($approval_status === 'APPROVED'): ?>
                        <span class="badge badge-green">✅ Approved</span>
                        <span class="badge badge-navy">📡 <?= htmlspecialchars($availability) ?></span>
                    <?php else: ?>
                        <span class="badge badge-red">⏳ Pending Approval</span>
                    <?php endif; ?>
                    <span class="badge badge-navy">✅ <?= $completedTasksCount ?> Completed</span>
                </div>
            </div>
        </div>
        <div class="profile-tabs">
            <a href="dashboard.php?tab=requests"
               class="tab-btn <?= $activeTab === 'requests' ? 'active' : '' ?>">New Requests</a>
            <a href="dashboard.php?tab=tasks"
               class="tab-btn <?= $activeTab === 'tasks' ? 'active' : '' ?>">My Tasks</a>
            <a href="feedback.php" class="tab-btn">Feedback</a>
            <a href="profile.php" class="tab-btn">Business Profile</a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon orange">🔔</div>
                <div>
                    <div class="stat-num" id="statNewReqs"><?= $newRequestsCount ?></div>
                    <div class="stat-label">New Requests</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teal">⚙️</div>
                <div>
                    <div class="stat-num"><?= $activeTasksCount ?></div>
                    <div class="stat-label">Active Tasks</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon navy">✅</div>
                <div>
                    <div class="stat-num"><?= $completedTasksCount ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if ($approval_status !== 'APPROVED'): ?>
            <div class="alert alert-warning" style="margin-bottom:0;">
                ⚠️ Your account is currently <strong>Pending Approval</strong> by the admin. You cannot view or accept new requests until approved.
            </div>
        <?php endif; ?>

        <!-- ===== NEW REQUESTS TAB ===== -->
        <?php if ($activeTab === 'requests'): ?>
        <div class="content-grid">

            <!-- New Breakdown Requests -->
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                    New Breakdown Requests
                    <span class="count-pill" id="reqCount2"><?= $newRequestsCount ?></span>
                </div>

                <?php if ($approval_status !== 'APPROVED'): ?>
                    <div class="empty-state">
                        <div class="emoji">🔒</div>
                        <p>Account not yet approved. Check back after admin review.</p>
                    </div>
                <?php elseif (count($pendingRequests) > 0): ?>
                    <?php foreach($pendingRequests as $req): ?>
                        <div class="request-item">
                            <div class="request-header">
                                <strong style="font-size: 0.9rem;">🚗 <?= htmlspecialchars($req['vehicle_type']) ?></strong>
                                <span class="status-badge status-Pending">Pending</span>
                            </div>
                            <div class="request-detail">👤 Driver: <?= htmlspecialchars($req['driver_name']) ?> · <?= htmlspecialchars($req['driver_phone']) ?></div>
                            <div class="request-detail">📍 <?= htmlspecialchars($req['location_address']) ?></div>
                            <div class="request-detail">🔧 <?= htmlspecialchars($req['problem_description']) ?></div>
                            <div class="request-actions">
                                <form action="accept_request.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <button type="submit" class="btn btn-success">✅ Accept Job</button>
                                </form>
                            </div>
                            <div class="request-time"><?= date('M d, Y h:i A', strtotime($req['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="emoji">📭</div>
                        <p>No new breakdown requests at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Active Tasks (preview) -->
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                    Active Tasks
                </div>

                <?php
                $activeTasks = array_filter($myTasks, fn($t) => !in_array($t['status'], ['Completed', 'Cancelled']));
                $activeTasks = array_values($activeTasks);
                if (count($activeTasks) > 0):
                    foreach(array_slice($activeTasks, 0, 3) as $task):
                        $sClass = str_replace(' ', '', $task['status']);
                ?>
                    <div class="request-item">
                        <div class="request-header">
                            <strong style="font-size: 0.9rem;">Order #<?= $task['id'] ?></strong>
                            <span class="status-badge status-<?= $sClass ?>"><?= htmlspecialchars($task['status']) ?></span>
                        </div>
                        <div class="request-detail">👤 <?= htmlspecialchars($task['driver_name']) ?> · <?= htmlspecialchars($task['driver_phone']) ?></div>
                        <div class="request-detail">📍 <?= htmlspecialchars($task['location_address']) ?></div>
                        <div class="request-actions">
                            <a href="dashboard.php?tab=tasks" class="btn btn-sm btn-info" style="text-decoration:none;">Manage →</a>
                            <a href="../chat/chat_ui.php?request_id=<?= $task['id'] ?>" class="btn btn-sm btn-gray" style="text-decoration:none;">💬 Chat</a>
                        </div>
                        <div class="request-time"><?= date('M d, Y h:i A', strtotime($task['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
                    <?php if(count($activeTasks) > 3): ?>
                        <a href="dashboard.php?tab=tasks" class="btn btn-sm btn-gray" style="text-decoration:none;">View All Tasks →</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="emoji">⚙️</div>
                        <p>No active tasks right now.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        <?php endif; ?>

        <!-- ===== MY TASKS TAB ===== -->
        <?php if ($activeTab === 'tasks'): ?>
        <div class="card">
            <div class="card-title">
                <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                All My Assignments
            </div>

            <?php if (count($myTasks) > 0): ?>
                <?php foreach($myTasks as $task):
                    $sClass = str_replace(' ', '', $task['status']);
                ?>
                    <div class="request-item">
                        <div class="request-header">
                            <strong style="font-size: 0.9rem;">Order #<?= $task['id'] ?> – <?= htmlspecialchars($task['vehicle_type']) ?></strong>
                            <span class="status-badge status-<?= $sClass ?>"><?= htmlspecialchars($task['status']) ?></span>
                        </div>
                        <div class="request-detail">👤 <?= htmlspecialchars($task['driver_name']) ?> · <?= htmlspecialchars($task['driver_phone']) ?></div>
                        <div class="request-detail">📍 <?= htmlspecialchars($task['location_address']) ?></div>
                        <div class="request-detail">🔧 <?= htmlspecialchars($task['problem_description']) ?></div>

                        <?php if($task['status'] !== 'Completed' && $task['status'] !== 'Cancelled'): ?>
                            <div class="request-actions">
                                <form action="update_task.php" method="POST" style="margin:0; display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                                    <input type="hidden" name="request_id" value="<?= $task['id'] ?>">
                                    <select name="status">
                                        <option value="Accepted"    <?= $task['status'] == 'Accepted'    ? 'selected' : '' ?>>Accepted (On the way)</option>
                                        <option value="In Progress" <?= $task['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress (Repairing)</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                    <button type="submit" class="btn btn-info">Update Status</button>
                                    <a href="../chat/chat_ui.php?request_id=<?= $task['id'] ?>" class="btn btn-gray" style="text-decoration:none;">💬 Chat</a>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="request-actions">
                                <a href="../chat/chat_ui.php?request_id=<?= $task['id'] ?>" class="btn btn-sm btn-gray" style="text-decoration:none;">💬 View Chat History</a>
                            </div>
                        <?php endif; ?>

                        <div class="request-time"><?= date('M d, Y h:i A', strtotime($task['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="emoji">📋</div>
                    <p>You have no assigned tasks yet.</p>
                    <a href="dashboard.php?tab=requests" class="btn btn-sm btn-primary" style="margin-top: 1rem; text-decoration:none;">View New Requests</a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /content-area -->
</main>

<script>
    // ===== Ringtone / Notification Polling =====
    let lastCount = <?= $newRequestsCount ?>;
    const approvalStatus = "<?= $approval_status ?>";

    function playRingtone() {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gainNode = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
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
                const pill  = document.getElementById('reqCount');
                const pill2 = document.getElementById('reqCount2');
                const stat  = document.getElementById('statNewReqs');

                if (data.count > lastCount) {
                    playRingtone();
                    lastCount = data.count;
                    if (pill)  { pill.innerText  = data.count; pill.style.display  = ''; }
                    if (pill2) { pill2.innerText = data.count; }
                    if (stat)  { stat.innerText  = data.count; }
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    lastCount = data.count;
                    if (pill)  { pill.innerText  = data.count; if(data.count == 0) pill.style.display = 'none'; }
                    if (pill2) { pill2.innerText = data.count; }
                    if (stat)  { stat.innerText  = data.count; }
                }
            })
            .catch(err => console.error("Polling error:", err));
    }

    setInterval(checkNewRequests, 5000);
</script>
</body>
</html>
