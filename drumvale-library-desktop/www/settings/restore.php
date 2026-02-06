<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Restore Database";
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-upload"></i> Restore Database</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Settings
            </a>
        </div>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Warning:</strong> Restoring a database backup will replace all current data. Make sure you have a recent backup before proceeding.
    </div>

    <div class="form-container">
        <form method="POST" enctype="multipart/form-data" action="restore_process.php">
            <h3><i class="fas fa-file-upload"></i> Upload Backup File</h3>
            
            <div class="form-group">
                <label for="backup_file">Select SQL Backup File <span class="required">*</span></label>
                <input type="file" name="backup_file" id="backup_file" class="form-input" accept=".sql" required>
                <small class="form-help">Only .sql files are accepted</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to restore this backup? This will replace all current data!')">
                    <i class="fas fa-upload"></i> Restore Database
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
