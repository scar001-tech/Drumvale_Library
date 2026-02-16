<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Fine Reports";
include '../includes/header.php';
include '../includes/db_connect.php';

try {
    // Collect stats first
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_count,
            SUM(total_fine) as total_amount,
            SUM(amount_paid) as total_paid,
            SUM(CASE WHEN payment_status = 'Pending' OR payment_status = 'Partial' THEN total_fine - amount_paid ELSE 0 END) as total_pending
        FROM fines
    ")->fetch();

    // Stats by Type
    $by_type = $pdo->query("
        SELECT fine_type, COUNT(*) as count, SUM(total_fine) as amount
        FROM fines
        GROUP BY fine_type
    ")->fetchAll();
    
    // Recent Payments
    $recent_payments = $pdo->query("
        SELECT f.*, m.full_name, m.unique_identifier, b.title
        FROM fines f
        JOIN members m ON f.member_id = m.member_id
        JOIN transactions t ON f.transaction_id = t.transaction_id
        JOIN books b ON t.book_id = b.book_id
        WHERE f.payment_status = 'Paid' OR f.payment_status = 'Partial'
        ORDER BY f.payment_date DESC
        LIMIT 10
    ")->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-money-bill-wave"></i> Fine Reports</h1>
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

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-details">
                <h3>KSh <?php echo number_format($stats['total_amount'] ?? 0, 2); ?></h3>
                <p>Lifetime Fines Imposed</p>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #10b981;">
            <div class="stat-details">
                <h3>KSh <?php echo number_format($stats['total_paid'] ?? 0, 2); ?></h3>
                <p>Total Collected</p>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-details">
                <h3>KSh <?php echo number_format($stats['total_pending'] ?? 0, 2); ?></h3>
                <p>Outstanding Balance</p>
            </div>
        </div>
    </div>

    <div class="report-grid" style="margin-top: 2rem;">
        <div class="report-section">
            <h3><i class="fas fa-filter"></i> Fines by Type</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fine Type</th>
                            <th>Count</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($by_type as $row): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['fine_type']); ?></strong></td>
                            <td><?php echo $row['count']; ?></td>
                            <td>KSh <?php echo number_format($row['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-section">
            <h3><i class="fas fa-receipt"></i> Recent Fine Payments</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Amount Paid</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $row): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($row['unique_identifier']); ?></small>
                            </td>
                            <td>KSh <?php echo number_format($row['amount_paid'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    font-size: 1.75rem;
    margin: 0;
    color: #1e293b;
}

.stat-card p {
    color: #64748b;
    margin: 0.5rem 0 0 0;
    font-weight: 500;
}

.report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.report-section {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.report-section h3 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: #1f2937;
    border-bottom: 2px solid #f3f4f6;
    padding-bottom: 0.75rem;
}

@media print {
    .page-header .page-actions,
    .main-nav {
        display: none !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
