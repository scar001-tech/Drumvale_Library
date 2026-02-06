<?php
session_start();

// Include database connection for logging
include 'includes/db_connect.php';

// Log logout activity if admin is logged in
if (isset($_SESSION['admin_id'])) {
    try {
        logActivity($pdo, $_SESSION['admin_id'], 'LOGOUT', 'admins', $_SESSION['admin_id']);
    } catch (Exception $e) {
        // Log error but don't prevent logout
        error_log("Logout logging failed: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>