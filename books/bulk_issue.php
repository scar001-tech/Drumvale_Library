<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db_connect.php';

// Get system settings
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$settings = [];
while ($row = $settings_stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'];
    $book_key = $_POST['book_key']; // Contains "title||author"
    $issue_mode = $_POST['issue_mode']; // 'range' or 'quantity'
    $admin_id = $_SESSION['admin_id'];
    
    if (empty($member_id) || empty($book_key)) {
        $_SESSION['error_message'] = "Please select both a member and a book.";
    } else {
        // Parse title and author
        list($book_title, $book_author) = explode('||', $book_key);
        
        // Find books to issue
        $books_to_issue = [];
        
        try {
            if ($issue_mode === 'range') {
                $start_acc = trim($_POST['acc_start']);
                $end_acc = trim($_POST['acc_end']);
                
                if (empty($start_acc) || empty($end_acc)) {
                    throw new Exception("Please specify both start and end accession numbers.");
                }

                $stmt = $pdo->prepare("
                    SELECT book_id, accession_number, title, status, available_copies 
                    FROM books 
                    WHERE title = ? AND author = ? AND accession_number >= ? AND accession_number <= ?
                    AND status = 'Active'
                    ORDER BY accession_number ASC
                ");
                $stmt->execute([$book_title, $book_author, $start_acc, $end_acc]);
                $books_to_issue = $stmt->fetchAll();
                
            } else {
                $quantity = intval($_POST['quantity'] ?? 0);
                if ($quantity < 1) {
                    throw new Exception("Please enter a valid number of copies.");
                }
                
                $stmt = $pdo->prepare("
                    SELECT book_id, accession_number, title, status, available_copies 
                    FROM books 
                    WHERE title = ? AND author = ? AND status = 'Active' AND available_copies > 0
                    ORDER BY accession_number ASC
                    LIMIT ?
                ");
                $stmt->execute([$book_title, $book_author, $quantity]);
                $books_to_issue = $stmt->fetchAll();
                
                if (count($books_to_issue) < $quantity) {
                    $_SESSION['warning_message'] = "Only " . count($books_to_issue) . " copies were available and found.";
                }
            }

            if (empty($books_to_issue)) {
                $_SESSION['error_message'] = "No available books found matching your criteria.";
            } else {
                // Get member details for limits
                $member_stmt = $pdo->prepare("SELECT full_name, member_type FROM members WHERE member_id = ?");
                $member_stmt->execute([$member_id]);
                $member = $member_stmt->fetch();
                
                // Check borrow limit
                $limit_key = strtolower($member['member_type']) . '_borrow_limit';
                $borrow_limit = $settings[$limit_key] ?? 2;
                
                $active_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions WHERE member_id = ? AND status = 'Issued'");
                $active_stmt->execute([$member_id]);
                $current_active = $active_stmt->fetch()['count'];
                
                $loan_duration = $settings[strtolower($member['member_type']) . '_loan_duration'] ?? 14;
                $issue_date = date('Y-m-d');
                $due_date = date('Y-m-d', strtotime("+$loan_duration days"));

                foreach ($books_to_issue as $book) {
                    if ($current_active >= $borrow_limit) {
                        $results[] = [
                            'accession' => $book['accession_number'],
                            'title' => $book['title'],
                            'status' => 'error',
                            'message' => "Limit reached ($borrow_limit books maximum)"
                        ];
                        continue;
                    }

                    if ($book['available_copies'] < 1) {
                        $results[] = [
                            'accession' => $book['accession_number'],
                            'title' => $book['title'],
                            'status' => 'error',
                            'message' => "Book is already issued"
                        ];
                        continue;
                    }

                    // Process Issue
                    $pdo->beginTransaction();
                    $trans_stmt = $pdo->prepare("INSERT INTO transactions (book_id, member_id, issue_date, due_date, status, handled_by) VALUES (?, ?, ?, ?, 'Issued', ?)");
                    $trans_stmt->execute([$book['book_id'], $member_id, $issue_date, $due_date, $admin_id]);
                    
                    $update_stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?");
                    $update_stmt->execute([$book['book_id']]);
                    $pdo->commit();

                    $current_active++;
                    $results[] = [
                        'accession' => $book['accession_number'],
                        'title' => $book['title'],
                        'status' => 'success',
                        'message' => "Successfully issued"
                    ];
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['error_message'] = $e->getMessage();
        }
    }
}

// Get active members
$members = $pdo->query("SELECT member_id, unique_identifier, full_name, member_type, class_or_department FROM members WHERE status = 'Active' ORDER BY full_name")->fetchAll();

// Get unique book titles + authors
$books_list = $pdo->query("SELECT DISTINCT title, author FROM books WHERE status = 'Active' ORDER BY title")->fetchAll();

$page_title = "Bulk Issue Books";
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-layer-group"></i> Bulk Issue Books</h1>
        <div class="page-actions">
            <a href="../transactions/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Transactions
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['warning_message'])): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['warning_message']; unset($_SESSION['warning_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($results)): ?>
        <div class="results-section mb-4">
            <h3><i class="fas fa-tasks"></i> Processing Results</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Accession #</th>
                            <th>Book Title</th>
                            <th>Status</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $res): ?>
                        <tr class="<?php echo $res['status'] === 'success' ? 'row-success' : 'row-error'; ?>">
                            <td><code><?php echo htmlspecialchars($res['accession']); ?></code></td>
                            <td><?php echo htmlspecialchars($res['title']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $res['status'] === 'success' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($res['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($res['message']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <a href="bulk_issue.php" class="btn btn-primary">Process More</a>
            </div>
        </div>
    <?php else: ?>

    <div class="form-container">
        <form method="POST" class="bulk-form">
            <div class="form-grid">
                <!-- Step 1: Member -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> 1. Select Member</h3>
                    <div class="form-group">
                        <label>Search Member</label>
                        <input type="text" id="member_search" class="form-input" placeholder="Type name or ID..." onkeyup="filterItems('member_search', 'member_id')">
                    </div>
                    <div class="form-group">
                        <label for="member_id">Member <span class="required">*</span></label>
                        <select name="member_id" id="member_id" class="form-select" required onchange="updateInfoDisplay('member_id', 'member_info')">
                            <option value="">-- Choose Member --</option>
                            <?php foreach ($members as $m): ?>
                            <option value="<?php echo $m['member_id']; ?>" 
                                    data-info="<?php echo htmlspecialchars($m['unique_identifier'] . ' | ' . $m['member_type'] . ' | ' . $m['class_or_department']); ?>">
                                <?php echo htmlspecialchars($m['unique_identifier'] . ' - ' . $m['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="member_info" class="info-bubble" style="display:none;"></div>
                </div>

                <!-- Step 2: Book -->
                <div class="form-section">
                    <h3><i class="fas fa-book"></i> 2. Select Book</h3>
                    <div class="form-group">
                        <label>Search Book</label>
                        <input type="text" id="book_search" class="form-input" placeholder="Type title or author..." onkeyup="filterItems('book_search', 'book_key')">
                    </div>
                    <div class="form-group">
                        <label for="book_key">Book <span class="required">*</span></label>
                        <select name="book_key" id="book_key" class="form-select" required>
                            <option value="">-- Choose Book --</option>
                            <?php foreach ($books_list as $b): ?>
                            <option value="<?php echo htmlspecialchars($b['title'] . '||' . $b['author']); ?>">
                                <?php echo htmlspecialchars($b['title'] . ' by ' . $b['author']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Step 3: Method -->
                <div class="form-section full-width">
                    <h3><i class="fas fa-cogs"></i> 3. Issue Method</h3>
                    <div class="method-toggle">
                        <label class="radio-card">
                            <input type="radio" name="issue_mode" value="quantity" checked onchange="toggleMode('quantity')">
                            <div class="card-content">
                                <i class="fas fa-hashtag"></i>
                                <span>By Number of Copies</span>
                            </div>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="issue_mode" value="range" onchange="toggleMode('range')">
                            <div class="card-content">
                                <i class="fas fa-arrows-alt-h"></i>
                                <span>By Accession Range</span>
                            </div>
                        </label>
                    </div>

                    <div id="mode_quantity" class="mode-container">
                        <div class="form-group">
                            <label for="quantity">Number of Copies to Issue</label>
                            <input type="number" name="quantity" id="quantity" class="form-input" min="1" placeholder="e.g. 5">
                        </div>
                    </div>

                    <div id="mode_range" class="mode-container" style="display:none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="acc_start">From Accession #</label>
                                <input type="text" name="acc_start" id="acc_start" class="form-input" placeholder="e.g. ACC001">
                            </div>
                            <div class="form-group">
                                <label for="acc_end">To Accession #</label>
                                <input type="text" name="acc_end" id="acc_end" class="form-input" placeholder="e.g. ACC010">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fas fa-check-double"></i> Confirm Bulk Issue
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
function filterItems(searchId, selectId) {
    const search = document.getElementById(searchId).value.toLowerCase();
    const select = document.getElementById(selectId);
    const options = select.options;
    for (let i = 1; i < options.length; i++) {
        const text = options[i].text.toLowerCase();
        options[i].style.display = text.includes(search) ? '' : 'none';
    }
}

function updateInfoDisplay(selectId, displayId) {
    const select = document.getElementById(selectId);
    const display = document.getElementById(displayId);
    const option = select.options[select.selectedIndex];
    if (option.value) {
        display.innerHTML = `<i class="fas fa-info-circle"></i> ${option.dataset.info}`;
        display.style.display = 'block';
    } else {
        display.style.display = 'none';
    }
}

function toggleMode(mode) {
    document.getElementById('mode_quantity').style.display = (mode === 'quantity') ? 'block' : 'none';
    document.getElementById('mode_range').style.display = (mode === 'range') ? 'block' : 'none';
    
    // Clear other mode inputs
    if (mode === 'quantity') {
        document.getElementById('acc_start').value = '';
        document.getElementById('acc_end').value = '';
    } else {
        document.getElementById('quantity').value = '';
    }
}
</script>

<style>
.full-width { grid-column: span 2; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
.info-bubble { background: #e0f2fe; color: #0369a1; padding: 10px; border-radius: 6px; font-size: 0.85rem; margin-top: 10px; border: 1px solid #bae6fd; }

.method-toggle { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
.radio-card { flex: 1; cursor: pointer; }
.radio-card input { display: none; }
.radio-card .card-content { border: 2px solid #e2e8f0; border-radius: 10px; padding: 1.5rem; text-align: center; transition: all 0.2s; }
.radio-card input:checked + .card-content { border-color: #2563eb; background: #eff6ff; color: #1e40af; }
.card-content i { display: block; font-size: 1.5rem; margin-bottom: 0.5rem; }
.card-content span { font-weight: 600; }

.mode-container { background: #f8fafc; padding: 1.5rem; border-radius: 10px; border: 1px dashed #cbd5e1; }

.row-success { background-color: #f0fdf4; }
.row-error { background-color: #fef2f2; }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
.badge-success { background: #dcfce7; color: #166534; }
.badge-danger { background: #fecaca; color: #991b1b; }

@media (max-width: 768px) {
    .form-grid { grid-template-columns: 1fr; }
    .full-width { grid-column: auto; }
}
</style>

<?php include '../includes/footer.php'; ?>
