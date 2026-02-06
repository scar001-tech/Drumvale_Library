<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Include database connection first for processing
include '../includes/db_connect.php';

// Get fine rate
$fine_rate_stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'fine_rate_per_day'");
$fine_rate = $fine_rate_stmt->fetch()['setting_value'] ?? 5.00;

// Handle form submission BEFORE including header (to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $transaction_id = $_POST['transaction_id'];
        $admin_id = $_SESSION['admin_id'];
        $return_date = date('Y-m-d');
        
        // Get transaction details
        $trans_stmt = $pdo->prepare("SELECT * FROM transactions WHERE transaction_id = ?");
        $trans_stmt->execute([$transaction_id]);
        $transaction = $trans_stmt->fetch();
        
        $pdo->beginTransaction();
        
        // Update transaction
        $update_sql = "UPDATE transactions SET return_date = ?, status = 'Returned' WHERE transaction_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$return_date, $transaction_id]);
        
        // Update book availability
        $book_sql = "UPDATE books SET available_copies = available_copies + 1 WHERE book_id = ?";
        $book_stmt = $pdo->prepare($book_sql);
        $book_stmt->execute([$transaction['book_id']]);
        
        // Calculate fine if overdue
        $due_date = new DateTime($transaction['due_date']);
        $return_dt = new DateTime($return_date);
        
        if ($return_dt > $due_date) {
            $days_overdue = $return_dt->diff($due_date)->days;
            $total_fine = $days_overdue * $fine_rate;
            
            $fine_sql = "INSERT INTO fines (transaction_id, member_id, fine_type, days_overdue, fine_rate, total_fine, payment_status) 
                         VALUES (?, ?, 'Overdue', ?, ?, ?, 'Pending')";
            $fine_stmt = $pdo->prepare($fine_sql);
            $fine_stmt->execute([$transaction_id, $transaction['member_id'], $days_overdue, $fine_rate, $total_fine]);
            
            $_SESSION['warning_message'] = "Book returned successfully! Fine of KSh " . number_format($total_fine, 2) . " applied for $days_overdue days overdue.";
        } else {
            $_SESSION['success_message'] = "Book returned successfully!";
        }
        
        $pdo->commit();
        header('Location: index.php');
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Get issued books (for display)
$issued_stmt = $pdo->query("
    SELECT t.*, b.accession_number, b.title, b.author, m.unique_identifier, m.full_name, m.member_type,
           DATEDIFF(CURDATE(), t.due_date) as days_overdue
    FROM transactions t
    JOIN books b ON t.book_id = b.book_id
    JOIN members m ON t.member_id = m.member_id
    WHERE t.status = 'Issued'
    ORDER BY t.due_date ASC
");
$issued_books = $issued_stmt->fetchAll();

// Now include header after all redirect logic (HTML output starts here)
$page_title = "Return Book";
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-undo"></i> Return Book</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Transactions
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Accession #</th>
                    <th>Book Title</th>
                    <th>Borrower</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Days Overdue</th>
                    <th>Estimated Fine</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($issued_books)): ?>
                <tr>
                    <td colspan="8" class="text-center">No issued books</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($issued_books as $book): ?>
                    <tr class="<?php echo $book['days_overdue'] > 0 ? 'overdue-row' : ''; ?>">
                        <td><?php echo htmlspecialchars($book['accession_number']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                            <small><?php echo htmlspecialchars($book['author']); ?></small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($book['full_name']); ?><br>
                            <small><?php echo htmlspecialchars($book['unique_identifier']); ?></small>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                        <td>
                            <?php if ($book['days_overdue'] > 0): ?>
                                <span class="badge badge-danger"><?php echo $book['days_overdue']; ?> days</span>
                            <?php else: ?>
                                <span class="badge badge-success">On time</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($book['days_overdue'] > 0): ?>
                                <strong>KSh <?php echo number_format($book['days_overdue'] * $fine_rate, 2); ?></strong>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="transaction_id" value="<?php echo $book['transaction_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success" 
                                        onclick="return confirm('Confirm return of this book?')">
                                    <i class="fas fa-check"></i> Return
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.overdue-row {
    background-color: #fee;
}
</style>

<?php include '../includes/footer.php'; ?>
