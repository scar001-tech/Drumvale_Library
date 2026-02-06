<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Book Reports";
include '../includes/header.php';
include '../includes/db_connect.php';

try {
    // Books by subject
    $by_subject = $pdo->query("
        SELECT subject, COUNT(*) as count, SUM(total_copies) as copies, SUM(available_copies) as available
        FROM books WHERE status = 'Active'
        GROUP BY subject
        ORDER BY count DESC
    ")->fetchAll();
    
    // Books by category
    $by_category = $pdo->query("
        SELECT category, COUNT(*) as count, SUM(total_copies) as copies
        FROM books WHERE status = 'Active'
        GROUP BY category
        ORDER BY count DESC
    ")->fetchAll();
    
    // Most borrowed books
    $most_borrowed = $pdo->query("
        SELECT b.title, b.author, b.accession_number, COUNT(t.transaction_id) as borrow_count
        FROM books b
        LEFT JOIN transactions t ON b.book_id = t.book_id
        WHERE b.status = 'Active'
        GROUP BY b.book_id
        ORDER BY borrow_count DESC
        LIMIT 10
    ")->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-book"></i> Book Reports</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="report-section">
        <h3><i class="fas fa-chart-pie"></i> Books by Subject</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Number of Titles</th>
                        <th>Total Copies</th>
                        <th>Available Copies</th>
                        <th>Issued</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($by_subject as $row): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['subject']); ?></strong></td>
                        <td><?php echo $row['count']; ?></td>
                        <td><?php echo $row['copies']; ?></td>
                        <td><?php echo $row['available']; ?></td>
                        <td><?php echo $row['copies'] - $row['available']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="report-section">
        <h3><i class="fas fa-layer-group"></i> Books by Category</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Number of Titles</th>
                        <th>Total Copies</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($by_category as $row): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['category']); ?></strong></td>
                        <td><?php echo $row['count']; ?></td>
                        <td><?php echo $row['copies']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="report-section">
        <h3><i class="fas fa-star"></i> Most Borrowed Books</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Accession #</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Times Borrowed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($most_borrowed as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['accession_number']); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['author']); ?></td>
                        <td><span class="badge badge-primary"><?php echo $row['borrow_count']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.report-section {
    margin-bottom: 2rem;
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.report-section h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: #1f2937;
}

@media print {
    .page-header .page-actions,
    .main-nav,
    .filters-section {
        display: none !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
