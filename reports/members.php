<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Member Reports";
include '../includes/header.php';
include '../includes/db_connect.php';

try {
    // Members by Type
    $by_type = $pdo->query("
        SELECT member_type, COUNT(*) as count, 
               SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
               SUM(CASE WHEN status != 'Active' THEN 1 ELSE 0 END) as inactive
        FROM members
        GROUP BY member_type
    ")->fetchAll();
    
    // Members by Class/Department
    $by_class_dept = $pdo->query("
        SELECT class_or_department, COUNT(*) as count
        FROM members
        WHERE status = 'Active'
        GROUP BY class_or_department
        ORDER BY count DESC
    ")->fetchAll();
    
    // Top Borrowers - Allow full view for printing
    $view_all = isset($_GET['view']) && $_GET['view'] === 'all';
    $limit_sql = $view_all ? "" : "LIMIT 10";
    
    $top_borrowers = $pdo->query("
        SELECT m.full_name, m.unique_identifier, m.member_type, COUNT(t.transaction_id) as borrow_count
        FROM members m
        JOIN transactions t ON m.member_id = t.member_id
        GROUP BY m.member_id
        ORDER BY borrow_count DESC
        $limit_sql
    ")->fetchAll();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Member Reports</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <?php if (!$view_all): ?>
                <a href="?view=all" class="btn btn-info">
                    <i class="fas fa-list"></i> View All Data
                </a>
            <?php else: ?>
                <a href="members.php" class="btn btn-info">
                    <i class="fas fa-compress-alt"></i> Show Less
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <div id="members-report">
        <div class="report-print-header" style="display: none;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="margin: 0;">Drumvale Secondary School</h1>
                <h2 style="margin: 0.5rem 0; color: #64748b;">Library Member Report</h2>
                <p style="margin: 0; color: #94a3b8;">Generated on: <?php echo date('F d, Y H:i'); ?></p>
            </div>
        </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="report-grid">
        <div class="report-section">
            <h3><i class="fas fa-user-tag"></i> Membership Summary</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Member Type</th>
                            <th>Total</th>
                            <th>Active</th>
                            <th>Inactive</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($by_type as $row): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['member_type']); ?></strong></td>
                            <td><?php echo $row['count']; ?></td>
                            <td><span class="badge badge-success"><?php echo $row['active']; ?></span></td>
                            <td><span class="badge badge-secondary"><?php echo $row['inactive']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-section">
            <h3><i class="fas fa-medal"></i> Top 10 Borrowers</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Type</th>
                            <th>Books Borrowed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_borrowers as $row): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($row['unique_identifier']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['member_type']); ?></td>
                            <td><span class="badge badge-primary"><?php echo $row['borrow_count']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="report-section" style="margin-top: 2rem;">
        <h3><i class="fas fa-school"></i> Active Members by Class/Department</h3>
        <div class="stats-cards-mini">
            <?php foreach ($by_class_dept as $row): ?>
                <div class="stat-card-mini">
                    <span class="label"><?php echo htmlspecialchars($row['class_or_department']); ?></span>
                    <span class="value"><?php echo $row['count']; ?></span>
                </div>
            <?php endforeach; ?>
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
    .report-grid {
        display: block !important;
    }
    .report-section {
        margin-bottom: 20px;
        box-shadow: none !important;
        border: 1px solid #e2e8f0 !important;
        break-inside: avoid;
    }
    .stat-card-mini {
        break-inside: avoid;
    }
}

.report-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
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

.stats-cards-mini {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}

.stat-card-mini {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    text-align: center;
}

.stat-card-mini .label {
    display: block;
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.stat-card-mini .value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
}
</style>

<?php include '../includes/footer.php'; ?>
