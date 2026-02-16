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
        $book_condition = $_POST['book_condition'] ?? 'Good';
        $admin_id = $_SESSION['admin_id'];
        $return_date = date('Y-m-d');
        
        // Get transaction and book details
        $trans_stmt = $pdo->prepare("
            SELECT t.*, b.price, b.book_id 
            FROM transactions t 
            JOIN books b ON t.book_id = b.book_id 
            WHERE t.transaction_id = ?
        ");
        $trans_stmt->execute([$transaction_id]);
        $transaction = $trans_stmt->fetch();

        if (!$transaction) {
            throw new Exception("Transaction not found.");
        }
        
        $pdo->beginTransaction();
        
        // Update transaction status
        $status = ($book_condition === 'Lost') ? 'Lost' : 'Returned';
        $update_sql = "UPDATE transactions SET return_date = ?, status = ? WHERE transaction_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$return_date, $status, $transaction_id]);
        
        // Update book availability and condition
        if ($book_condition === 'Lost') {
            // If lost, it's not available and its status is updated
            $book_sql = "UPDATE books SET condition_status = 'Lost', status = 'Archived', available_copies = GREATEST(0, available_copies) WHERE book_id = ?";
            $book_stmt = $pdo->prepare($book_sql);
            $book_stmt->execute([$transaction['book_id']]);
        } else {
            // If returned (Good or Damaged), increment availability and update condition
            $book_sql = "UPDATE books SET available_copies = available_copies + 1, condition_status = ? WHERE book_id = ?";
            $book_stmt = $pdo->prepare($book_sql);
            $book_stmt->execute([$book_condition, $transaction['book_id']]);
        }
        
        $messages = [];

        // 1. Calculate Overdue Fine
        $due_date = new DateTime($transaction['due_date']);
        $return_dt = new DateTime($return_date);
        
        if ($return_dt > $due_date) {
            $days_overdue = $return_dt->diff($due_date)->days;
            $overdue_fine = $days_overdue * $fine_rate;
            
            $fine_sql = "INSERT INTO fines (transaction_id, member_id, fine_type, days_overdue, fine_rate, total_fine, payment_status) 
                         VALUES (?, ?, 'Overdue', ?, ?, ?, 'Pending')";
            $fine_stmt = $pdo->prepare($fine_sql);
            $fine_stmt->execute([$transaction_id, $transaction['member_id'], $days_overdue, $fine_rate, $overdue_fine]);
            
            $messages[] = "Overdue fine of KSh " . number_format($overdue_fine, 2) . " applied ($days_overdue days).";
        }

        // 2. Calculate Damage/Lost Fine
        if ($book_condition === 'Damaged' || $book_condition === 'Lost') {
            $fine_amount = $transaction['price'] ?? 0;
            $fine_type = ($book_condition === 'Damaged') ? 'Damage' : 'Lost Book';
            
            if ($fine_amount > 0) {
                $fine_sql = "INSERT INTO fines (transaction_id, member_id, fine_type, total_fine, payment_status, notes) 
                             VALUES (?, ?, ?, ?, 'Pending', ?)";
                $fine_stmt = $pdo->prepare($fine_sql);
                $fine_stmt->execute([$transaction_id, $transaction['member_id'], $fine_type, $fine_amount, "Book returned as $book_condition"]);
                
                $messages[] = "$fine_type fine of KSh " . number_format($fine_amount, 2) . " applied based on book price.";
            } else {
                $messages[] = "Warning: No price set for this book. $fine_type fine could not be automatically calculated.";
            }
        }
        
        if (empty($messages)) {
            $_SESSION['success_message'] = "Book returned successfully in Good condition!";
        } else {
            $_SESSION['warning_message'] = "Book processed: " . implode(" ", $messages);
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
    SELECT t.*, b.accession_number, b.title, b.author, b.price, m.unique_identifier, m.full_name, m.member_type,
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

    <!-- Search Box -->
    <div class="search-card">
        <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="returnBookSearch" class="search-input" 
                   placeholder="Search by accession number, title, author, or borrower..." 
                   autocomplete="off">
            <button type="button" id="clearSearch" class="search-clear-btn" title="Clear search" style="display:none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="search-info">
            <span id="searchResultCount"><?php echo count($issued_books); ?></span> of <?php echo count($issued_books); ?> issued books shown
        </div>
    </div>

    <div class="table-container">
        <table class="data-table" id="returnBooksTable">
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
                <tr id="noIssuedRow">
                    <td colspan="8" class="text-center">No issued books</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($issued_books as $book): ?>
                    <tr class="book-row <?php echo $book['days_overdue'] > 0 ? 'overdue-row' : ''; ?>"
                        data-accession="<?php echo htmlspecialchars(strtolower($book['accession_number'])); ?>"
                        data-title="<?php echo htmlspecialchars(strtolower($book['title'])); ?>"
                        data-author="<?php echo htmlspecialchars(strtolower($book['author'])); ?>"
                        data-borrower="<?php echo htmlspecialchars(strtolower($book['full_name'])); ?>"
                        data-identifier="<?php echo htmlspecialchars(strtolower($book['unique_identifier'])); ?>">
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
                            <form method="POST" style="display: flex; gap: 8px; flex-direction: column;">
                                <input type="hidden" name="transaction_id" value="<?php echo $book['transaction_id']; ?>">
                                <div style="display: flex; gap: 5px;">
                                    <select name="book_condition" class="form-select form-select-sm" required style="padding: 2px 5px; font-size: 0.85rem;">
                                        <option value="Good">Good Condition</option>
                                        <option value="Damaged">Damaged</option>
                                        <option value="Lost">Lost</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-success" 
                                            onclick="return confirm('Confirm return of this book?')">
                                        <i class="fas fa-check"></i> Return
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="noResultsRow" style="display:none;">
                    <td colspan="8" class="text-center">
                        <div class="no-results-message">
                            <i class="fas fa-search" style="font-size: 1.5rem; color: #94a3b8; margin-bottom: 8px;"></i>
                            <p style="margin: 0; color: #64748b;">No books match your search.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.overdue-row {
    background-color: #fee;
}

/* Search Card Styles */
.search-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
}

.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 16px;
    color: #94a3b8;
    font-size: 1rem;
    pointer-events: none;
    transition: color 0.2s ease;
}

.search-input {
    width: 100%;
    padding: 12px 44px 12px 44px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.95rem;
    color: #1e293b;
    background: #f8fafc;
    transition: all 0.3s ease;
    outline: none;
}

.search-input:focus {
    border-color: #6366f1;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.search-input:focus + .search-icon,
.search-input:focus ~ .search-icon {
    color: #6366f1;
}

.search-wrapper:focus-within .search-icon {
    color: #6366f1;
}

.search-input::placeholder {
    color: #94a3b8;
    font-weight: 400;
}

.search-clear-btn {
    position: absolute;
    right: 12px;
    background: #e2e8f0;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    font-size: 0.75rem;
    transition: all 0.2s ease;
}

.search-clear-btn:hover {
    background: #cbd5e1;
    color: #1e293b;
}

.search-info {
    margin-top: 10px;
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 500;
}

.search-info span {
    color: #6366f1;
    font-weight: 700;
}

.no-results-message {
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Animation for showing/hiding rows */
.book-row {
    transition: opacity 0.15s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('returnBookSearch');
    const clearBtn = document.getElementById('clearSearch');
    const resultCount = document.getElementById('searchResultCount');
    const noResultsRow = document.getElementById('noResultsRow');
    const bookRows = document.querySelectorAll('.book-row');
    const totalBooks = bookRows.length;

    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        
        // Toggle clear button
        clearBtn.style.display = query.length > 0 ? 'flex' : 'none';

        let visibleCount = 0;

        bookRows.forEach(function(row) {
            const accession = row.getAttribute('data-accession') || '';
            const title = row.getAttribute('data-title') || '';
            const author = row.getAttribute('data-author') || '';
            const borrower = row.getAttribute('data-borrower') || '';
            const identifier = row.getAttribute('data-identifier') || '';

            const matches = query === '' ||
                accession.includes(query) ||
                title.includes(query) ||
                author.includes(query) ||
                borrower.includes(query) ||
                identifier.includes(query);

            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        // Update count
        resultCount.textContent = visibleCount;

        // Show/hide no results message
        if (noResultsRow) {
            noResultsRow.style.display = (visibleCount === 0 && totalBooks > 0) ? '' : 'none';
        }
    });

    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        searchInput.focus();
    });

    // Allow keyboard shortcut: Ctrl+F or / to focus search
    document.addEventListener('keydown', function(e) {
        if ((e.key === '/' && !e.ctrlKey && !e.metaKey && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'SELECT' && document.activeElement.tagName !== 'TEXTAREA') ||
            (e.ctrlKey && e.key === 'f' && !e.metaKey)) {
            // Only prevent default for Ctrl+F to avoid browser search
            if (e.ctrlKey) e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
