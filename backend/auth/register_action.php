<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'user';
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    // Basic Validation
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: ../../frontend/register.php");
        exit();
    }

    try {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email already registered.";
            header("Location: ../../frontend/register.php");
            exit();
        }

        // Hash Password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Begin transaction
        $pdo->beginTransaction();

        // Insert into users
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $password_hash, $role]);
        $user_id = $pdo->lastInsertId();

        // If mechanic, insert into mechanics table
        if ($role === 'mechanic') {
            $service_location = trim($_POST['service_location']);
            $services_offered = trim($_POST['services_offered']);
            $license_number = trim($_POST['license_number']);

            if (empty($service_location) || empty($services_offered) || empty($license_number)) {
                $pdo->rollBack();
                $_SESSION['error'] = "Mechanic details are required.";
                header("Location: ../../frontend/register.php");
                exit();
            }

            // Check if license is unique
            $stmt = $pdo->prepare("SELECT id FROM mechanics WHERE license_number = ?");
            $stmt->execute([$license_number]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                $_SESSION['error'] = "License number already registered.";
                header("Location: ../../frontend/register.php");
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO mechanics (user_id, service_location, services_offered, license_number, approval_status) VALUES (?, ?, ?, ?, 'PENDING APPROVAL')");
            $stmt->execute([$user_id, $service_location, $services_offered, $license_number]);
        }

        // Commit transaction
        $pdo->commit();

        $_SESSION['success'] = "Registration successful! Please log in.";
        if($role === 'mechanic') {
            $_SESSION['success'] = "Registration successful! Your mechanic account is pending administrator approval.";
        }
        
        header("Location: ../../frontend/login.php");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        header("Location: ../../frontend/register.php");
        exit();
    }
} else {
    header("Location: ../../frontend/register.php");
    exit();
}
?>
