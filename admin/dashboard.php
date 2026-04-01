<?php
session_start();
require_once '../config/db.php';

// Protect route
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// ── Core counts ──────────────────────────────────────────────────────────────
$totalUsers     = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalMechanics = $pdo->query("SELECT COUNT(*) FROM mechanics")->fetchColumn();
$totalRequests  = $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
$completedReqs  = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Completed'")->fetchColumn();
$pendingApprovalCount = $pdo->query("SELECT COUNT(*) FROM mechanics WHERE approval_status = 'PENDING APPROVAL'")->fetchColumn();

// ── Pending mechanic approvals ────────────────────────────────────────────────
$stmtPending = $pdo->query("
    SELECT m.*, u.name, u.email, u.phone
    FROM mechanics m
    JOIN users u ON m.user_id = u.id
    WHERE m.approval_status = 'PENDING APPROVAL'
    ORDER BY m.id DESC
");
$pendingMechanics = $stmtPending->fetchAll();

// ── Recent requests (table) ───────────────────────────────────────────────────
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

// ── CHART DATA ────────────────────────────────────────────────────────────────

// 1. Breakdowns by vehicle type (Pie)
$vehicleRows = $pdo->query("
    SELECT vehicle_type, COUNT(*) as cnt
    FROM requests
    GROUP BY vehicle_type
    ORDER BY cnt DESC
")->fetchAll();
$vehicleLabels = json_encode(array_column($vehicleRows, 'vehicle_type'));
$vehicleCounts = json_encode(array_map('intval', array_column($vehicleRows, 'cnt')));

// 2. Request status distribution (Doughnut)
$statusRows = $pdo->query("
    SELECT status, COUNT(*) as cnt
    FROM requests
    GROUP BY status
")->fetchAll();
$statusLabels = json_encode(array_column($statusRows, 'status'));
$statusCounts = json_encode(array_map('intval', array_column($statusRows, 'cnt')));

// 3. Requests over last 14 days (Line)
$dateRows = $pdo->query("
    SELECT DATE(created_at) as day, COUNT(*) as cnt
    FROM requests
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY day
    ORDER BY day ASC
")->fetchAll();
$dateLabels = json_encode(array_column($dateRows, 'day'));
$dateCounts = json_encode(array_map('intval', array_column($dateRows, 'cnt')));

// 4. Top 8 mechanics by completed jobs (Bar)
$mechRows = $pdo->query("
    SELECT u.name, COUNT(r.id) as cnt
    FROM requests r
    JOIN mechanics m ON r.mechanic_id = m.id
    JOIN users u ON m.user_id = u.id
    WHERE r.status = 'Completed'
    GROUP BY m.id, u.name
    ORDER BY cnt DESC
    LIMIT 8
")->fetchAll();
$mechLabels = json_encode(array_column($mechRows, 'name'));
$mechCounts = json_encode(array_map('intval', array_column($mechRows, 'cnt')));

// 5. Breakdown problem categories (keyword grouping)
$problemRows = $pdo->query("SELECT problem_description FROM requests")->fetchAll(PDO::FETCH_COLUMN);
$categories = [
    'Flat Tyre'       => 0,
    'Battery / Jump'  => 0,
    'Engine Issue'    => 0,
    'Out of Fuel'     => 0,
    'Overheating'     => 0,
    'Lockout'         => 0,
    'Other'           => 0,
];
foreach ($problemRows as $desc) {
    $d = strtolower($desc);
    if (str_contains($d, 'tyre') || str_contains($d, 'tire') || str_contains($d, 'flat'))         $categories['Flat Tyre']++;
    elseif (str_contains($d, 'battery') || str_contains($d, 'jump') || str_contains($d, 'start'))  $categories['Battery / Jump']++;
    elseif (str_contains($d, 'engine') || str_contains($d, 'motor'))                               $categories['Engine Issue']++;
    elseif (str_contains($d, 'fuel') || str_contains($d, 'gas') || str_contains($d, 'petrol'))     $categories['Out of Fuel']++;
    elseif (str_contains($d, 'over') || str_contains($d, 'heat'))                                  $categories['Overheating']++;
    elseif (str_contains($d, 'lock') || str_contains($d, 'key'))                                   $categories['Lockout']++;
    else                                                                                            $categories['Other']++;
}
$catLabels = json_encode(array_keys($categories));
$catCounts = json_encode(array_values($categories));

// Active tab
$activeTab = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Nyamato Roadside</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
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
            --sidebar-w: 230px;
        }

        body {
            font-family: 'Poppins', 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--navy);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            box-shadow: 4px 0 16px rgba(0,0,0,0.15);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 1.4rem 1.5rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--orange);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-decoration: none;
        }
        .sidebar-logo span { font-size: 1.5rem; }
        .sidebar-logo small { font-size: 0.65rem; color: rgba(255,255,255,0.4); display: block; line-height: 1; font-weight: 400; }

        .sidebar-nav { flex: 1; padding: 1rem 0; overflow-y: auto; }

        .nav-section {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.3);
            padding: 1rem 1.5rem 0.4rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.72rem 1.5rem;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            cursor: pointer;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.07);
            color: #fff;
            border-left-color: var(--orange);
        }

        .nav-item.active {
            background: rgba(255,107,0,0.15);
            color: var(--orange);
            border-left-color: var(--orange);
            font-weight: 600;
        }

        .nav-item svg {
            width: 17px; height: 17px;
            flex-shrink: 0;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .badge-notif {
            background: var(--orange);
            color: white;
            font-size: 0.62rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 9999px;
            margin-left: auto;
        }

        .sidebar-bottom {
            padding: 1rem 0;
            border-top: 1px solid rgba(255,255,255,0.1);
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

        .topbar-title { font-size: 1rem; font-weight: 600; color: var(--text); }

        .topbar-right { display: flex; align-items: center; gap: 1rem; }

        .avatar-sm {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--navy), #1e3a5f);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 0.85rem;
            text-transform: uppercase;
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
            height: 150px;
            background: linear-gradient(135deg, #0A2540 0%, #1e3a5f 40%, #FF6B00 100%);
            position: relative;
        }
        .profile-cover::after {
            content: "⚙️";
            position: absolute;
            right: 2rem; bottom: 1rem;
            font-size: 5rem;
            opacity: 0.1;
        }

        .profile-info-row {
            display: flex;
            align-items: flex-end;
            gap: 1.25rem;
            padding: 0 1.75rem 1.25rem;
        }

        .profile-avatar {
            width: 88px; height: 88px;
            border-radius: 50%;
            border: 4px solid var(--white);
            background: linear-gradient(135deg, var(--navy), var(--orange));
            display: flex; align-items: center; justify-content: center;
            font-size: 1.9rem; font-weight: 700; color: white;
            margin-top: -44px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .profile-meta { flex: 1; padding-top: 0.5rem; }
        .profile-name { font-size: 1.2rem; font-weight: 700; color: var(--text); }
        .profile-sub { font-size: 0.82rem; color: var(--muted); margin-top: 2px; }

        .profile-badges { display: flex; gap: 0.6rem; margin-top: 0.5rem; flex-wrap: wrap; }

        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 0.74rem; font-weight: 600;
            padding: 4px 10px; border-radius: 9999px;
        }
        .badge-teal   { background: #ccfbf1; color: #0f766e; }
        .badge-orange { background: var(--orange-lt); color: var(--orange); }
        .badge-navy   { background: #e0e7ff; color: #3730a3; }
        .badge-red    { background: #fee2e2; color: #dc2626; }

        .profile-tabs {
            display: flex;
            border-top: 1px solid var(--border);
            margin: 0 1.75rem;
        }

        .tab-btn {
            padding: 0.85rem 1.25rem;
            font-size: 0.875rem; font-weight: 500;
            color: var(--muted);
            background: none; border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer; transition: all 0.2s;
            text-decoration: none; display: inline-block;
        }
        .tab-btn:hover { color: var(--text); }
        .tab-btn.active { color: var(--text); border-bottom-color: var(--text); font-weight: 600; }

        /* ===== CONTENT ===== */
        .content-area { padding: 1.5rem 2rem 2rem; display: flex; flex-direction: column; gap: 1.5rem; }

        /* ===== STATS ROW ===== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.2rem 1.4rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            display: flex; align-items: center; gap: 1rem;
        }

        .stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; flex-shrink: 0;
        }
        .stat-icon.teal   { background: #ccfbf1; }
        .stat-icon.orange { background: var(--orange-lt); }
        .stat-icon.navy   { background: #e0e7ff; }
        .stat-icon.green  { background: #d1fae5; }
        .stat-icon.red    { background: #fee2e2; }

        .stat-num  { font-size: 1.5rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: 0.75rem; color: var(--muted); margin-top: 3px; }

        /* ===== CARDS ===== */
        .card {
            background: var(--white);
            border-radius: 14px;
            padding: 1.5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
        }

        .card-title {
            font-size: 1rem; font-weight: 600; color: var(--text);
            margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: 8px;
        }
        .card-title svg {
            width: 18px; height: 18px;
            stroke: var(--orange); fill: none;
            stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
        }
        .card-title .count-pill {
            background: var(--danger);
            color: white; font-size: 0.65rem; font-weight: 700;
            padding: 2px 7px; border-radius: 9999px; margin-left: auto;
        }

        /* ===== CHARTS GRID ===== */
        .charts-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .charts-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
        }

        .chart-wrap {
            position: relative;
            width: 100%;
            height: 260px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chart-wrap.tall { height: 320px; }

        /* ===== TABLE ===== */
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .data-table th {
            background: #f8fafc;
            font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.04em; color: var(--muted);
            padding: 0.75rem 1rem; text-align: left;
            border-bottom: 2px solid var(--border);
        }
        .data-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #f8fafc; }

        .status-badge {
            display: inline-block;
            padding: 3px 10px; border-radius: 9999px;
            font-size: 0.72rem; font-weight: 600;
        }
        .status-Pending    { background: #fef3c7; color: #d97706; }
        .status-Accepted   { background: #e0e7ff; color: #4338ca; }
        .status-InProgress { background: #dbeafe; color: #1d4ed8; }
        .status-Completed  { background: #d1fae5; color: #059669; }
        .status-Cancelled  { background: #fee2e2; color: #dc2626; }

        /* ===== MECHANIC APPROVAL ITEMS ===== */
        .mechanic-row {
            display: flex; align-items: center; gap: 1rem;
            padding: 0.9rem 0;
            border-bottom: 1px solid var(--border);
        }
        .mechanic-row:last-child { border-bottom: none; }

        .mech-avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--teal), var(--navy));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 1rem;
            flex-shrink: 0;
        }

        .mech-info { flex: 1; }
        .mech-name { font-weight: 600; font-size: 0.9rem; }
        .mech-sub  { font-size: 0.78rem; color: var(--muted); margin-top: 2px; }

        .mech-actions { display: flex; gap: 0.5rem; }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 0.45rem 1rem;
            border-radius: 7px; border: none;
            font-family: inherit; font-size: 0.8rem; font-weight: 600;
            cursor: pointer; text-decoration: none; transition: all 0.2s;
        }
        .btn-approve { background: var(--success);      color: white; }
        .btn-approve:hover { background: #059669; }
        .btn-reject  { background: var(--danger);       color: white; }
        .btn-reject:hover  { background: #dc2626; }
        .btn-primary { background: var(--navy);         color: white; }
        .btn-primary:hover { background: #0f3460; }
        .btn-sm { padding: 0.3rem 0.7rem; font-size: 0.75rem; border-radius: 6px; }

        /* ===== ALERTS ===== */
        .alert { padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem; }
        .alert-success { background: #ecfdf5; color: #059669; border: 1px solid #6ee7b7; }

        /* ===== EMPTY STATE ===== */
        .empty-state { text-align: center; padding: 2.5rem; color: var(--muted); }
        .empty-state .emoji { font-size: 2rem; margin-bottom: 0.5rem; }
        .empty-state p { font-size: 0.9rem; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1100px) {
            .stats-row      { grid-template-columns: repeat(3, 1fr); }
            .charts-grid-3  { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 900px) {
            .charts-grid-2  { grid-template-columns: 1fr; }
            .charts-grid-3  { grid-template-columns: 1fr; }
            .stats-row      { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .profile-header { margin: 1rem; }
            .content-area { padding: 1rem; }
            .stats-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar">
    <a href="dashboard.php" class="sidebar-logo">
        <span>🛡️</span>
        <div>Nyamato <small>Admin Panel</small></div>
    </a>

    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <a href="dashboard.php?tab=overview" class="nav-item <?= $activeTab === 'overview' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z"/><path d="M9 21V12h6v9"/></svg>
            Overview
        </a>
        <a href="dashboard.php?tab=analytics" class="nav-item <?= $activeTab === 'analytics' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
            Analytics
        </a>
        <a href="dashboard.php?tab=requests" class="nav-item <?= $activeTab === 'requests' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
            Requests
        </a>

        <div class="nav-section">Management</div>
        <a href="dashboard.php?tab=approvals" class="nav-item <?= $activeTab === 'approvals' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            Mechanic Approvals
            <?php if($pendingApprovalCount > 0): ?>
                <span class="badge-notif"><?= $pendingApprovalCount ?></span>
            <?php endif; ?>
        </a>
        <a href="reports.php" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
            Reports
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="../logout.php" class="nav-item" style="color: #f87171;">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            Logout
        </a>
    </div>
</aside>

<!-- ===== MAIN ===== -->
<main class="main">

    <!-- Topbar -->
    <div class="topbar">
        <span class="topbar-title">Admin Dashboard</span>
        <div class="topbar-right">
            <?php if($pendingApprovalCount > 0): ?>
                <span class="badge-notif" style="background:var(--danger);"><?= $pendingApprovalCount ?> pending</span>
            <?php endif; ?>
            <div class="avatar-sm"><?= strtoupper(substr($admin_name, 0, 2)) ?></div>
        </div>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-cover"></div>
        <div class="profile-info-row">
            <div class="profile-avatar">🛡️</div>
            <div class="profile-meta">
                <div class="profile-name"><?= htmlspecialchars($admin_name) ?></div>
                <div class="profile-sub">System Administrator · Nyamato Roadside</div>
                <div class="profile-badges">
                    <span class="badge badge-navy">🛡️ Super Admin</span>
                    <span class="badge badge-teal">👥 <?= $totalUsers ?> Drivers</span>
                    <span class="badge badge-orange">🔧 <?= $totalMechanics ?> Mechanics</span>
                    <?php if($pendingApprovalCount > 0): ?>
                        <span class="badge badge-red">⏳ <?= $pendingApprovalCount ?> Pending Approvals</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="profile-tabs">
            <a href="dashboard.php?tab=overview"  class="tab-btn <?= $activeTab === 'overview'  ? 'active' : '' ?>">Overview</a>
            <a href="dashboard.php?tab=analytics" class="tab-btn <?= $activeTab === 'analytics' ? 'active' : '' ?>">Analytics</a>
            <a href="dashboard.php?tab=requests"  class="tab-btn <?= $activeTab === 'requests'  ? 'active' : '' ?>">Requests</a>
            <a href="dashboard.php?tab=approvals" class="tab-btn <?= $activeTab === 'approvals' ? 'active' : '' ?>">Approvals</a>
            <a href="reports.php" class="tab-btn">Reports</a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- ═══════════ STATS ROW (always visible) ═══════════ -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon teal">👥</div>
                <div><div class="stat-num"><?= $totalUsers ?></div><div class="stat-label">Total Drivers</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">🔧</div>
                <div><div class="stat-num"><?= $totalMechanics ?></div><div class="stat-label">Mechanics</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon navy">📋</div>
                <div><div class="stat-num"><?= $totalRequests ?></div><div class="stat-label">Total Requests</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div><div class="stat-num"><?= $completedReqs ?></div><div class="stat-label">Completed</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">⏳</div>
                <div><div class="stat-num"><?= $pendingApprovalCount ?></div><div class="stat-label">Pending Approvals</div></div>
            </div>
        </div>

        <!-- ═══════════ OVERVIEW TAB ═══════════ -->
        <?php if ($activeTab === 'overview'): ?>

        <!-- Charts row: Pie + Doughnut -->
        <div class="charts-grid-2">
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><path d="M21.21 15.89A10 10 0 118 2.83"/><path d="M22 12A10 10 0 0012 2v10z"/></svg>
                    Breakdowns by Vehicle Type
                </div>
                <div class="chart-wrap">
                    <canvas id="vehiclePieChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    Request Status Distribution
                </div>
                <div class="chart-wrap">
                    <canvas id="statusDoughnutChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Requests over time (Line) + Mechanic approvals widget -->
        <div class="charts-grid-2">
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Requests – Last 14 Days
                </div>
                <div class="chart-wrap tall">
                    <canvas id="requestsLineChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                    Pending Mechanic Approvals
                    <?php if($pendingApprovalCount > 0): ?>
                        <span class="count-pill"><?= $pendingApprovalCount ?></span>
                    <?php endif; ?>
                </div>
                <?php if (count($pendingMechanics) > 0): ?>
                    <?php foreach(array_slice($pendingMechanics, 0, 4) as $m): ?>
                        <div class="mechanic-row">
                            <div class="mech-avatar"><?= strtoupper(substr($m['name'], 0, 2)) ?></div>
                            <div class="mech-info">
                                <div class="mech-name"><?= htmlspecialchars($m['name']) ?></div>
                                <div class="mech-sub">📍 <?= htmlspecialchars($m['service_location']) ?> · <?= htmlspecialchars($m['license_number']) ?></div>
                            </div>
                            <div class="mech-actions">
                                <form action="approve_mechanic.php" method="POST" style="margin:0;display:inline;">
                                    <input type="hidden" name="mechanic_id" value="<?= $m['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-approve btn-sm">✅ Approve</button>
                                    <button type="submit" name="action" value="reject"  class="btn btn-reject  btn-sm">✗ Reject</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if(count($pendingMechanics) > 4): ?>
                        <a href="dashboard.php?tab=approvals" class="btn btn-primary btn-sm" style="margin-top:1rem;text-decoration:none;">View All →</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state"><div class="emoji">✅</div><p>No pending approvals.</p></div>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>

        <!-- ═══════════ ANALYTICS TAB ═══════════ -->
        <?php if ($activeTab === 'analytics'): ?>

        <!-- Breakdown problem categories (Polar / Bar) + Top Mechanics (Bar) -->
        <div class="charts-grid-2">
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><path d="M21.21 15.89A10 10 0 118 2.83"/><path d="M22 12A10 10 0 0012 2v10z"/></svg>
                    Breakdown Problem Categories
                </div>
                <div class="chart-wrap tall">
                    <canvas id="categoryBarChart"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                    Top Mechanics by Completed Jobs
                </div>
                <div class="chart-wrap tall">
                    <canvas id="mechBarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Vehicle pie + Status doughnut -->
        <div class="charts-grid-2">
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    Vehicle Type Share
                </div>
                <div class="chart-wrap tall">
                    <canvas id="vehiclePieChart2"></canvas>
                </div>
            </div>
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Requests – Last 14 Days
                </div>
                <div class="chart-wrap tall">
                    <canvas id="requestsLineChart2"></canvas>
                </div>
            </div>
        </div>

        <!-- Summary stats table -->
        <div class="card">
            <div class="card-title">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                Breakdown Summary by Vehicle & Status
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vehicle Type</th>
                        <th>Total Requests</th>
                        <th>Pending</th>
                        <th>In Progress</th>
                        <th>Completed</th>
                        <th>Cancelled</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $summaryRows = $pdo->query("
                        SELECT vehicle_type,
                            COUNT(*) as total,
                            SUM(status = 'Pending') as pending,
                            SUM(status = 'In Progress') as inprog,
                            SUM(status = 'Completed') as completed,
                            SUM(status = 'Cancelled') as cancelled
                        FROM requests
                        GROUP BY vehicle_type
                        ORDER BY total DESC
                    ")->fetchAll();
                    foreach($summaryRows as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['vehicle_type']) ?></strong></td>
                        <td><?= $row['total'] ?></td>
                        <td><span class="status-badge status-Pending"><?= $row['pending'] ?></span></td>
                        <td><span class="status-badge status-InProgress"><?= $row['inprog'] ?></span></td>
                        <td><span class="status-badge status-Completed"><?= $row['completed'] ?></span></td>
                        <td><span class="status-badge status-Cancelled"><?= $row['cancelled'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>

        <!-- ═══════════ REQUESTS TAB ═══════════ -->
        <?php if ($activeTab === 'requests'): ?>
        <div class="card">
            <div class="card-title">
                <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                Recent Breakdown Requests (Last 20)
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Driver</th>
                        <th>Vehicle</th>
                        <th>Problem</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($recentRequests as $req):
                    $sClass = str_replace(' ', '', $req['status']); ?>
                <tr>
                    <td style="color:var(--muted);font-size:0.8rem;">#<?= $req['id'] ?></td>
                    <td><?= htmlspecialchars($req['driver_name']) ?></td>
                    <td><?= htmlspecialchars($req['vehicle_type']) ?></td>
                    <td><?= htmlspecialchars(substr($req['problem_description'], 0, 40)) ?>…</td>
                    <td><span class="status-badge status-<?= $sClass ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                    <td><?= $req['mechanic_name'] ? htmlspecialchars($req['mechanic_name']) : '<span style="color:var(--muted)">Unassigned</span>' ?></td>
                    <td style="white-space:nowrap;"><?= date('M d, Y', strtotime($req['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- ═══════════ APPROVALS TAB ═══════════ -->
        <?php if ($activeTab === 'approvals'): ?>
        <div class="card">
            <div class="card-title">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                All Pending Mechanic Approvals
                <?php if($pendingApprovalCount > 0): ?>
                    <span class="count-pill"><?= $pendingApprovalCount ?></span>
                <?php endif; ?>
            </div>
            <?php if (count($pendingMechanics) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mechanic</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th>License</th>
                            <th>Services</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($pendingMechanics as $m): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="mech-avatar" style="width:34px;height:34px;font-size:0.8rem;"><?= strtoupper(substr($m['name'],0,2)) ?></div>
                                <strong><?= htmlspecialchars($m['name']) ?></strong>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td><?= htmlspecialchars($m['phone']) ?></td>
                        <td><?= htmlspecialchars($m['service_location']) ?></td>
                        <td><?= htmlspecialchars($m['license_number']) ?></td>
                        <td style="font-size:0.78rem;"><?= htmlspecialchars(substr($m['services_offered'],0,40)) ?></td>
                        <td>
                            <form action="approve_mechanic.php" method="POST" style="margin:0;display:flex;gap:0.4rem;">
                                <input type="hidden" name="mechanic_id" value="<?= $m['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-approve btn-sm">✅ Approve</button>
                                <button type="submit" name="action" value="reject"  class="btn btn-reject  btn-sm">✗ Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state"><div class="emoji">✅</div><p>No pending mechanic approvals right now.</p></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /content-area -->
</main>

<!-- ===== CHART.JS CONFIG ===== -->
<script>
// Shared palette
const COLORS = [
    '#FF6B00','#0d9488','#0A2540','#f59e0b','#6366f1',
    '#10b981','#ef4444','#0ea5e9','#8b5cf6','#ec4899'
];

const CHART_DEFAULTS = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { font: { family: 'Poppins', size: 12 }, boxWidth: 12, padding: 16 } }
    }
};

// ── Vehicle Pie (Overview & Analytics) ──────────────────────────────────────
const vehicleData = {
    labels: <?= $vehicleLabels ?>,
    datasets: [{
        data: <?= $vehicleCounts ?>,
        backgroundColor: COLORS,
        borderWidth: 2,
        borderColor: '#fff'
    }]
};
const vehicleCfg = { type: 'pie', data: vehicleData, options: { ...CHART_DEFAULTS, plugins: { ...CHART_DEFAULTS.plugins, tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} requests` } } } } };
if (document.getElementById('vehiclePieChart'))  new Chart(document.getElementById('vehiclePieChart'),  vehicleCfg);
if (document.getElementById('vehiclePieChart2')) new Chart(document.getElementById('vehiclePieChart2'), vehicleCfg);

// ── Status Doughnut ──────────────────────────────────────────────────────────
const statusColorMap = {
    Pending:    '#f59e0b',
    Accepted:   '#6366f1',
    'In Progress': '#0ea5e9',
    Completed:  '#10b981',
    Cancelled:  '#ef4444'
};
const statusLabelsRaw = <?= $statusLabels ?>;
if (document.getElementById('statusDoughnutChart')) {
    new Chart(document.getElementById('statusDoughnutChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabelsRaw,
            datasets: [{
                data: <?= $statusCounts ?>,
                backgroundColor: statusLabelsRaw.map(l => statusColorMap[l] || '#94a3b8'),
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: {
            ...CHART_DEFAULTS,
            cutout: '62%',
            plugins: {
                ...CHART_DEFAULTS.plugins,
                tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } }
            }
        }
    });
}

// ── Requests Line (Overview & Analytics) ────────────────────────────────────
const lineData = {
    labels: <?= $dateLabels ?>,
    datasets: [{
        label: 'Requests',
        data: <?= $dateCounts ?>,
        borderColor: '#FF6B00',
        backgroundColor: 'rgba(255,107,0,0.1)',
        tension: 0.4, fill: true,
        pointBackgroundColor: '#FF6B00',
        pointRadius: 4
    }]
};
const lineCfg = {
    type: 'line',
    data: lineData,
    options: {
        ...CHART_DEFAULTS,
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
        },
        plugins: { ...CHART_DEFAULTS.plugins, legend: { display: false } }
    }
};
if (document.getElementById('requestsLineChart'))  new Chart(document.getElementById('requestsLineChart'),  lineCfg);
if (document.getElementById('requestsLineChart2')) new Chart(document.getElementById('requestsLineChart2'), lineCfg);

// ── Problem Category Bar (Analytics) ────────────────────────────────────────
if (document.getElementById('categoryBarChart')) {
    new Chart(document.getElementById('categoryBarChart'), {
        type: 'bar',
        data: {
            labels: <?= $catLabels ?>,
            datasets: [{
                label: 'Incidents',
                data: <?= $catCounts ?>,
                backgroundColor: COLORS,
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            ...CHART_DEFAULTS,
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                y: { grid: { display: false } }
            },
            plugins: { ...CHART_DEFAULTS.plugins, legend: { display: false } }
        }
    });
}

// ── Top Mechanics Bar (Analytics) ────────────────────────────────────────────
if (document.getElementById('mechBarChart')) {
    new Chart(document.getElementById('mechBarChart'), {
        type: 'bar',
        data: {
            labels: <?= $mechLabels ?>,
            datasets: [{
                label: 'Completed Jobs',
                data: <?= $mechCounts ?>,
                backgroundColor: '#0d9488',
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            ...CHART_DEFAULTS,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false }, ticks: { maxRotation: 30 } }
            },
            plugins: { ...CHART_DEFAULTS.plugins, legend: { display: false } }
        }
    });
}
</script>
</body>
</html>
