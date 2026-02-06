<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db_connect.php';

try {
    $filename = 'drumvale_library_backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Get database name from connection
    $db_name = 'drumvale_library';
    
    // Execute mysqldump
    $command = "mysqldump --user=root --password= --host=localhost $db_name";
    passthru($command);
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Backup failed: " . $e->getMessage();
    header('Location: index.php');
    exit();
}
?>
