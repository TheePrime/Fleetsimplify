<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment – Nyamato Roadside</title>
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
            background: var(--bg); color: var(--text);
            display: flex; min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-w); background: var(--white);
            border-right: 1px solid var(--border); display: flex;
            flex-direction: column; position: fixed; top: 0; left: 0;
            height: 100vh; z-index: 100; box-shadow: 2px 0 8px rgba(0,0,0,0.04);
        }
        .sidebar-logo {
            display: flex; align-items: center; gap: 10px;
            justify-content: center; padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border); text-decoration: none;
        }
        .sidebar-logo img { height: 48px; max-width: 170px; width: auto; display: block; }
        .sidebar-nav { flex: 1; padding: 1rem 0; overflow-y: auto; }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 0.75rem 1.5rem; color: var(--muted); text-decoration: none;
            font-size: 0.92rem; font-weight: 500; transition: all 0.2s;
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
            background: var(--white); border-bottom: 1px solid var(--border);
            padding: 0.85rem 2rem; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 50;
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
        .badge-teal { background: #ccfbf1; color: #0f766e; }
        .badge-orange { background: var(--orange-lt); color: var(--orange); }

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

        .page-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; }

        .payment-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; }

        .payment-card {
            background: var(--white); border-radius: 14px; padding: 1.5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            border-top: 4px solid transparent; transition: box-shadow 0.2s;
        }
        .payment-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
        .payment-card.mpesa  { border-top-color: #10b981; }
        .payment-card.bank   { border-top-color: #f59e0b; }
        .payment-card.cash   { border-top-color: #6366f1; }

        .payment-icon { font-size: 2rem; margin-bottom: 0.75rem; }
        .payment-name { font-size: 1rem; font-weight: 700; margin-bottom: 0.4rem; }
        .payment-desc { font-size: 0.82rem; color: var(--muted); line-height: 1.6; }

        .btn-pay {
            display: inline-block; margin-top: 1rem; padding: 0.5rem 1.2rem;
            background: var(--navy); color: white; border: none; border-radius: 8px;
            font-family: inherit; font-size: 0.82rem; font-weight: 600; cursor: pointer;
            transition: all 0.2s; text-decoration: none;
        }
        .btn-pay:hover { background: #0f3460; }
        .btn-pay.green  { background: #10b981; }
        .btn-pay.amber  { background: #f59e0b; }
        .btn-pay.violet { background: #6366f1; }

        .info-box {
            background: #e0f2fe; border-radius: 10px; padding: 1rem 1.25rem;
            font-size: 0.85rem; color: #0369a1; margin-top: 1.5rem;
            border-left: 4px solid #0ea5e9;
        }
        .info-box strong { display: block; margin-bottom: 4px; }

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
        <img src="../Images/logo.png" alt="FleetSimplify logo">
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
        <a href="payment.php" class="nav-item active">
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
        <a href="../../backend/logout.php" class="nav-item" style="color: #ef4444;">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            Logout
        </a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="topbar">
        <span class="topbar-title">Payment Methods</span>
        <div class="avatar-sm"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-cover"></div>
        <div class="profile-info-row">
            <div class="profile-avatar"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
            <div class="profile-meta">
                <div class="profile-name"><?= htmlspecialchars($user_name) ?></div>
                <div class="profile-sub">Roadside Assistance Member</div>
                <div class="profile-badges">
                    <span class="badge badge-teal">💳 Payment Methods</span>
                    <span class="badge badge-orange">🚗 Driver Account</span>
                </div>
            </div>
        </div>
        <div class="profile-tabs">
            <a href="dashboard.php?tab=home" class="tab-btn">Home</a>
            <a href="dashboard.php?tab=requests" class="tab-btn">My Requests</a>
            <a href="payment.php" class="tab-btn active">Payment</a>
            <a href="profile.php" class="tab-btn">My Profile</a>
        </div>
    </div>

    <!-- Content -->
    <div class="content-area">
        <p class="page-title">Supported Payment Methods</p>
        <p style="font-size: 0.875rem; color: var(--muted); margin-bottom: 1.5rem;">
            Pay your mechanic easily using any of the methods below once the service is complete.
        </p>

        <div class="payment-grid">
            <!-- M-Pesa -->
            <div class="payment-card mpesa">
                <div class="payment-icon">📱</div>
                <div class="payment-name">M-Pesa</div>
                <div class="payment-desc">
                    Pay conveniently using M-Pesa. Go to M-Pesa Menu › Lipa na M-Pesa › Buy Goods and Services › Enter Till Number › Enter Amount.
                </div>
                <button class="btn-pay green">Set as Default</button>
            </div>

            <!-- Bank Transfer -->
            <div class="payment-card bank">
                <div class="payment-icon">🏦</div>
                <div class="payment-name">Bank Transfer</div>
                <div class="payment-desc">
                    We support direct bank transfers to all major banks. Your mechanic will provide account details upon request completion.
                </div>
                <button class="btn-pay amber">Set as Default</button>
            </div>

            <!-- Cash -->
            <div class="payment-card cash">
                <div class="payment-icon">💵</div>
                <div class="payment-name">Cash</div>
                <div class="payment-desc">
                    You can pay in cash directly to the mechanic after service is completed. Ensure you agree on the fee beforehand.
                </div>
                <button class="btn-pay violet">Set as Default</button>
            </div>
        </div>

        <div class="info-box">
            <strong>💡 Payment Tips</strong>
            Payments are made directly to your mechanic. Always confirm the service cost before work begins. Nyamato does not handle payments directly — we simply connect you to verified mechanics.
        </div>
    </div>
</main>

</body>
</html>
