<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Members Management";
$additional_css = ['assets/css/tables.css'];
include '../includes/header.php';
include '../includes/db_connect.php';

// Handle search and filters
$search = $_GET['search'] ?? '';
$member_type = $_GET['member_type'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(m.full_name LIKE ? OR m.unique_identifier LIKE ? OR m.phone_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($member_type)) {
    $where_conditions[] = "m.member_type = ?";
    $params[] = $member_type;
}

if (!empty($status)) {
    $where_conditions[] = "m.status = ?";
    $params[] = $status;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM members m WHERE $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_members = $stmt->fetch()['total'];
    $total_pages = ceil($total_members / $per_page);

    // Get members
    $sql = "SELECT m.*, 
                   COUNT(DISTINCT t.transaction_id) as total_borrows,
                   COUNT(DISTINCT CASE WHEN t.status = 'Issued' THEN t.transaction_id END) as active_borrows
            FROM members m 
            LEFT JOIN transactions t ON m.member_id = t.member_id
            WHERE $where_clause 
            GROUP BY m.member_id
            ORDER BY m.created_at DESC 
            LIMIT $per_page OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll();

    // Get statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN member_type = 'Student' THEN 1 ELSE 0 END) as students,
                    SUM(CASE WHEN member_type = 'Teacher' THEN 1 ELSE 0 END) as teachers,
                    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active
                  FROM members";
    $stats = $pdo->query($stats_sql)->fetch();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Members Management</h1>
        <div class="page-actions">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Register Member
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #3b82f6;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Members</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #10b981;">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['students']; ?></h3>
                <p>Students</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #f59e0b;">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['teachers']; ?></h3>
                <p>Teachers</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #8b5cf6;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $stats['active']; ?></h3>
                <p>Active Members</p>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search members..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="form-input">
            </div>
            
            <div class="filter-group">
                <select name="member_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="Student" <?php echo $member_type === 'Student' ? 'selected' : ''; ?>>Students</option>
                    <option value="Teacher" <?php echo $member_type === 'Teacher' ? 'selected' : ''; ?>>Teachers</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Left School" <?php echo $status === 'Left School' ? 'selected' : ''; ?>>Left School</option>
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

    <!-- Members Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Class/Department</th>
                    <th>Phone</th>
                    <th>Active Borrows</th>
                    <th>Total Borrows</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="9" class="text-center">No members found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['unique_identifier']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge <?php echo $member['member_type'] === 'Student' ? 'badge-info' : 'badge-warning'; ?>">
                                    <?php echo $member['member_type']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($member['class_or_department']); ?></td>
                            <td><?php echo htmlspecialchars($member['phone_number'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="badge <?php echo $member['active_borrows'] > 0 ? 'badge-primary' : 'badge-secondary'; ?>">
                                    <?php echo $member['active_borrows']; ?>
                                </span>
                            </td>
                            <td><?php echo $member['total_borrows']; ?></td>
                            <td>
                                <span class="badge <?php echo $member['status'] === 'Active' ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $member['status']; ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="view.php?id=<?php echo $member['member_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $member['member_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($member['active_borrows'] == 0): ?>
                                    <button onclick="deleteMember(<?php echo $member['member_id']; ?>)" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&member_type=<?php echo urlencode($member_type); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-secondary">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <span class="pagination-info">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                (<?php echo $total_members; ?> total members)
            </span>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&member_type=<?php echo urlencode($member_type); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-secondary">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteMember(memberId) {
    if (confirm('Are you sure you want to delete this member? This action cannot be undone.')) {
        fetch('delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({member_id: memberId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error deleting member');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
