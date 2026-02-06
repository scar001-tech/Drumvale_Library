<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$book_id = intval($_GET['id'] ?? 0);
if (!$book_id) {
    header('Location: index.php');
    exit();
}

include '../includes/db_connect.php';

// Get book details and history BEFORE header
try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ? AND status != 'Deleted'");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    if (!$book) {
        $_SESSION['error_message'] = "Book not found!";
        header('Location: index.php');
        exit();
    }
    
    // Get borrowing history
    $history_stmt = $pdo->prepare("
        SELECT t.*, m.unique_identifier, m.full_name, m.member_type
        FROM transactions t
        JOIN members m ON t.member_id = m.member_id
        WHERE t.book_id = ?
        ORDER BY t.issue_date DESC
        LIMIT 10
    ");
    $history_stmt->execute([$book_id]);
    $history = $history_stmt->fetchAll();
    
    // Get current borrower if issued
    $current_borrower = null;
    if ($book['available_copies'] < $book['total_copies']) {
        $borrower_stmt = $pdo->prepare("
            SELECT t.*, m.unique_identifier, m.full_name, m.member_type, m.phone_number
            FROM transactions t
            JOIN members m ON t.member_id = m.member_id
            WHERE t.book_id = ? AND t.status = 'Issued'
            ORDER BY t.issue_date DESC
            LIMIT 1
        ");
        $borrower_stmt->execute([$book_id]);
        $current_borrower = $borrower_stmt->fetch();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: index.php');
    exit();
}

$page_title = "Book Details";
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-book"></i> Book Details</h1>
        <div class="page-actions">
            <a href="edit.php?id=<?php echo $book_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Book
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Books
            </a>
        </div>
    </div>

    <div class="details-grid">
        <!-- Basic Information -->
        <div class="details-card">
            <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
            <div class="details-content">
                <div class="detail-row">
                    <span class="detail-label">Accession Number:</span>
                    <span class="detail-value"><strong><?php echo htmlspecialchars($book['accession_number']); ?></strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Title:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($book['title']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Author:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($book['author']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Subject:</span>
                    <span class="detail-value">
                        <span class="badge badge-primary"><?php echo htmlspecialchars($book['subject']); ?></span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Category:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($book['category']); ?></span>
                </div>
                <?php if ($book['isbn']): ?>
                <div class="detail-row">
                    <span class="detail-label">ISBN:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($book['isbn']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Publication Details -->
        <div class="details-card">
            <h3><i class="fas fa-book-open"></i> Publication Details</h3>
            <div class="details-content">
                <?php if ($book['publisher']): ?>
                <div class="detail-row">
                    <span class="detail-label">Publisher:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($book['publisher']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($book['publication_year']): ?>
                <div class="detail-row">
                    <span class="detail-label">Publication Year:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($book['publication_year']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($book['edition']): ?>
                <div class="detail-row">
                    <span class="detail-label">Edition:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($book['edition']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($book['pages']): ?>
                <div class="detail-row">
                    <span class="detail-label">Pages:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($book['pages']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Library Information -->
        <div class="details-card">
            <h3><i class="fas fa-warehouse"></i> Library Information</h3>
            <div class="details-content">
                <div class="detail-row">
                    <span class="detail-label">Shelf Location:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($book['shelf_location']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Copies:</span>
                    <span class="detail-value"><?php echo $book['total_copies']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Available Copies:</span>
                    <span class="detail-value">
                        <span class="badge <?php echo $book['available_copies'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $book['available_copies']; ?>
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Issued Copies:</span>
                    <span class="detail-value"><?php echo $book['total_copies'] - $book['available_copies']; ?></span>
                </div>
                <?php if ($book['price']): ?>
                <div class="detail-row">
                    <span class="detail-label">Price:</span>
                    <span class="detail-value">KSh <?php echo number_format($book['price'], 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="detail-label">Condition:</span>
                    <span class="detail-value">
                        <span class="badge badge-<?php echo $book['condition_status'] === 'Good' ? 'success' : 'warning'; ?>">
                            <?php echo $book['condition_status']; ?>
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="badge badge-<?php echo $book['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                            <?php echo $book['status']; ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <?php if ($book['description'] || $book['notes']): ?>
        <div class="details-card">
            <h3><i class="fas fa-sticky-note"></i> Additional Information</h3>
            <div class="details-content">
                <?php if ($book['description']): ?>
                <div class="detail-row">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($book['description'])); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($book['notes']): ?>
                <div class="detail-row">
                    <span class="detail-label">Notes:</span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($book['notes'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Current Borrower -->
    <?php if ($current_borrower): ?>
    <div class="section-card">
        <h3><i class="fas fa-user-clock"></i> Currently Borrowed By</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Member ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($current_borrower['unique_identifier']); ?></td>
                        <td><?php echo htmlspecialchars($current_borrower['full_name']); ?></td>
                        <td><?php echo $current_borrower['member_type']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($current_borrower['issue_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($current_borrower['due_date'])); ?></td>
                        <td>
                            <?php
                            $is_overdue = strtotime($current_borrower['due_date']) < time();
                            ?>
                            <span class="badge badge-<?php echo $is_overdue ? 'danger' : 'primary'; ?>">
                                <?php echo $is_overdue ? 'Overdue' : 'Issued'; ?>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Borrowing History -->
    <div class="section-card">
        <h3><i class="fas fa-history"></i> Borrowing History</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Member ID</th>
                        <th>Member Name</th>
                        <th>Type</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No borrowing history</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($history as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['unique_identifier']); ?></td>
                            <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                            <td><?php echo $record['member_type']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['issue_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                            <td><?php echo $record['return_date'] ? date('M d, Y', strtotime($record['return_date'])) : '-'; ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $record['status'] === 'Returned' ? 'success' : 
                                        ($record['status'] === 'Overdue' ? 'danger' : 'primary'); 
                                ?>">
                                    <?php echo $record['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.details-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.details-card h3 {
    margin: 0 0 1rem 0;
    color: #1f2937;
    font-size: 1.1rem;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0.5rem;
}

.details-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 500;
    color: #6b7280;
    flex: 0 0 40%;
}

.detail-value {
    color: #1f2937;
    flex: 1;
    text-align: right;
}

.section-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.section-card h3 {
    margin: 0 0 1rem 0;
    color: #1f2937;
    font-size: 1.1rem;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0.5rem;
}
</style>

<?php include '../includes/footer.php'; ?>