<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db_connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $admin_id = $_SESSION['admin_id'];
        
        foreach ($_POST['settings'] as $key => $value) {
            $sql = "UPDATE system_settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$value, $admin_id, $key]);
        }
        
        $_SESSION['success_message'] = "Settings updated successfully!";
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

$page_title = "System Settings";
include '../includes/header.php';

// Get all settings
try {
    $stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key");
    $settings = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-cog"></i> System Settings</h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" class="form-grid">
            <div class="form-section">
                <h3><i class="fas fa-book-reader"></i> Borrowing Limits</h3>
                
                <?php foreach ($settings as $setting): ?>
                    <?php if (strpos($setting['setting_key'], 'borrow_limit') !== false): ?>
                    <div class="form-group">
                        <label for="<?php echo $setting['setting_key']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                        </label>
                        <input type="number" 
                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                               id="<?php echo $setting['setting_key']; ?>"
                               class="form-input" 
                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                               min="1" required>
                        <small class="form-help"><?php echo htmlspecialchars($setting['description']); ?></small>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-calendar-alt"></i> Loan Duration</h3>
                
                <?php foreach ($settings as $setting): ?>
                    <?php if (strpos($setting['setting_key'], 'loan_duration') !== false): ?>
                    <div class="form-group">
                        <label for="<?php echo $setting['setting_key']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                        </label>
                        <input type="number" 
                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                               id="<?php echo $setting['setting_key']; ?>"
                               class="form-input" 
                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                               min="1" required>
                        <small class="form-help"><?php echo htmlspecialchars($setting['description']); ?></small>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-money-bill-wave"></i> Fine Settings</h3>
                
                <?php foreach ($settings as $setting): ?>
                    <?php if (strpos($setting['setting_key'], 'fine') !== false || strpos($setting['setting_key'], 'renewal') !== false): ?>
                    <div class="form-group">
                        <label for="<?php echo $setting['setting_key']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                        </label>
                        <input type="number" 
                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                               id="<?php echo $setting['setting_key']; ?>"
                               class="form-input" 
                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                               step="0.01" min="0" required>
                        <small class="form-help"><?php echo htmlspecialchars($setting['description']); ?></small>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> General Settings</h3>
                
                <?php foreach ($settings as $setting): ?>
                    <?php if (strpos($setting['setting_key'], 'library_name') !== false || strpos($setting['setting_key'], 'academic_year') !== false): ?>
                    <div class="form-group">
                        <label for="<?php echo $setting['setting_key']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                        </label>
                        <input type="text" 
                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                               id="<?php echo $setting['setting_key']; ?>"
                               class="form-input" 
                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                               required>
                        <small class="form-help"><?php echo htmlspecialchars($setting['description']); ?></small>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
                <a href="../index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <div class="settings-info">
        <h3><i class="fas fa-database"></i> Database Management</h3>
        <div class="button-group">
            <a href="backup.php" class="btn btn-success">
                <i class="fas fa-download"></i> Backup Database
            </a>
            <a href="restore.php" class="btn btn-warning">
                <i class="fas fa-upload"></i> Restore Database
            </a>
        </div>
    </div>
</div>

<style>
.settings-info {
    margin-top: 2rem;
    padding: 1.5rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.settings-info h3 {
    margin-top: 0;
    margin-bottom: 1rem;
}

.button-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
</style>

<?php include '../includes/footer.php'; ?>
