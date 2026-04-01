<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mechanic') {
        header("Location: ../login.php");
        exit();
    }

    $request_id = $_POST['request_id'];
    $status = $_POST['status']; // 'In Progress' or 'Completed'
    $user_id = $_SESSION['user_id'];

    if (!in_array($status, ['Accepted', 'In Progress', 'Completed'])) {
        $_SESSION['error'] = "Invalid status.";
        header("Location: dashboard.php");
        exit();
    }

    try {
        // Verify mechanic owns this request
        $stmtM = $pdo->prepare("SELECT id FROM mechanics WHERE user_id = ?");
        $stmtM->execute([$user_id]);
        $mechanic = $stmtM->fetch();
        $mechanic_id = $mechanic['id'];

        $stmtVerify = $pdo->prepare("SELECT id FROM requests WHERE id = ? AND mechanic_id = ?");
        $stmtVerify->execute([$request_id, $mechanic_id]);
        if (!$stmtVerify->fetch()) {
            $_SESSION['error'] = "Unauthorized or invalid request.";
            header("Location: dashboard.php");
            exit();
        }

        // Update status
        $stmtUpdate = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
        $stmtUpdate->execute([$status, $request_id]);

        $_SESSION['success'] = "Task status updated to: " . $status;
        header("Location: dashboard.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>
