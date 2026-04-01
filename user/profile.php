<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch request counts for stats
$reqStmt = $pdo->prepare("SELECT status FROM requests WHERE driver_id = ?");
$reqStmt->execute([$user_id]);
$allReqs       = $reqStmt->fetchAll();
$totalReqs     = count($allReqs);
$completedReqs = count(array_filter($allReqs, fn($r) => $r['status'] === 'Completed'));
$memberSince   = isset($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'N/A';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    if (!empty($phone)) {
        $update = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $update->execute([$phone, $user_id]);
        $user['phone'] = $phone;
        $success = "Profile updated successfully.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – Nyamato Roadside</title>
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
            --sidebar-w: 220px;
        }

        body {
            font-family: 'Poppins', 'Inter', 'Roboto', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
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

        .sidebar-nav { flex: 1; padding: 1rem 0; overflow-y: auto; }

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
        }

        .nav-item:hover { background: var(--orange-lt); color: var(--orange); border-left-color: var(--orange); }
        .nav-item.active { background: var(--orange-lt); color: var(--orange); border-left-color: var(--orange); font-weight: 600; }

        .nav-item svg {
            width: 18px; height: 18px; flex-shrink: 0;
            stroke: currentColor; fill: none;
            stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round;
        }

        .sidebar-bottom { padding: 1rem 0; border-top: 1px solid var(--border); }

        /* MAIN */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; }

        /* TOPBAR */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 2rem;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar-title { font-size: 1rem; font-weight: 600; }
        .avatar-sm {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--teal), var(--teal-dk));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 0.85rem; text-transform: uppercase;
        }

        /* PROFILE HEADER */
        .profile-header {
            background: var(--white); border-radius: 16px; overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07); margin: 1.5rem 2rem 0;
        }
        .profile-cover {
            height: 160px;
            background: linear-gradient(135deg, #0d9488 0%, #0891b2 50%, #1d4ed8 100%);
        }
        .profile-info-row {
            display: flex; align-items: flex-end; gap: 1.25rem;
            padding: 0 1.75rem 1.25rem; position: relative;
        }
        .profile-avatar {
            width: 90px; height: 90px; border-radius: 50%;
            border: 4px solid var(--white);
            background: linear-gradient(135deg, var(--teal), var(--navy));
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 700; color: white; text-transform: uppercase;
            margin-top: -45px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .profile-meta { flex: 1; padding-top: 0.5rem; }
        .profile-name { font-size: 1.25rem; font-weight: 700; }
        .profile-sub  { font-size: 0.82rem; color: var(--muted); margin-top: 2px; }
        .profile-badges { display: flex; gap: 0.6rem; margin-top: 0.5rem; flex-wrap: wrap; }
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 9999px;
        }
        .badge-teal   { background: #ccfbf1; color: #0f766e; }
        .badge-orange { background: var(--orange-lt); color: var(--orange); }
        .badge-navy   { background: #e0e7ff; color: #3730a3; }

        /* TABS */
        .profile-tabs { display: flex; border-top: 1px solid var(--border); margin: 0 1.75rem; }
        .tab-btn {
            padding: 0.85rem 1.25rem; font-size: 0.875rem; font-weight: 500;
            color: var(--muted); background: none; border: none;
            border-bottom: 2px solid transparent; cursor: pointer; transition: all 0.2s;
            text-decoration: none; display: inline-block; font-family: inherit;
        }
        .tab-btn:hover { color: var(--text); }
        .tab-btn.active { color: var(--text); border-bottom-color: var(--text); font-weight: 600; }

        /* CONTENT */
        .content-area { padding: 1.5rem 2rem 2rem; }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 1.5rem;
            align-items: start;
        }

        .card {
            background: var(--white); border-radius: 14px;
            padding: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,0.07);
        }

        .card-title {
            font-size: 1rem; font-weight: 600; color: var(--text);
            margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;
        }
        .card-title svg {
            width: 18px; height: 18px; stroke: var(--orange);
            fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
        }

        /* INFO LIST */
        .info-list { display: flex; flex-direction: column; gap: 0.85rem; }
        .info-row {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.7rem 0.85rem; background: #f8fafc; border-radius: 8px;
        }
        .info-icon { font-size: 1.1rem; width: 28px; text-align: center; flex-shrink: 0; }
        .info-label { font-size: 0.72rem; color: var(--muted); margin-bottom: 1px; }
        .info-value { font-size: 0.88rem; font-weight: 500; }

        /* FORM */
        .form-group { margin-bottom: 1.1rem; }
        label { display: block; margin-bottom: 0.4rem; font-size: 0.82rem; font-weight: 500; color: var(--muted); }
        input[type="text"], input[type="email"] {
            width: 100%; padding: 0.7rem 0.9rem;
            border: 1.5px solid var(--border); border-radius: 8px;
            font-family: inherit; font-size: 0.9rem;
            background: #fafafa; color: var(--text); transition: border-color 0.2s;
        }
        input[type="text"]:focus, input[type="email"]:focus {
            outline: none; border-color: var(--teal); background: var(--white);
        }
        input:disabled { opacity: 0.6; cursor: not-allowed; background: #f1f5f9; }

        .btn-primary {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0.75rem 1.5rem; background: var(--navy); color: white;
            border: none; border-radius: 8px; font-family: inherit;
            font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .btn-primary:hover { background: #0f3460; }

        .alert {
            padding: 0.85rem 1rem; border-radius: 8px;
            margin-bottom: 1rem; font-size: 0.875rem;
        }
        .alert-success { background: #ecfdf5; color: #059669; border: 1px solid #6ee7b7; }

        /* STATS */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; }
        .mini-stat {
            background: #f8fafc; border-radius: 10px; padding: 1rem;
            text-align: center;
        }
        .mini-stat-num { font-size: 1.5rem; font-weight: 700; color: var(--teal); }
        .mini-stat-label { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }

        @media (max-width: 900px) {
            .content-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; }
            .profile-header { margin: 1rem; }
            .content-area { padding: 1rem; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <a href="dashboard.php" class="sidebar-logo">
        <span>🚛</span> Nyamato
    </a>
    <nav class="sidebar-nav">
        <a href="dashboard.php?tab=home" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z"/><path d="M9 21V12h6v9"/></svg>
            Home
        </a>
        <a href="dashboard.php?tab=requests" class="nav-item">
            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
            My Requests
        </a>
        <a href="payment.php" class="nav-item">
            <svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            Payment
        </a>
        <a href="profile.php" class="nav-item active">
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

<!-- MAIN -->
<main class="main">
    <div class="topbar">
        <span class="topbar-title">My Profile</span>
        <div class="avatar-sm"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-cover"></div>
        <div class="profile-info-row">
            <div class="profile-avatar"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
            <div class="profile-meta">
                <div class="profile-name"><?= htmlspecialchars($user_name) ?></div>
                <div class="profile-sub"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                <div class="profile-badges">
                    <span class="badge badge-teal">🗓 Member since <?= $memberSince ?></span>
                    <span class="badge badge-orange">🚗 Driver</span>
                    <span class="badge badge-navy">✅ <?= $completedReqs ?> Completed</span>
                </div>
            </div>
        </div>
        <div class="profile-tabs">
            <a href="dashboard.php?tab=home" class="tab-btn">Home</a>
            <a href="dashboard.php?tab=requests" class="tab-btn">My Requests</a>
            <a href="payment.php" class="tab-btn">Payment</a>
            <a href="profile.php" class="tab-btn active">My Profile</a>
        </div>
    </div>

    <!-- Content -->
    <div class="content-area">
        <div class="content-grid">

            <!-- Left: Account Info snapshot -->
            <div>
                <div class="card">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Account Overview
                    </div>
                    <div class="info-list">
                        <div class="info-row">
                            <div class="info-icon">👤</div>
                            <div>
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?= htmlspecialchars($user['name']) ?></div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-icon">✉️</div>
                            <div>
                                <div class="info-label">Email Address</div>
                                <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-icon">📞</div>
                            <div>
                                <div class="info-label">Phone Number</div>
                                <div class="info-value"><?= htmlspecialchars($user['phone'] ?: 'Not set') ?></div>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-icon">📅</div>
                            <div>
                                <div class="info-label">Member Since</div>
                                <div class="info-value"><?= $memberSince ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="mini-stat">
                            <div class="mini-stat-num"><?= $totalReqs ?></div>
                            <div class="mini-stat-label">Total Requests</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-num"><?= $completedReqs ?></div>
                            <div class="mini-stat-label">Completed</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Edit Form -->
            <div class="card">
                <div class="card-title">
                    <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit Profile
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?= htmlspecialchars($user['name']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number <span style="color: var(--teal);">*</span></label>
                        <input type="text" id="phone" name="phone"
                               value="<?= htmlspecialchars($user['phone']) ?>"
                               placeholder="+254 7XX XXX XXX" required>
                    </div>
                    <button type="submit" class="btn-primary">💾 Update Profile</button>
                </form>
            </div>

        </div>
    </div>
</main>

</body>
</html>
