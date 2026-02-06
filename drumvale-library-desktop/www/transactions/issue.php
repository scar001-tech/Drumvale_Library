<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Issue Book";
include '../includes/header.php';
include '../includes/db_connect.php';

// Get system settings
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$settings = [];
while ($row = $settings_stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $book_id = $_POST['book_id'];
        $member_id = $_POST['member_id'];
        $admin_id = $_SESSION['admin_id'];
        
        // Get member type
        $member_stmt = $pdo->prepare("SELECT member_type FROM members WHERE member_id = ?");
        $member_stmt->execute([$member_id]);
        $member = $member_stmt->fetch();
        
        // Check borrow limit
        $limit_key = strtolower($member['member_type']) . '_borrow_limit';
        $borrow_limit = $settings[$limit_key] ?? 2;
        
        $active_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions WHERE member_id = ? AND status = 'Issued'");
        $active_stmt->execute([$member_id]);
        $active_count = $active_stmt->fetch()['count'];
        
        if ($active_count >= $borrow_limit) {
            $_SESSION['error_message'] = "Member has reached borrow limit ($borrow_limit books)";
        } else {
            // Check book availability
            $book_stmt = $pdo->prepare("SELECT available_copies FROM books WHERE book_id = ?");
            $book_stmt->execute([$book_id]);
            $book = $book_stmt->fetch();
            
            if ($book['available_copies'] < 1) {
                $_SESSION['error_message'] = "Book is not available";
            } else {
                // Calculate due date
                $duration_key = strtolower($member['member_type']) . '_loan_duration';
                $loan_duration = $settings[$duration_key] ?? 14;
                
                $issue_date = date('Y-m-d');
                $due_date = date('Y-m-d', strtotime("+$loan_duration days"));
                
                // Create transaction
                $pdo->beginTransaction();
                
                $trans_sql = "INSERT INTO transactions (book_id, member_id, issue_date, due_date, status, handled_by) 
                              VALUES (?, ?, ?, ?, 'Issued', ?)";
                $trans_stmt = $pdo->prepare($trans_sql);
                $trans_stmt->execute([$book_id, $member_id, $issue_date, $due_date, $admin_id]);
                
                // Update book availability
                $update_sql = "UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$book_id]);
                
                $pdo->commit();
                
                $_SESSION['success_message'] = "Book issued successfully! Due date: " . date('M d, Y', strtotime($due_date));
                header('Location: index.php');
                exit();
            }
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Get available books
$books_stmt = $pdo->query("SELECT book_id, accession_number, title, author, available_copies 
                           FROM books WHERE status = 'Active' AND available_copies > 0 
                           ORDER BY title");
$books = $books_stmt->fetchAll();

// Get active members
$members_stmt = $pdo->query("SELECT member_id, unique_identifier, full_name, member_type, class_or_department 
                             FROM members WHERE status = 'Active' ORDER BY full_name");
$members = $members_stmt->fetchAll();
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-book-reader"></i> Issue Book</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Transactions
            </a>
        </div>
    </div>

    <div class="form-container">
        <form method="POST" class="form-grid" id="issueForm">
            <div class="form-section">
                <h3><i class="fas fa-user"></i> Select Member</h3>
                
                <div class="form-group">
                    <label for="member_search">Search Member</label>
                    <input type="text" id="member_search" class="form-input" 
                           placeholder="Type to search member..." onkeyup="filterMembers()">
                </div>

                <div class="form-group">
                    <label for="member_id">Member <span class="required">*</span></label>
                    <select name="member_id" id="member_id" class="form-select" required onchange="showMemberInfo()">
                        <option value="">Select Member</option>
                        <?php foreach ($members as $member): ?>
                        <option value="<?php echo $member['member_id']; ?>" 
                                data-name="<?php echo htmlspecialchars($member['full_name']); ?>"
                                data-id="<?php echo htmlspecialchars($member['unique_identifier']); ?>"
                                data-type="<?php echo $member['member_type']; ?>"
                                data-class="<?php echo htmlspecialchars($member['class_or_department']); ?>">
                            <?php echo htmlspecialchars($member['unique_identifier'] . ' - ' . $member['full_name'] . ' (' . $member['member_type'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="member_info" class="info-box" style="display: none;">
                    <h4>Member Information</h4>
                    <p><strong>Name:</strong> <span id="info_name"></span></p>
                    <p><strong>ID:</strong> <span id="info_id"></span></p>
                    <p><strong>Type:</strong> <span id="info_type"></span></p>
                    <p><strong>Class/Dept:</strong> <span id="info_class"></span></p>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-book"></i> Select Book</h3>
                
                <div class="form-group">
                    <label for="book_search">Search Book</label>
                    <input type="text" id="book_search" class="form-input" 
                           placeholder="Type to search book..." onkeyup="filterBooks()">
                </div>

                <div class="form-group">
                    <label for="book_id">Book <span class="required">*</span></label>
                    <select name="book_id" id="book_id" class="form-select" required onchange="showBookInfo()">
                        <option value="">Select Book</option>
                        <?php foreach ($books as $book): ?>
                        <option value="<?php echo $book['book_id']; ?>"
                                data-accession="<?php echo htmlspecialchars($book['accession_number']); ?>"
                                data-title="<?php echo htmlspecialchars($book['title']); ?>"
                                data-author="<?php echo htmlspecialchars($book['author']); ?>"
                                data-available="<?php echo $book['available_copies']; ?>">
                            <?php echo htmlspecialchars($book['accession_number'] . ' - ' . $book['title'] . ' by ' . $book['author']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="book_info" class="info-box" style="display: none;">
                    <h4>Book Information</h4>
                    <p><strong>Accession #:</strong> <span id="info_accession"></span></p>
                    <p><strong>Title:</strong> <span id="info_title"></span></p>
                    <p><strong>Author:</strong> <span id="info_author"></span></p>
                    <p><strong>Available Copies:</strong> <span id="info_available"></span></p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Issue Book
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function filterMembers() {
    const search = document.getElementById('member_search').value.toLowerCase();
    const select = document.getElementById('member_id');
    const options = select.options;
    
    for (let i = 1; i < options.length; i++) {
        const text = options[i].text.toLowerCase();
        options[i].style.display = text.includes(search) ? '' : 'none';
    }
}

function filterBooks() {
    const search = document.getElementById('book_search').value.toLowerCase();
    const select = document.getElementById('book_id');
    const options = select.options;
    
    for (let i = 1; i < options.length; i++) {
        const text = options[i].text.toLowerCase();
        options[i].style.display = text.includes(search) ? '' : 'none';
    }
}

function showMemberInfo() {
    const select = document.getElementById('member_id');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        document.getElementById('info_name').textContent = option.dataset.name;
        document.getElementById('info_id').textContent = option.dataset.id;
        document.getElementById('info_type').textContent = option.dataset.type;
        document.getElementById('info_class').textContent = option.dataset.class;
        document.getElementById('member_info').style.display = 'block';
    } else {
        document.getElementById('member_info').style.display = 'none';
    }
}

function showBookInfo() {
    const select = document.getElementById('book_id');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        document.getElementById('info_accession').textContent = option.dataset.accession;
        document.getElementById('info_title').textContent = option.dataset.title;
        document.getElementById('info_author').textContent = option.dataset.author;
        document.getElementById('info_available').textContent = option.dataset.available;
        document.getElementById('book_info').style.display = 'block';
    } else {
        document.getElementById('book_info').style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
