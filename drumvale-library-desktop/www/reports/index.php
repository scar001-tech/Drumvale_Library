<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Reports Dashboard";
include '../includes/header.php';
include '../includes/db_connect.php';

try {
    // Get statistics for reports
    $stats = [];
    
    // Books statistics
    $books_stats = $pdo->query("SELECT COUNT(*) as total, SUM(total_copies) as copies, SUM(available_copies) as available FROM books WHERE status = 'Active'")->fetch();
    $stats['books'] = $books_stats;
    
    // Members statistics
    $members_stats = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN member_type = 'Student' THEN 1 ELSE 0 END) as students, SUM(CASE WHEN member_type = 'Teacher' THEN 1 ELSE 0 END) as teachers FROM members WHERE status = 'Active'")->fetch();
    $stats['members'] = $members_stats;
    
    // Transactions statistics
    $trans_stats = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Issued' THEN 1 ELSE 0 END) as active, SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue FROM transactions")->fetch();
    $stats['transactions'] = $trans_stats;
    
    // Fines statistics
    $fines_stats = $pdo->query("SELECT COUNT(*) as total, SUM(total_fine) as amount, SUM(amount_paid) as paid FROM fines WHERE payment_status != 'Paid'")->fetch();
    $stats['fines'] = $fines_stats;
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar"></i> Reports Dashboard</h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="reports-grid">
        <a href="books.php" class="report-card">
            <div class="report-icon" style="background: #3b82f6;">
                <i class="fas fa-book"></i>
            </div>
            <div class="report-details">
                <h3>Book Reports</h3>
                <p><?php echo $stats['books']['total']; ?> books, <?php echo $stats['books']['copies']; ?> total copies</p>
                <p class="report-meta"><?php echo $stats['books']['available']; ?> available</p>
            </div>
            <div class="report-action">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>

        <a href="members.php" class="report-card">
            <div class="report-icon" style="background: #10b981;">
                <i class="fas fa-users"></i>
            </div>
            <div class="report-details">
                <h3>Member Reports</h3>
                <p><?php echo $stats['members']['total']; ?> active members</p>
                <p class="report-meta"><?php echo $stats['members']['students']; ?> students, <?php echo $stats['members']['teachers']; ?> teachers</p>
            </div>
            <div class="report-action">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>

        <a href="transactions.php" class="report-card">
            <div class="report-icon" style="background: #f59e0b;">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="report-details">
                <h3>Transaction Reports</h3>
                <p><?php echo $stats['transactions']['total']; ?> total transactions</p>
                <p class="report-meta"><?php echo $stats['transactions']['active']; ?> active, <?php echo $stats['transactions']['overdue']; ?> overdue</p>
            </div>
            <div class="report-action">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>

        <a href="fines.php" class="report-card">
            <div class="report-icon" style="background: #ef4444;">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="report-details">
                <h3>Fine Reports</h3>
                <p><?php echo $stats['fines']['total']; ?> pending fines</p>
                <p class="report-meta">KSh <?php echo number_format($stats['fines']['amount'] - $stats['fines']['paid'], 2); ?> outstanding</p>
            </div>
            <div class="report-action">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>

        <a href="inventory.php" class="report-card">
            <div class="report-icon" style="background: #8b5cf6;">
                <i class="fas fa-warehouse"></i>
            </div>
            <div class="report-details">
                <h3>Inventory Report</h3>
                <p>Complete book inventory</p>
                <p class="report-meta">By subject and category</p>
            </div>
            <div class="report-action">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>
    </div>
</div>

<style>
.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.report-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
}

.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.report-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.report-details {
    flex: 1;
}

.report-details h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    color: #1f2937;
}

.report-details p {
    margin: 0.25rem 0;
    color: #6b7280;
    font-size: 0.9rem;
}

.report-meta {
    font-size: 0.85rem !important;
    color: #9ca3af !important;
}

.report-action {
    color: #9ca3af;
    font-size: 1.2rem;
}
</style>

<?php include '../includes/footer.php'; ?>
