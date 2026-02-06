<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Fines Management";
$additional_css = ['assets/css/tables.css'];
include '../includes/header.php';
include '../includes/db_connect.php';

// Handle filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(m.full_name LIKE ? OR m.unique_identifier LIKE ? OR b.title LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($status)) {
    $where_conditions[] = "f.payment_status = ?";
    $params[] = $status;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    $count_sql = "SELECT COUNT(*) as total FROM fines f 
                  JOIN members m ON f.member_id = m.member_id 
                  JOIN transactions t ON f.transaction_id = t.transaction_id
                  JOIN books b ON t.book_id = b.book_id
                  WHERE $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_fines = $stmt->fetch()['total'];
    $total_pages = ceil($total_fines / $per_page);

    $sql = "SELECT f.*, m.unique_identifier, m.full_name, m.member_type, 
                   b.title, b.accession_number, t.issue_date, t.due_date, t.return_date
            FROM fines f
            JOIN members m ON f.member_id = m.member_id
            JOIN transactions t ON f.transaction_id = t.transaction_id
            JOIN books b ON t.book_id = b.book_id
            WHERE $where_clause
            ORDER BY f.created_at DESC
            LIMIT $per_page OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $fines = $stmt->fetchAll();

    // Get statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total_fines,
                    SUM(total_fine) as total_amount,
                    SUM(amount_paid) as total_paid,
                    SUM(CASE WHEN payment_status = 'Pending' THEN total_fine - amount_paid ELSE 0 END) as pending_amount
                  FROM fines";
    $stats = $pdo->query($stats_sql)->fetch();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-money-bill-wave"></i> Fines Management</h1>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #3b82f6;">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['total_fines']; ?></h3>
                <p>Total Fines</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #ef4444;">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-details">
                <h3>KSh <?php echo number_format($stats['total_amount'], 2); ?></h3>
                <p>Total Amount</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #10b981;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h3>KSh <?php echo number_format($stats['total_paid'], 2); ?></h3>
                <p>Total Paid</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #f59e0b;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h3>KSh <?php echo number_format($stats['pending_amount'], 2); ?></h3>
                <p>Pending Amount</p>
            </div>
        </div>
    </div>

    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search fines..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="form-input">
            </div>
            
            <div class="filter-group">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Partial" <?php echo $status === 'Partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="Paid" <?php echo $status === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="Waived" <?php echo $status === 'Waived' ? 'selected' : ''; ?>>Waived</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fine ID</th>
                    <th>Member</th>
                    <th>Book</th>
                    <th>Fine Type</th>
                    <th>Days Overdue</th>
                    <th>Total Fine</th>
                    <th>Amount Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fines)): ?>
                    <tr>
                        <td colspan="10" class="text-center">No fines found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fines as $fine): ?>
                        <?php $balance = $fine['total_fine'] - $fine['amount_paid']; ?>
                        <tr>
                            <td>#<?php echo $fine['fine_id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($fine['full_name']); ?><br>
                                <small><?php echo htmlspecialchars($fine['unique_identifier']); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($fine['title']); ?></strong><br>
                                <small><?php echo htmlspecialchars($fine['accession_number']); ?></small>
                            </td>
                            <td><?php echo $fine['fine_type']; ?></td>
                            <td><?php echo $fine['days_overdue']; ?> days</td>
                            <td>KSh <?php echo number_format($fine['total_fine'], 2); ?></td>
                            <td>KSh <?php echo number_format($fine['amount_paid'], 2); ?></td>
                            <td>
                                <strong>KSh <?php echo number_format($balance, 2); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $fine['payment_status'] === 'Paid' ? 'success' : 
                                        ($fine['payment_status'] === 'Waived' ? 'info' : 
                                        ($fine['payment_status'] === 'Partial' ? 'warning' : 'danger')); 
                                ?>">
                                    <?php echo $fine['payment_status']; ?>
                                </span>
                            </td>
                            <td class="actions">
                                <?php if ($fine['payment_status'] !== 'Paid' && $fine['payment_status'] !== 'Waived'): ?>
                                <a href="collect.php?id=<?php echo $fine['fine_id']; ?>" class="btn btn-sm btn-success" title="Collect Payment">
                                    <i class="fas fa-money-bill"></i>
                                </a>
                                <a href="waive.php?id=<?php echo $fine['fine_id']; ?>" class="btn btn-sm btn-warning" title="Waive Fine">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-secondary">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <span class="pagination-info">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                (<?php echo $total_fines; ?> total fines)
            </span>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-secondary">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
