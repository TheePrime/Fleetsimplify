<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roadside Assistance - Fast & Reliable Help</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #f59e0b;
            --dark: #0f172a;
            --light: #f8fafc;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light);
            color: var(--dark);
        }

        header {
            background-color: white;
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-sizing: border-box;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            margin-left: 2rem;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .btn {
            padding: 0.6rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: 2px solid var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
            border: 2px solid var(--secondary);
            font-size: 1.1rem;
            padding: 0.8rem 2rem;
        }

        .hero {
    height: 100vh;
    display: flex;
    align-items: center;
    padding: 0 5%;
    margin-top: 0;

    background: url('images/0_0.webp');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

        .hero-content {
            max-width: 600px;
        }

        .hero h1 {
            font-size: 3.5rem;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: #a6a8ab;
        }

        .hero h1 span {
            color: var(--primary);
        }

        .hero p {
            font-size: 1.25rem;
            color: #e6e3e3;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
        }

        .hero-image {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero-image img {
            max-width: 100%;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
        }

        .features {
            padding: 5rem 5%;
            background-color: white;
            text-align: center;
        }

        .features h2 {
            font-size: 2.5rem;
            margin-bottom: 3rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            padding: 2rem;
            background: var(--light);
            border-radius: 12px;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #64748b;
            line-height: 1.6;
        }

        footer {
            background-color: var(--dark);
            color: white;
            text-align: center;
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .hero {
                flex-direction: column;
                justify-content: center;
                text-align: center;
                height: auto;
                padding-top: 8rem;
                padding-bottom: 4rem;
            }
            .hero-buttons {
                justify-content: center;
            }
            .hero-image {
                margin-top: 3rem;
            }
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>

    <header>
        <a href="#" class="logo">🛠️ MechanicRescue</a>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#features">Features</a>
            <a href="login.php" class="btn btn-outline">Login</a>
            <a href="register.php" class="btn btn-primary">Sign Up</a>
        </div>
    </header>

    <section id="home" class="hero">
        <div class="hero-content">
            <h1>On-Demand <span>Roadside Assistance</span> When You Need It Most</h1>
            <p>Stuck on the side of the road? Get connected with trusted, nearby mechanics and service providers instantly. Real-time tracking and fast response.</p>
            <div class="hero-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $_SESSION['user_role']; ?>/dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Get Help Now</a>
                    <a href="register.php" class="btn btn-outline" style="padding: 0.8rem 2rem; font-size: 1.1rem; display: flex; align-items: center;">Become a Provider</a>
                <?php endif; ?>
            </div>
        </div>
        
    </section>

    <section id="features" class="features">
        <h2>Why Choose MechanicRescue?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <span class="feature-icon">📍</span>
                <h3>Real-Time Tracking</h3>
                <p>Watch your mechanic arrive in real-time. Know exactly when help will be there with dynamic ETA updates.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon">⚡</span>
                <h3>Fast Response</h3>
                <p>Our intelligent system automatically alerts the nearest available mechanics to minimize your wait time.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon">🔧</span>
                <h3>Verified Professionals</h3>
                <p>Every mechanic is vetted and approved by our administrators. Get high-quality service you can trust.</p>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> MechanicRescue. All rights reserved.</p>
    </footer>

</body>
</html>
