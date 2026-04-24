<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Roadside Assistance</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary: #475569;
            --bg-color: #f8fafc;
            --form-bg: #ffffff;
            --text-color: #0f172a;
            --border-color: #e2e8f0;
            --success: #10b981;
            --danger: #ef4444;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .auth-container {
            background-color: var(--form-bg);
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo {
            margin-bottom: 0.75rem;
        }

        .brand-logo img {
            height: 72px;
            max-width: 240px;
            width: auto;
            display: inline-block;
        }

        .auth-header h1 {
            font-size: 1.75rem;
            margin: 0 0 0.5rem 0;
            color: var(--primary);
        }

        .auth-header p {
            color: var(--secondary);
            margin: 0;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-color);
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
            background-color: #fafafa;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background-color: #fff;
        }

        .btn {
            width: 100%;
            padding: 0.875rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn:hover {
            background-color: var(--primary-hover);
        }

        .btn:active {
            transform: scale(0.98);
        }

        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .auth-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: none;
        }
        
        .alert.error {
            background-color: #fef2f2;
            color: var(--danger);
            border: 1px solid #fecaca;
            display: block;
        }
        .alert.success {
            background-color: #ecfdf5;
            color: var(--success);
            border: 1px solid #a7f3d0;
            display: block;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-header">
        <div class="brand-logo"><img src="Images/logo.png" alt="FleetSimplify logo"></div>
        <h1>Welcome Back</h1>
        <p>Log in to Roadside Assistance</p>
    </div>

    <?php
    if (isset($_SESSION['error'])) {
        echo '<div class="alert error">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<div class="alert success">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    ?>

    <form action="../backend/auth/login_action.php" method="POST">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="john@example.com">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>

        <button type="submit" class="btn">Log In</button>
    </form>

    <div class="auth-links">
        Don't have an account? <a href="register.php">Sign up</a>
    </div>
</div>

</body>
</html>
