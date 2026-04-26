<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Roadside Assistance</title>
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
            transition: all 0.3s ease;
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

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
            background-color: #fafafa;
        }

        input:focus, select:focus {
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

        /* Toggle switches */
        .role-selector {
            display: flex;
            background-color: #f1f5f9;
            border-radius: 8px;
            padding: 0.25rem;
            margin-bottom: 1.5rem;
        }

        .role-btn {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--secondary);
            border-radius: 6px;
            transition: all 0.2s;
        }

        .role-btn.active {
            background-color: white;
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .mechanic-fields {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .mechanic-fields.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
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
        <h1>Create an Account</h1>
        <p>Join Roadside Assistance</p>
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

    <div class="role-selector">
        <div class="role-btn active" onclick="toggleRole('user')" id="btn-user">Driver (User)</div>
        <div class="role-btn" onclick="toggleRole('mechanic')" id="btn-mechanic">Service Provider</div>
    </div>

    <form action="auth/register_action.php" method="POST" id="registerForm">
        <input type="hidden" name="role" id="roleInput" value="user">

        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required placeholder="John Doe">
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="john@example.com">
        </div>

        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" required placeholder="e.g. 555-123-4567">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="••••••••" minlength="6">
        </div>

        <!-- Mechanic Specific Fields -->
        <div id="mechanicFields" class="mechanic-fields">
            <div class="form-group">
                <label for="service_location">Base Service Location</label>
                <input type="text" id="service_location" name="service_location" placeholder="City or general area">
            </div>
            
            <div class="form-group">
                <label for="services_offered">Services Offered</label>
                <input type="text" id="services_offered" name="services_offered" placeholder="e.g. Towing, Battery Jump, Flat Tire">
            </div>

            <div class="form-group">
                <label for="license_number">Business / License Number</label>
                <input type="text" id="license_number" name="license_number" placeholder="LIC-XXXXX">
            </div>
        </div>

        <button type="submit" class="btn">Register</button>
    </form>

    <div class="auth-links">
        Already have an account? <a href="login.php">Log in</a>
    </div>
</div>

<script>
    function toggleRole(role) {
        document.getElementById('roleInput').value = role;
        
        const btnUser = document.getElementById('btn-user');
        const btnMechanic = document.getElementById('btn-mechanic');
        const mechanicFields = document.getElementById('mechanicFields');
        const mechanicInputs = mechanicFields.querySelectorAll('input');

        if (role === 'mechanic') {
            btnMechanic.classList.add('active');
            btnUser.classList.remove('active');
            mechanicFields.classList.add('active');
            // Require mechanic fields
            mechanicInputs.forEach(input => input.setAttribute('required', 'true'));
        } else {
            btnUser.classList.add('active');
            btnMechanic.classList.remove('active');
            mechanicFields.classList.remove('active');
            // Remove requirement for mechanic fields
            mechanicInputs.forEach(input => input.removeAttribute('required'));
        }
    }
</script>

</body>
</html>
