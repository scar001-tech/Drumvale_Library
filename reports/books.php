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
    // Overall counts
    $book_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_titles,
            SUM(total_copies) as total_copies,
            SUM(available_copies) as available_copies
        FROM books WHERE status = 'Active'
    ")->fetch();

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
    
    // Most borrowed books - Allow full view for printing
    $view_all = isset($_GET['view']) && $_GET['view'] === 'all';
    $limit_sql = $view_all ? "" : "LIMIT 10";
    
    $most_borrowed = $pdo->query("
        SELECT b.title, b.author, b.accession_number, COUNT(t.transaction_id) as borrow_count
        FROM books b
        LEFT JOIN transactions t ON b.book_id = t.book_id
        WHERE b.status = 'Active'
        GROUP BY b.book_id
        ORDER BY borrow_count DESC
        $limit_sql
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
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <?php if (!$view_all): ?>
                <a href="?view=all" class="btn btn-info">
                    <i class="fas fa-list"></i> View All Data
                </a>
            <?php else: ?>
                <a href="books.php" class="btn btn-info">
                    <i class="fas fa-compress-alt"></i> Show Less
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <div id="books-report">
        <div class="report-print-header" style="display: none;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="margin: 0;">Drumvale Secondary School</h1>
                <h2 style="margin: 0.5rem 0; color: #64748b;">Library Book Report</h2>
                <p style="margin: 0; color: #94a3b8;">Generated on: <?php echo date('F d, Y H:i'); ?></p>
            </div>
        </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
            <h3 style="font-size: 2rem; margin: 0; color: #1e293b;"><?php echo $book_stats['total_titles']; ?></h3>
            <p style="color: #64748b; margin: 0.5rem 0 0 0; font-weight: 500;">Total Titles</p>
        </div>
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid #3b82f6;">
            <h3 style="font-size: 2rem; margin: 0; color: #1e293b;"><?php echo $book_stats['total_copies']; ?></h3>
            <p style="color: #64748b; margin: 0.5rem 0 0 0; font-weight: 500;">Total Copies</p>
        </div>
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid #10b981;">
            <h3 style="font-size: 2rem; margin: 0; color: #1e293b;"><?php echo $book_stats['available_copies']; ?></h3>
            <p style="color: #64748b; margin: 0.5rem 0 0 0; font-weight: 500;">Available Now</p>
        </div>
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid #f59e0b;">
            <h3 style="font-size: 2rem; margin: 0; color: #1e293b;"><?php echo $book_stats['total_copies'] - $book_stats['available_copies']; ?></h3>
            <p style="color: #64748b; margin: 0.5rem 0 0 0; font-weight: 500;">Currently Issued</p>
        </div>
    </div>

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

    </div>
</div>

<style>
.report-print-header {
    display: none !important;
}

@media print {
    .report-print-header {
        display: block !important;
    }
    .page-header, .page-actions, .main-nav, .main-footer, .btn, .alert {
        display: none !important;
    }
    .page-container {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    .report-section {
        box-shadow: none !important;
        border: 1px solid #e2e8f0 !important;
        break-inside: avoid;
    }
    .stat-card {
        box-shadow: none !important;
        border: 1px solid #e2e8f0 !important;
    }
}

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
</style>

<?php include '../includes/footer.php'; ?>
