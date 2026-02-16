<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db_connect.php';

// Get fine rate
$fine_rate_stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'fine_rate_per_day'");
$fine_rate = $fine_rate_stmt->fetch()['setting_value'] ?? 5.00;

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_ids'])) {
    $transaction_ids = $_POST['transaction_ids'];
    $admin_id = $_SESSION['admin_id'];
    $return_date = date('Y-m-d');
    
    foreach ($transaction_ids as $trans_id) {
        try {
            // Get transaction details
            $trans_stmt = $pdo->prepare("SELECT t.*, b.title, b.accession_number FROM transactions t JOIN books b ON t.book_id = b.book_id WHERE t.transaction_id = ?");
            $trans_stmt->execute([$trans_id]);
            $transaction = $trans_stmt->fetch();
            
            if (!$transaction || $transaction['status'] !== 'Issued') continue;

            $pdo->beginTransaction();
            
            // Update transaction
            $update_stmt = $pdo->prepare("UPDATE transactions SET return_date = ?, status = 'Returned' WHERE transaction_id = ?");
            $update_stmt->execute([$return_date, $trans_id]);
            
            // Update book availability
            $book_stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE book_id = ?");
            $book_stmt->execute([$transaction['book_id']]);
            
            // Calculate fine if overdue
            $due_date = new DateTime($transaction['due_date']);
            $return_dt = new DateTime($return_date);
            $fine_msg = "";
            
            if ($return_dt > $due_date) {
                $days_overdue = $return_dt->diff($due_date)->days;
                $total_fine = $days_overdue * $fine_rate;
                
                $fine_stmt = $pdo->prepare("INSERT INTO fines (transaction_id, member_id, fine_type, days_overdue, fine_rate, total_fine, payment_status) VALUES (?, ?, 'Overdue', ?, ?, ?, 'Pending')");
                $fine_stmt->execute([$trans_id, $transaction['member_id'], $days_overdue, $fine_rate, $total_fine]);
                $fine_msg = " [Fine of KSh " . number_format($total_fine, 2) . " applied]";
            }
            
            $pdo->commit();
            $results[] = [
                'accession' => $transaction['accession_number'],
                'title' => $transaction['title'],
                'status' => 'success',
                'message' => "Returned successfully$fine_msg"
            ];
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $results[] = [
                'accession' => $transaction['accession_number'] ?? 'ID: '.$trans_id,
                'title' => $transaction['title'] ?? 'Unknown',
                'status' => 'error',
                'message' => "Error: " . $e->getMessage()
            ];
        }
    }
}

// Filters
$member_id = $_GET['member_id'] ?? '';
$where = "t.status = 'Issued'";
$params = [];

if ($member_id) {
    $where .= " AND t.member_id = ?";
    $params[] = $member_id;
}

// Get issued books
$issued_books = $pdo->prepare("
    SELECT t.*, b.accession_number, b.title, b.author, m.unique_identifier, m.full_name, m.member_type,
           (JULIANDAY('now') - JULIANDAY(t.due_date)) as days_overdue
    FROM transactions t
    JOIN books b ON t.book_id = b.book_id
    JOIN members m ON t.member_id = m.member_id
    WHERE $where
    ORDER BY t.due_date ASC
");
$issued_books->execute($params);
$books = $issued_books->fetchAll();

// Get active members for filter
$members = $pdo->query("SELECT DISTINCT m.member_id, m.full_name, m.unique_identifier 
                        FROM members m 
                        JOIN transactions t ON m.member_id = t.member_id 
                        WHERE t.status = 'Issued' 
                        ORDER BY m.full_name")->fetchAll();

$page_title = "Bulk Return Books";
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-check"></i> Bulk Return Books</h1>
        <div class="page-actions">
            <a href="../transactions/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Transactions
            </a>
        </div>
    </div>

    <?php if (!empty($results)): ?>
        <div class="results-section mb-4">
            <h3>Processing Results</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Accession #</th>
                            <th>Book Title</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $res): ?>
                        <tr class="<?php echo $res['status'] === 'success' ? 'row-success' : 'row-error'; ?>">
                            <td><code><?php echo htmlspecialchars($res['accession']); ?></code></td>
                            <td><?php echo htmlspecialchars($res['title']); ?></td>
                            <td><?php echo htmlspecialchars($res['message']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <a href="bulk_return.php" class="btn btn-primary">Process More Returns</a>
            </div>
        </div>
    <?php else: ?>

    <div class="filter-card card mb-4">
        <div class="card-body">
            <form method="GET" class="filters-form" style="display:flex; gap:15px; align-items:flex-end;">
                <div class="form-group" style="flex:1;">
                    <label>Filter by Borrower</label>
                    <select name="member_id" class="form-select">
                        <option value="">-- All Borrowers --</option>
                        <?php foreach ($members as $m): ?>
                        <option value="<?php echo $m['member_id']; ?>" <?php echo $member_id == $m['member_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['unique_identifier'] . ' - ' . $m['full_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <a href="bulk_return.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>
    </div>

    <form method="POST" id="bulkReturnForm">
        <div class="bulk-sticky-bar mb-4">
            <div class="selection-info">
                <span id="selectedCount">0</span> books selected for return
            </div>
            <button type="submit" class="btn btn-success" id="submitBtn" disabled onclick="return confirm('Process return for all selected books?')">
                <i class="fas fa-check-double"></i> Confirm Bulk Return
            </button>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                        <th>Accession #</th>
                        <th>Book Title</th>
                        <th>Borrower</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($books)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No issued books found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($books as $book): 
                            $overdue = $book['days_overdue'] > 0;
                        ?>
                        <tr class="<?php echo $overdue ? 'row-overdue' : ''; ?>">
                            <td>
                                <input type="checkbox" name="transaction_ids[]" value="<?php echo $book['transaction_id']; ?>" class="row-checkbox">
                            </td>
                            <td><?php echo htmlspecialchars($book['accession_number']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                <small><?php echo htmlspecialchars($book['author']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($book['full_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                            <td>
                                <?php if ($overdue): ?>
                                    <span class="badge badge-danger"><?php echo ceil($book['days_overdue']); ?> days late</span>
                                <?php else: ?>
                                    <span class="badge badge-success">On Time</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const submitBtn = document.getElementById('submitBtn');
    const selectedCount = document.getElementById('selectedCount');

    function updateCounter() {
        const count = document.querySelectorAll('.row-checkbox:checked').length;
        selectedCount.textContent = count;
        submitBtn.disabled = count === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateCounter();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCounter);
    });
});
</script>

<style>
.bulk-sticky-bar {
    background: white;
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 10px;
    z-index: 100;
    border: 1px solid #e2e8f0;
}
.selection-info { font-weight: 600; color: #1e293b; }
.row-success { background-color: #f0fdf4; }
.row-error { background-color: #fef2f2; }
.row-overdue { background-color: #fff1f2; }
.badge { padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem; }
.badge-danger { background: #fecaca; color: #991b1b; }
.badge-success { background: #dcfce7; color: #166534; }
.card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; }
.mb-4 { margin-bottom: 1.5rem; }
.mt-4 { margin-top: 1.5rem; }
</style>

<?php include '../includes/footer.php'; ?>
