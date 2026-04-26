<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mechanic') {
        header("Location: ../../frontend/login.php");
        exit();
    }

    $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $status = $_POST['status']; // 'Accepted', 'In Progress' or 'Completed'
    $agreed_amount_raw = $_POST['agreed_amount'] ?? null;
    $user_id = $_SESSION['user_id'];

    if (!in_array($status, ['Accepted', 'In Progress', 'Completed'])) {
        $_SESSION['error'] = "Invalid status.";
        header("Location: ../../frontend/mechanic/dashboard.php");
        exit();
    }

    $agreed_amount = null;
    if ($status === 'Completed') {
        if ($agreed_amount_raw === null || trim((string)$agreed_amount_raw) === '') {
            $_SESSION['error'] = "Amount (KES) is required when marking a task as Completed.";
            header("Location: ../../frontend/mechanic/dashboard.php?tab=tasks");
            exit();
        }

        if (!is_numeric($agreed_amount_raw) || (float)$agreed_amount_raw <= 0) {
            $_SESSION['error'] = "Please provide a valid invoice amount greater than zero.";
            header("Location: ../../frontend/mechanic/dashboard.php?tab=tasks");
            exit();
        }

        $agreed_amount = round((float)$agreed_amount_raw, 2);
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
            header("Location: ../../frontend/mechanic/dashboard.php");
            exit();
        }

        // Update status (+ agreed amount when completed)
        if ($status === 'Completed') {
            $stmtUpdate = $pdo->prepare("UPDATE requests SET status = ?, agreed_amount = ? WHERE id = ?");
            $stmtUpdate->execute([$status, $agreed_amount, $request_id]);
            $_SESSION['success'] = "Task marked Completed. Invoice set to KES " . number_format($agreed_amount, 2);
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$status, $request_id]);
            $_SESSION['success'] = "Task status updated to: " . $status;
        }

        header("Location: ../../frontend/mechanic/dashboard.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
        header("Location: ../../frontend/mechanic/dashboard.php");
        exit();
    }
} else {
    header("Location: ../../frontend/mechanic/dashboard.php");
    exit();
}
?>
