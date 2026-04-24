<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Auth check
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
        header("Location: ../../frontend/login.php");
        exit();
    }

    $driver_id = $_SESSION['user_id'];
    $vehicle_type = trim($_POST['vehicle_type']);
    $location_address = trim($_POST['location_address']);
    $problem_description = trim($_POST['problem_description']);
    $location_lat = $_POST['location_lat'];
    $location_lng = $_POST['location_lng'];

    if (empty($vehicle_type) || empty($location_address) || empty($problem_description)) {
        $_SESSION['error'] = "Please fill in all details.";
        header("Location: ../../frontend/user/dashboard.php");
        exit();
    }

    // Default coordinates if location failed or bypassed
    if(empty($location_lat) || empty($location_lng)) {
        $location_lat = 0.000000;
        $location_lng = 0.000000;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO requests (driver_id, vehicle_type, location_lat, location_lng, location_address, problem_description, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        
        $stmt->execute([
            $driver_id, 
            $vehicle_type, 
            $location_lat, 
            $location_lng, 
            $location_address, 
            $problem_description
        ]);

        $_SESSION['success'] = "Breakdown request submitted successfully. We are notifying nearby mechanics.";
        header("Location: ../../frontend/user/dashboard.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
        header("Location: ../../frontend/user/dashboard.php");
        exit();
    }
} else {
    header("Location: ../../frontend/user/dashboard.php");
    exit();
}
?>
