<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Inventory Report";
include '../includes/header.php';
include '../includes/db_connect.php';

try {
    // Inventory Summary
    $summary = $pdo->query("
        SELECT 
            status, 
            condition_status,
            COUNT(*) as count,
            SUM(total_copies) as total_copies,
            SUM(available_copies) as available,
            SUM(price * total_copies) as total_value
        FROM books
        GROUP BY status, condition_status
    ")->fetchAll();

    // Books with low availability
    $low_stock = $pdo->query("
        SELECT title, accession_number, total_copies, available_copies
        FROM books
        WHERE available_copies = 0 AND total_copies > 0 AND status = 'Active'
        LIMIT 20
    ")->fetchAll();
    
    // Total Inventory Value
    $total_value = $pdo->query("SELECT SUM(price * total_copies) FROM books")->fetchColumn();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-warehouse"></i> Inventory Report</h1>
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
                <h3>KSh <?php echo number_format($total_value ?? 0, 2); ?></h3>
                <p>Estimated Inventory Value</p>
            </div>
        </div>
    </div>

    <div class="report-section" style="margin-top: 2rem;">
        <h3><i class="fas fa-th-list"></i> Status & Condition Breakdown</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Condition</th>
                        <th>Titles</th>
                        <th>Total Copies</th>
                        <th>Available</th>
                        <th>Estimated Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $row): ?>
                    <tr>
                        <td>
                            <span class="badge badge-<?php echo $row['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td><strong><?php echo htmlspecialchars($row['condition_status']); ?></strong></td>
                        <td><?php echo $row['count']; ?></td>
                        <td><?php echo $row['total_copies']; ?></td>
                        <td><?php echo $row['available']; ?></td>
                        <td>KSh <?php echo number_format($row['total_value'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="report-section" style="margin-top: 2rem;">
        <h3><i class="fas fa-exclamation-triangle"></i> Out of Stock / Fully Issued Books</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Accession #</th>
                        <th>Book Title</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($low_stock)): ?>
                        <tr><td colspan="3" class="text-center">No books are currently fully issued.</td></tr>
                    <?php else: ?>
                        <?php foreach ($low_stock as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['accession_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><span class="badge badge-warning">Fully Issued</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: 1fr;
    max-width: 400px;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    border-top: 4px solid #8b5cf6;
}

.stat-card h3 {
    font-size: 2rem;
    margin: 0;
    color: #1e293b;
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
