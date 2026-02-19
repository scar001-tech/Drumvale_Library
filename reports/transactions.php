<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Transaction Reports";
include '../includes/header.php';
include '../includes/db_connect.php';

try {
    // Overall Stats
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Issued' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned,
            SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN status = 'Lost' THEN 1 ELSE 0 END) as lost
        FROM transactions
    ")->fetch();
    
    // Monthly Trends (Last 6 Months)
    // Calculate the date 6 months ago in PHP to ensure cross-database compatibility
    $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
    
    // Check database type for appropriate date formatting
    $db_type = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    if ($db_type === 'sqlite') {
        $monthly_sql = "
            SELECT strftime('%m-%Y', issue_date) as month_val, 
                   strftime('%b %Y', issue_date) as month, 
                   COUNT(*) as count
            FROM transactions
            WHERE issue_date >= ?
            GROUP BY month_val
            ORDER BY issue_date ASC
        ";
    } else {
        $monthly_sql = "
            SELECT DATE_FORMAT(issue_date, '%b %Y') as month, COUNT(*) as count
            FROM transactions
            WHERE issue_date >= ?
            GROUP BY month
            ORDER BY issue_date ASC
        ";
    }
    
    $monthly_stmt = $pdo->prepare($monthly_sql);
    $monthly_stmt->execute([$sixMonthsAgo]);
    $monthly_trends = $monthly_stmt->fetchAll();
    
    // Recent Transactions - Allow full view for printing
    $view_all = isset($_GET['view']) && $_GET['view'] === 'all';
    $limit_sql = $view_all ? "" : "LIMIT 15";
    
    $recent = $pdo->query("
        SELECT t.*, b.title, m.full_name
        FROM transactions t
        JOIN books b ON t.book_id = b.book_id
        JOIN members m ON t.member_id = m.member_id
        ORDER BY t.created_at DESC
        $limit_sql
    ")->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-exchange-alt"></i> Transaction Reports</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <?php if (!$view_all): ?>
                <a href="?view=all" class="btn btn-info">
                    <i class="fas fa-list"></i> View All Data
                </a>
            <?php else: ?>
                <a href="transactions.php" class="btn btn-info">
                    <i class="fas fa-compress-alt"></i> Show Less
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <div id="transactions-report">
        <div class="report-print-header" style="display: none;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="margin: 0;">Drumvale Secondary School</h1>
                <h2 style="margin: 0.5rem 0; color: #64748b;">Library Transaction Report</h2>
                <p style="margin: 0; color: #94a3b8;">Generated on: <?php echo date('F d, Y H:i'); ?></p>
            </div>
        </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-details">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Transactions</p>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="stat-details">
                <h3><?php echo $stats['active']; ?></h3>
                <p>Currently Issued</p>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #ef4444;">
            <div class="stat-details">
                <h3><?php echo $stats['overdue']; ?></h3>
                <p>Overdue Books</p>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #10b981;">
            <div class="stat-details">
                <h3><?php echo $stats['returned']; ?></h3>
                <p>Total Returned</p>
            </div>
        </div>
    </div>

    <div class="report-section" style="margin-top: 2rem;">
        <h3><i class="fas fa-history"></i> Recent Activity (Last 15 Transactions)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Book</th>
                        <th>Member</th>
                        <th>Status</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $t): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($t['issue_date'])); ?></td>
                        <td><?php echo htmlspecialchars($t['title']); ?></td>
                        <td><?php echo htmlspecialchars($t['full_name']); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $t['status'] === 'Returned' ? 'success' : 
                                    ($t['status'] === 'Overdue' ? 'danger' : 'primary'); 
                            ?>">
                                <?php echo $t['status']; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($t['due_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
    .page-header, .btn, .main-nav, .main-footer {
        display: none !important;
    }
    .page-container {
        padding: 0;
        margin: 0;
        width: 100%;
    }
    .report-section {
        box-shadow: none !important;
        border: 1px solid #e2e8f0 !important;
    }
}
</style>

<?php 
if (isset($_GET['print'])) {
    $inline_js = "window.onload = function() { window.print(); }";
}
?>

<?php include '../includes/footer.php'; ?>
