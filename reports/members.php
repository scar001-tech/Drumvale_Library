<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}
// Redirect to books report for now - can be expanded later
header('Location: books.php');
exit();
?>
