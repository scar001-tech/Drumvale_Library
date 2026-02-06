<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database connection first for processing
include 'includes/db_connect.php';

$admin_id = $_SESSION['admin_id'];

// Fetch admin details
try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        $_SESSION['error_message'] = "Admin not found!";
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: index.php');
    exit();
}

// Handle form submission BEFORE including header (to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']) ?: null;
        
        // Validate
        if (empty($full_name)) {
            $_SESSION['error_message'] = "Full name is required!";
        } else {
            $sql = "UPDATE admins SET full_name = ?, email = ? WHERE admin_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$full_name, $email, $admin_id]);
            
            // Update session
            $_SESSION['admin_name'] = $full_name;
            
            $_SESSION['success_message'] = "Profile updated successfully!";
            header('Location: profile.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Now include header after all redirect logic (HTML output starts here)
$page_title = "My Profile";
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-user-circle"></i> My Profile</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="profile-grid">
        <!-- Profile Info Card -->
        <div class="card profile-card">
            <div class="card-header">
                <h3><i class="fas fa-id-badge"></i> Account Information</h3>
            </div>
            <div class="card-body">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="profile-details">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($admin['username']); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge <?php echo $admin['status'] === 'Active' ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $admin['status']; ?>
                        </span>
                    </p>
                    <p><strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($admin['created_at'])); ?></p>
                    <p><strong>Last Login:</strong> 
                        <?php echo $admin['last_login'] ? date('M d, Y \a\t h:i A', strtotime($admin['last_login'])) : 'Never'; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Edit Profile Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-edit"></i> Edit Profile</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="form-grid">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" class="form-input" 
                               value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                        <small class="form-help">Username cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" id="full_name" class="form-input" 
                               value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-input" 
                               value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>"
                               placeholder="email@example.com">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="change_password.php" class="btn btn-secondary">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.profile-grid {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 2rem;
}

@media (max-width: 900px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
}

.profile-card .card-body {
    text-align: center;
    padding: 2rem;
}

.profile-avatar {
    font-size: 6rem;
    color: var(--primary-color, #667eea);
    margin-bottom: 1.5rem;
}

.profile-avatar i {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.profile-details {
    text-align: left;
}

.profile-details p {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    margin: 0;
}

.profile-details p:last-child {
    border-bottom: none;
}

.card {
    background: var(--card-bg, #fff);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
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

.form-help {
    color: var(--text-muted, #718096);
    font-size: 0.85rem;
    margin-top: 0.25rem;
    display: block;
}
</style>

<?php include 'includes/footer.php'; ?>
