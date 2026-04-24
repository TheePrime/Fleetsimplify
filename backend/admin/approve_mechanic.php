<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: ../../frontend/login.php");
        exit();
    }

    $mechanic_id = $_POST['mechanic_id'];
    $action = $_POST['action'];

    $status = ($action === 'approve') ? 'APPROVED' : 'REJECTED';

    try {
        $stmt = $pdo->prepare("UPDATE mechanics SET approval_status = ? WHERE id = ?");
        $stmt->execute([$status, $mechanic_id]);

        $_SESSION['success'] = "Mechanic status updated to " . $status;
        header("Location: ../../frontend/admin/dashboard.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
        header("Location: ../../frontend/admin/dashboard.php");
        exit();
    }
} else {
    header("Location: ../../frontend/admin/dashboard.php");
    exit();
}
?>
