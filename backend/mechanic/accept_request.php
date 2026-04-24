<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mechanic') {
        header("Location: ../../frontend/login.php");
        exit();
    }

    $request_id = $_POST['request_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Get mechanic id
        $stmtM = $pdo->prepare("SELECT id FROM mechanics WHERE user_id = ?");
        $stmtM->execute([$user_id]);
        $mechanic = $stmtM->fetch();
        
        if(!$mechanic) {
            $_SESSION['error'] = "Mechanic profile not found.";
            header("Location: ../../frontend/user/dashboard.php");
            exit();
        }

        $mechanic_id = $mechanic['id'];

        // Begin Transaction to prevent race conditions (two mechanics accepting simultaneously)
        $pdo->beginTransaction();

        $stmtCheck = $pdo->prepare("SELECT status FROM requests WHERE id = ? FOR UPDATE");
        $stmtCheck->execute([$request_id]);
        $req = $stmtCheck->fetch();

        if ($req && $req['status'] === 'Pending') {
            $stmtUpdate = $pdo->prepare("UPDATE requests SET mechanic_id = ?, status = 'Accepted' WHERE id = ?");
            $stmtUpdate->execute([$mechanic_id, $request_id]);
            $pdo->commit();

            $_SESSION['success'] = "You have successfully accepted the request. Please proceed to the location.";
        } else {
            $pdo->rollBack();
            $_SESSION['error'] = "This request is no longer available.";
        }
        
        header("Location: ../../frontend/mechanic/dashboard.php");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
        header("Location: ../../frontend/mechanic/dashboard.php");
        exit();
    }
} else {
    header("Location: ../../frontend/mechanic/dashboard.php");
    exit();
}
?>
