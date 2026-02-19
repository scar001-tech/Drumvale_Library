<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Transaction History";
$additional_css = ['assets/css/tables.css'];
include '../includes/header.php';
include '../includes/db_connect.php';

// Handle filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$view_all = isset($_GET['view']) && $_GET['view'] === 'all';
$per_page = $view_all ? 10000 : 20;
$offset = ($page - 1) * $per_page;

$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(b.title LIKE ? OR b.accession_number LIKE ? OR m.full_name LIKE ? OR m.unique_identifier LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($status)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    $count_sql = "SELECT COUNT(*) as total FROM transactions t 
                  JOIN books b ON t.book_id = b.book_id 
                  JOIN members m ON t.member_id = m.member_id 
                  WHERE $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_transactions = $stmt->fetch()['total'];
    $total_pages = ceil($total_transactions / $per_page);

    $sql = "SELECT t.*, b.accession_number, b.title, b.author, m.unique_identifier, m.full_name, m.member_type
            FROM transactions t
            JOIN books b ON t.book_id = b.book_id
            JOIN members m ON t.member_id = m.member_id
            WHERE $where_clause
            ORDER BY t.issue_date DESC
            LIMIT $per_page OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="page-hero hero-transactions">
    <div class="hero-content">
        <h2>Transaction History</h2>
        <p>Monitor the Flow of Knowledge</p>
    </div>
</div>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-exchange-alt"></i> History</h1>
        <div class="page-actions">
            <a href="issue.php" class="btn btn-primary">
                <i class="fas fa-book-reader"></i> Issue
            </a>
            <a href="return.php" class="btn btn-success">
                <i class="fas fa-undo"></i> Return
            </a>
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search transactions..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="form-input">
            </div>
            
            <div class="filter-group">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="Issued" <?php echo $status === 'Issued' ? 'selected' : ''; ?>>Issued</option>
                    <option value="Returned" <?php echo $status === 'Returned' ? 'selected' : ''; ?>>Returned</option>
                    <option value="Overdue" <?php echo $status === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                    <option value="Lost" <?php echo $status === 'Lost' ? 'selected' : ''; ?>>Lost</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            
            <?php if ($view_all || !empty($search) || !empty($status)): ?>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>

            <?php if (!$view_all): ?>
                <a href="?view=all&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-info">
                    <i class="fas fa-list"></i> View All For Printing
                </a>
            <?php else: ?>
                <a href="index.php?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-info">
                    <i class="fas fa-compress-alt"></i> Show Less
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div id="printable-transactions">
        <div class="report-print-header" style="display: none;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="margin: 0;">Drumvale Secondary School</h1>
                <h2 style="margin: 0.5rem 0; color: #64748b;">Library Transaction History</h2>
                <p style="margin: 0; color: #94a3b8;">Generated on: <?php echo date('F d, Y H:i'); ?></p>
            </div>
        </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Accession #</th>
                    <th>Book Title</th>
                    <th>Borrower</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No transactions found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $trans): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trans['accession_number']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($trans['title']); ?></strong><br>
                                <small><?php echo htmlspecialchars($trans['author']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($trans['full_name']); ?><br>
                                <small><?php echo htmlspecialchars($trans['unique_identifier']); ?></small>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($trans['issue_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($trans['due_date'])); ?></td>
                            <td><?php echo $trans['return_date'] ? date('M d, Y', strtotime($trans['return_date'])) : '-'; ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $trans['status'] === 'Returned' ? 'success' : 
                                        ($trans['status'] === 'Overdue' ? 'danger' : 
                                        ($trans['status'] === 'Lost' ? 'dark' : 'primary')); 
                                ?>">
                                    <?php echo $trans['status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>

    <?php if ($total_pages > 1 && !$view_all): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-secondary">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <span class="pagination-info">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                (<?php echo $total_transactions; ?> total transactions)
            </span>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-secondary">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.report-print-header {
    display: none !important;
}

@media print {
    .report-print-header {
        display: block !important;
    }
    .page-header, .page-actions, .filters-section, .pagination, .main-nav, .main-footer, .btn, .page-hero {
        display: none !important;
    }
    .page-container {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    .table-container {
        box-shadow: none !important;
        border: 1px solid #e2e8f0 !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
