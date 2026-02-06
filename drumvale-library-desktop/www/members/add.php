<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Register New Member";
include '../includes/header.php';
include '../includes/db_connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $unique_identifier = trim($_POST['unique_identifier']);
        $full_name = trim($_POST['full_name']);
        $member_type = $_POST['member_type'];
        $class_or_department = trim($_POST['class_or_department']);
        $phone_number = trim($_POST['phone_number']) ?: null;
        $email = trim($_POST['email']) ?: null;
        $address = trim($_POST['address']) ?: null;
        $parent_guardian_name = trim($_POST['parent_guardian_name']) ?: null;
        $parent_guardian_phone = trim($_POST['parent_guardian_phone']) ?: null;
        $registration_date = $_POST['registration_date'];
        
        // Check if unique identifier already exists
        $check_stmt = $pdo->prepare("SELECT member_id FROM members WHERE unique_identifier = ?");
        $check_stmt->execute([$unique_identifier]);
        
        if ($check_stmt->fetch()) {
            $_SESSION['error_message'] = "A member with this ID already exists!";
        } else {
            $sql = "INSERT INTO members (unique_identifier, full_name, member_type, class_or_department, 
                    phone_number, email, address, parent_guardian_name, parent_guardian_phone, 
                    registration_date, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $unique_identifier, $full_name, $member_type, $class_or_department,
                $phone_number, $email, $address, $parent_guardian_name, 
                $parent_guardian_phone, $registration_date
            ]);
            
            $_SESSION['success_message'] = "Member registered successfully!";
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-user-plus"></i> Register New Member</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Members
            </a>
        </div>
    </div>

    <div class="form-container">
        <form method="POST" class="form-grid" id="memberForm">
            <div class="form-section">
                <h3><i class="fas fa-id-card"></i> Basic Information</h3>
                
                <div class="form-group">
                    <label for="member_type">Member Type <span class="required">*</span></label>
                    <select name="member_type" id="member_type" class="form-select" required onchange="toggleFields()">
                        <option value="">Select Type</option>
                        <option value="Student">Student</option>
                        <option value="Teacher">Teacher</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="unique_identifier">
                        <span id="id_label">Admission/Staff Number</span> <span class="required">*</span>
                    </label>
                    <input type="text" name="unique_identifier" id="unique_identifier" 
                           class="form-input" required placeholder="e.g., ADM001 or STF001">
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" name="full_name" id="full_name" 
                           class="form-input" required placeholder="Enter full name">
                </div>

                <div class="form-group">
                    <label for="class_or_department">
                        <span id="class_label">Class/Department</span> <span class="required">*</span>
                    </label>
                    <input type="text" name="class_or_department" id="class_or_department" 
                           class="form-input" required placeholder="e.g., Form 1A or Mathematics Dept">
                </div>

                <div class="form-group">
                    <label for="registration_date">Registration Date <span class="required">*</span></label>
                    <input type="date" name="registration_date" id="registration_date" 
                           class="form-input" required value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-address-book"></i> Contact Information</h3>
                
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" name="phone_number" id="phone_number" 
                           class="form-input" placeholder="e.g., 0712345678">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" 
                           class="form-input" placeholder="email@example.com">
                </div>

                <div class="form-group">
                    <label for="address">Physical Address</label>
                    <textarea name="address" id="address" class="form-textarea" 
                              rows="3" placeholder="Enter physical address"></textarea>
                </div>
            </div>

            <div class="form-section" id="guardian_section" style="display: none;">
                <h3><i class="fas fa-user-shield"></i> Parent/Guardian Information</h3>
                <p class="form-help">Required for students only</p>
                
                <div class="form-group">
                    <label for="parent_guardian_name">Parent/Guardian Name</label>
                    <input type="text" name="parent_guardian_name" id="parent_guardian_name" 
                           class="form-input" placeholder="Enter parent/guardian name">
                </div>

                <div class="form-group">
                    <label for="parent_guardian_phone">Parent/Guardian Phone</label>
                    <input type="tel" name="parent_guardian_phone" id="parent_guardian_phone" 
                           class="form-input" placeholder="e.g., 0712345678">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Register Member
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleFields() {
    const memberType = document.getElementById('member_type').value;
    const guardianSection = document.getElementById('guardian_section');
    const idLabel = document.getElementById('id_label');
    const classLabel = document.getElementById('class_label');
    
    if (memberType === 'Student') {
        guardianSection.style.display = 'block';
        idLabel.textContent = 'Admission Number';
        classLabel.textContent = 'Class';
    } else if (memberType === 'Teacher') {
        guardianSection.style.display = 'none';
        idLabel.textContent = 'Staff Number';
        classLabel.textContent = 'Department';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
