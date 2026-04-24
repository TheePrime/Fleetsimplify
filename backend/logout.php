<?php
session_start();
session_unset();
session_destroy();

session_start();
$_SESSION['success'] = "You have been successfully logged out.";
header("Location: ../frontend/login.php");
exit();
?>
