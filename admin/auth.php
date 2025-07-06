<?php
require '../includes/security.php';
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}
?>
