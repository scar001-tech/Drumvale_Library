<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database connection first for processing
include 'includes/db_connect.php';

$admin_id = $_SESSION['admin_id'];

// Handle form submission BEFORE including header (to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate inputs
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error_message'] = "All fields are required!";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error_message'] = "New passwords do not match!";
        } elseif (strlen($new_password) < 6) {
            $_SESSION['error_message'] = "New password must be at least 6 characters!";
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE admin_id = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch();
            
            if (!password_verify($current_password, $admin['password_hash'])) {
                $_SESSION['error_message'] = "Current password is incorrect!";
            } else {
                // Update password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE admin_id = ?");
                $update_stmt->execute([$new_hash, $admin_id]);
                
                $_SESSION['success_message'] = "Password changed successfully!";
                header('Location: profile.php');
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Now include header after all redirect logic (HTML output starts here)
$page_title = "Change Password";
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-key"></i> Change Password</h1>
        <div class="page-actions">
            <a href="profile.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Profile
            </a>
        </div>
    </div>

    <div class="form-container password-form-container">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-lock"></i> Update Your Password</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="form-grid" id="passwordForm">
                    <div class="form-group">
                        <label for="current_password">Current Password <span class="required">*</span></label>
                        <div class="password-input-wrapper">
                            <input type="password" name="current_password" id="current_password" 
                                   class="form-input" required placeholder="Enter current password">
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password <span class="required">*</span></label>
                        <div class="password-input-wrapper">
                            <input type="password" name="new_password" id="new_password" 
                                   class="form-input" required placeholder="Enter new password" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-help">Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                        <div class="password-input-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" 
                                   class="form-input" required placeholder="Confirm new password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Change Password
                        </button>
                        <a href="profile.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="password-tips">
            <h4><i class="fas fa-shield-alt"></i> Password Tips</h4>
            <ul>
                <li><i class="fas fa-check"></i> Use at least 6 characters</li>
                <li><i class="fas fa-check"></i> Mix uppercase and lowercase letters</li>
                <li><i class="fas fa-check"></i> Include numbers and special characters</li>
                <li><i class="fas fa-check"></i> Don't use personal information</li>
                <li><i class="fas fa-check"></i> Don't reuse passwords from other accounts</li>
            </ul>
        </div>
    </div>
</div>

<style>
.password-form-container {
    max-width: 600px;
}

.card {
    background: var(--card-bg, #fff);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
}

.card-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.card-header h3 i {
    margin-right: 0.5rem;
}

.card-body {
    padding: 1.5rem;
}

.password-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-input-wrapper .form-input {
    padding-right: 45px;
}

.password-toggle {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    color: var(--text-muted, #718096);
    cursor: pointer;
    padding: 5px;
    font-size: 1rem;
}

.password-toggle:hover {
    color: var(--primary-color, #667eea);
}

.form-help {
    color: var(--text-muted, #718096);
    font-size: 0.85rem;
    margin-top: 0.25rem;
    display: block;
}

.password-tips {
    background: linear-gradient(135deg, #f6f8fb 0%, #eef2f7 100%);
    border-radius: 12px;
    padding: 1.5rem;
    border-left: 4px solid var(--primary-color, #667eea);
}

.password-tips h4 {
    margin: 0 0 1rem 0;
    color: var(--text-color, #2d3748);
    font-size: 1rem;
}

.password-tips h4 i {
    margin-right: 0.5rem;
    color: var(--primary-color, #667eea);
}

.password-tips ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.password-tips li {
    padding: 0.5rem 0;
    color: var(--text-muted, #718096);
    font-size: 0.9rem;
}

.password-tips li i {
    color: #48bb78;
    margin-right: 0.75rem;
    font-size: 0.8rem;
}
</style>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Form validation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New passwords do not match!');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
