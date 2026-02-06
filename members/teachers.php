<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Redirect to index with teacher filter
header('Location: index.php?member_type=Teacher');
exit();
?>
