<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Include database connection first for processing
include '../includes/db_connect.php';

$member_id = $_GET['id'] ?? 0;

// Fetch member details BEFORE including header (to allow redirects)
try {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        $_SESSION['error_message'] = "Member not found!";
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
        $sql = "UPDATE members SET 
                full_name = ?, class_or_department = ?, phone_number = ?, 
                email = ?, address = ?, parent_guardian_name = ?, 
                parent_guardian_phone = ?, status = ?
                WHERE member_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            trim($_POST['full_name']),
            trim($_POST['class_or_department']),
            trim($_POST['phone_number'] ?? '') ?: null,
            trim($_POST['email'] ?? '') ?: null,
            trim($_POST['address'] ?? '') ?: null,
            trim($_POST['parent_guardian_name'] ?? '') ?: null,
            trim($_POST['parent_guardian_phone'] ?? '') ?: null,
            $_POST['status'],
            $member_id
        ]);
        
        $_SESSION['success_message'] = "Member updated successfully!";
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Now include header after all redirect logic (HTML output starts here)
$page_title = "Edit Member";
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-user-edit"></i> Edit Member</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Members
            </a>
        </div>
    </div>

    <div class="form-container">
        <form method="POST" class="form-grid">
            <div class="form-section">
                <h3><i class="fas fa-id-card"></i> Basic Information</h3>
                
                <div class="form-group">
                    <label>Member Type</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($member['member_type']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label>ID Number</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($member['unique_identifier']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-input" 
                           value="<?php echo htmlspecialchars($member['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="class_or_department">Class/Department <span class="required">*</span></label>
                    <input type="text" name="class_or_department" id="class_or_department" class="form-input" 
                           value="<?php echo htmlspecialchars($member['class_or_department']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="status">Status <span class="required">*</span></label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="Active" <?php echo $member['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Left School" <?php echo $member['status'] === 'Left School' ? 'selected' : ''; ?>>Left School</option>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-address-book"></i> Contact Information</h3>
                
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" name="phone_number" id="phone_number" class="form-input" 
                           value="<?php echo htmlspecialchars($member['phone_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-input" 
                           value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="address">Physical Address</label>
                    <textarea name="address" id="address" class="form-textarea" rows="3"><?php echo htmlspecialchars($member['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <?php if ($member['member_type'] === 'Student'): ?>
            <div class="form-section">
                <h3><i class="fas fa-user-shield"></i> Parent/Guardian Information</h3>
                
                <div class="form-group">
                    <label for="parent_guardian_name">Parent/Guardian Name</label>
                    <input type="text" name="parent_guardian_name" id="parent_guardian_name" class="form-input" 
                           value="<?php echo htmlspecialchars($member['parent_guardian_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="parent_guardian_phone">Parent/Guardian Phone</label>
                    <input type="tel" name="parent_guardian_phone" id="parent_guardian_phone" class="form-input" 
                           value="<?php echo htmlspecialchars($member['parent_guardian_phone'] ?? ''); ?>">
                </div>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Member
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
