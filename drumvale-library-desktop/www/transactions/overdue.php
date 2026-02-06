<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Overdue Books";
include '../includes/header.php';
include '../includes/db_connect.php';

try {
    $sql = "SELECT t.*, b.accession_number, b.title, b.author, m.unique_identifier, m.full_name, 
                   m.member_type, m.phone_number, m.parent_guardian_phone,
                   DATEDIFF(CURDATE(), t.due_date) as days_overdue,
                   f.total_fine, f.payment_status
            FROM transactions t
            JOIN books b ON t.book_id = b.book_id
            JOIN members m ON t.member_id = m.member_id
            LEFT JOIN fines f ON t.transaction_id = f.transaction_id
            WHERE t.status = 'Issued' AND t.due_date < CURDATE()
            ORDER BY t.due_date ASC";
    
    $stmt = $pdo->query($sql);
    $overdue_books = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-exclamation-triangle"></i> Overdue Books</h1>
        <div class="page-actions">
            <a href="return.php" class="btn btn-primary">
                <i class="fas fa-undo"></i> Return Book
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i>
        <strong><?php echo count($overdue_books); ?></strong> book(s) are currently overdue
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Accession #</th>
                    <th>Book Title</th>
                    <th>Borrower</th>
                    <th>Contact</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Days Overdue</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($overdue_books)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No overdue books</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($overdue_books as $book): ?>
                        <tr class="overdue-row">
                            <td><?php echo htmlspecialchars($book['accession_number']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                <small><?php echo htmlspecialchars($book['author']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($book['full_name']); ?><br>
                                <small><?php echo htmlspecialchars($book['unique_identifier']); ?> (<?php echo $book['member_type']; ?>)</small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($book['phone_number'] ?? 'N/A'); ?><br>
                                <?php if ($book['member_type'] === 'Student' && $book['parent_guardian_phone']): ?>
                                    <small>Parent: <?php echo htmlspecialchars($book['parent_guardian_phone']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                            <td>
                                <span class="badge badge-danger">
                                    <?php echo $book['days_overdue']; ?> days
                                </span>
                            </td>
                            <td class="actions">
                                <a href="return.php" class="btn btn-sm btn-success" title="Return Book">
                                    <i class="fas fa-undo"></i>
                                </a>
                                <?php if ($book['phone_number']): ?>
                                <a href="tel:<?php echo $book['phone_number']; ?>" class="btn btn-sm btn-info" title="Call Member">
                                    <i class="fas fa-phone"></i>
                                </a>
                                <?php endif; ?>
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
