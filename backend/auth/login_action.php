<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields.";
        header("Location: ../../frontend/login.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Check if mechanic is approved
            if ($user['role'] === 'mechanic') {
                $stmt = $pdo->prepare("SELECT approval_status FROM mechanics WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $mechanic = $stmt->fetch();
                
                if ($mechanic && $mechanic['approval_status'] !== 'APPROVED') {
                    $_SESSION['error'] = "Account pending approval or rejected. Contact Admin.";
                    header("Location: ../../frontend/login.php");
                    exit();
                }
            }
            
            // Valid login, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../../frontend/admin/dashboard.php");
            } elseif ($user['role'] === 'mechanic') {
                header("Location: ../../frontend/mechanic/dashboard.php");
            } else {
                header("Location: ../../frontend/user/dashboard.php");
            }
            exit();

        } else {
            $_SESSION['error'] = "Invalid email or password.";
            header("Location: ../../frontend/login.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Login failed: " . $e->getMessage();
        header("Location: ../../frontend/login.php");
        exit();
    }
} else {
    header("Location: ../../frontend/login.php");
    exit();
}
?>
