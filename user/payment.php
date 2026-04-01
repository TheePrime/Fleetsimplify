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
    <title>Payment Methods - Roadside Assistance</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', 'Inter', 'Roboto', sans-serif; background: #f8fafc; margin:0; padding:0; color: #0f172a; }
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
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; }
        .payment-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 1.5rem; border-left: 4px solid #2563eb; }
        .payment-card h3 { margin-top: 0; color: #1e293b; }
        .payment-card p { color: #64748b; margin-bottom: 0; }
        .btn { padding: 0.5rem 1rem; background-color: #2563eb; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 1rem; }
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
            <li><a href="dashboard.php">Find Services</a></li>
            <li><a href="dashboard.php">My Requests</a></li>
            <li><a href="payment.php" class="active">Payment</a></li>
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
        <h2>Supported Payment Methods</h2>
        <p>You can pay your mechanic using any of the supported methods below once the service is complete.</p>

        <div class="payment-card" style="border-left-color: #10b981;">
            <h3>M-Pesa</h3>
            <p>Pay conveniently using M-Pesa. To pay: Go to M-Pesa Menu > Lipa na M-Pesa > Buy Goods and Services > Enter Till Number > Enter Amount.</p>
            <button class="btn" style="background-color: #10b981;">Set as Default</button>
        </div>

        <div class="payment-card" style="border-left-color: #f59e0b;">
            <h3>Bank Transfer</h3>
            <p>We support direct bank transfers to major banks. Your mechanic will provide the account details upon request completion.</p>
            <button class="btn" style="background-color: #f59e0b;">Set as Default</button>
        </div>

        <div class="payment-card" style="border-left-color: #64748b;">
            <h3>Cash</h3>
            <p>You can also pay in cash directly to the mechanic after the service.</p>
            <button class="btn" style="background-color: #64748b;">Set as Default</button>
        </div>
    </div>
</body>
</html>
