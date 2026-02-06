<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

include 'includes/header.php';
include 'includes/db_connect.php';

// Initialize default values
$total_books = 0;
$available_books = 0;
$issued_books = 0;
$overdue_books = 0;
$total_students = 0;
$total_teachers = 0;
$pending_fines = 0;

// Get dashboard statistics
try {
    // Total books (sum of all copies for non-deleted books)
    $stmt = $pdo->query("SELECT SUM(total_copies) as total FROM books WHERE status != 'Deleted'");
    $total_books = $stmt->fetch()['total'] ?? 0;

    // Available books (sum of available copies for non-deleted books)
    $stmt = $pdo->query("SELECT SUM(available_copies) as available FROM books WHERE status != 'Deleted'");
    $available_books = $stmt->fetch()['available'] ?? 0;

    // Currently issued books
    $stmt = $pdo->query("SELECT COUNT(*) as issued FROM transactions WHERE status = 'Issued'");
    $issued_books = $stmt->fetch()['issued'];

    // Overdue books
    $stmt = $pdo->query("SELECT COUNT(*) as overdue FROM transactions WHERE status = 'Issued' AND due_date < CURDATE()");
    $overdue_books = $stmt->fetch()['overdue'];

    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as students FROM members WHERE member_type = 'Student' AND status = 'Active'");
    $total_students = $stmt->fetch()['students'];

    // Total teachers
    $stmt = $pdo->query("SELECT COUNT(*) as teachers FROM members WHERE member_type = 'Teacher' AND status = 'Active'");
    $total_teachers = $stmt->fetch()['teachers'];

    // Pending fines
    $stmt = $pdo->query("SELECT SUM(total_fine - amount_paid) as pending_fines FROM fines WHERE payment_status IN ('Pending', 'Partial')");
    $pending_fines = $stmt->fetch()['pending_fines'] ?? 0;
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Library Dashboard</h1>
        <p class="dashboard-subtitle">Drumvale Secondary School Library Management System</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card books">
            <div class="stat-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_books); ?></h3>
                <p>Total Books</p>
                <small><?php echo number_format($available_books); ?> available</small>
            </div>
        </div>

        <div class="stat-card issued">
            <div class="stat-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($issued_books); ?></h3>
                <p>Books Issued</p>
                <small>Currently borrowed</small>
            </div>
        </div>

        <div class="stat-card overdue">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($overdue_books); ?></h3>
                <p>Overdue Books</p>
                <small>Need attention</small>
            </div>
        </div>

        <div class="stat-card students">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_students); ?></h3>
                <p>Students</p>
                <small>Registered</small>
            </div>
        </div>

        <div class="stat-card teachers">
            <div class="stat-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_teachers); ?></h3>
                <p>Teachers</p>
                <small>Registered</small>
            </div>
        </div>

        <div class="stat-card fines">
            <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
                <h3>KSh <?php echo number_format($pending_fines, 2); ?></h3>
                <p>Pending Fines</p>
                <small>To be collected</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="action-buttons">
            <a href="books/add.php" class="action-btn add-book">
                <i class="fas fa-plus"></i>
                <span>Add Book</span>
            </a>
            <a href="members/add.php" class="action-btn add-member">
                <i class="fas fa-user-plus"></i>
                <span>Register Member</span>
            </a>
            <a href="transactions/issue.php" class="action-btn issue-book">
                <i class="fas fa-hand-holding"></i>
                <span>Issue Book</span>
            </a>
            <a href="transactions/return.php" class="action-btn return-book">
                <i class="fas fa-undo"></i>
                <span>Return Book</span>
            </a>
            <a href="reports/" class="action-btn reports">
                <i class="fas fa-chart-bar"></i>
                <span>View Reports</span>
            </a>
            <a href="settings/" class="action-btn settings">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h2><i class="fas fa-history"></i> Recent Activity</h2>
        <div class="activity-container">
            <?php
            try {
                // Handle date compatibility between MySQL and SQLite
                $db_driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                $cur_date = ($db_driver === 'sqlite') ? "date('now')" : "CURDATE()";

                // Fetch book transactions
                $transactions_sql = "
                    SELECT 
                        'transaction' as act_type,
                        t.created_at,
                        t.status as act_status,
                        b.title as main_text,
                        m.full_name as sub_text,
                        a.full_name as admin_name,
                        CASE 
                            WHEN t.status = 'Issued' AND t.due_date < $cur_date THEN 'Overdue'
                            ELSE t.status
                        END as display_status,
                        t.transaction_id as ref_id
                    FROM transactions t 
                    JOIN books b ON t.book_id = b.book_id 
                    JOIN members m ON t.member_id = m.member_id 
                    JOIN admins a ON t.handled_by = a.admin_id
                ";

                // Fetch admin activities
                $logs_sql = "
                    SELECT 
                        'system' as act_type,
                        l.created_at,
                        l.action as act_status,
                        l.table_affected as main_text,
                        l.new_values as sub_text,
                        a.full_name as admin_name,
                        l.action as display_status,
                        l.log_id as ref_id
                    FROM activity_log l
                    JOIN admins a ON l.admin_id = a.admin_id
                ";

                // Combine and sort - Wrapped in subquery for maximum compatibility across MySQL/SQLite
                $combined_sql = "SELECT * FROM (" . $transactions_sql . " UNION " . $logs_sql . ") AS combined_activity ORDER BY created_at DESC LIMIT 15";
                $stmt = $pdo->query($combined_sql);
                $activities = $stmt->fetchAll();

                if (!empty($activities)) {
                    echo '<div class="activity-list">';
                    foreach ($activities as $act) {
                        $time_ago = date('M j, g:i a', strtotime($act['created_at']));
                        $icon = 'fa-info-circle';
                        $color = '#64748b';
                        $title = '';
                        $subtitle = '';
                        $status = $act['display_status'];

                        if ($act['act_type'] === 'transaction') {
                            $is_issue = $act['act_status'] === 'Issued';
                            $icon = $is_issue ? 'fa-hand-holding' : 'fa-undo';
                            $color = $is_issue ? '#f59e0b' : '#10b981';
                            if ($act['display_status'] === 'Overdue') {
                                $color = '#ef4444';
                                $icon = 'fa-exclamation-circle';
                            }
                            $title = htmlspecialchars($act['main_text']);
                            $subtitle = ($is_issue ? "Issued to " : "Returned by ") . htmlspecialchars($act['sub_text']);
                        } else {
                            // System log
                            $action = $act['act_status'];
                            $new_vals = json_decode($act['sub_text'], true);
                            
                            switch($action) {
                                case 'BULK_IMPORT':
                                    $icon = 'fa-file-import';
                                    $color = '#8b5cf6';
                                    $title = "Bulk Book Import";
                                    $count = $new_vals['success_count'] ?? 0;
                                    $subtitle = "Imported $count books from spreadsheet";
                                    $status = "Imported";
                                    break;
                                case 'CREATE':
                                    $icon = 'fa-plus-circle';
                                    $color = '#3b82f6';
                                    if ($act['main_text'] === 'books') {
                                        $title = "New Book Added";
                                        $subtitle = htmlspecialchars($new_vals['title'] ?? 'Book');
                                    } else {
                                        $title = "New Recrod Created";
                                        $subtitle = "A new entry was added to " . $act['main_text'];
                                    }
                                    $status = "Added";
                                    break;
                                case 'DELETE':
                                    $icon = 'fa-trash-alt';
                                    $color = '#ef4444';
                                    $title = "Record Deleted";
                                    $subtitle = "Removal from " . $act['main_text'];
                                    $status = "Deleted";
                                    break;
                                default:
                                    $icon = 'fa-cog';
                                    $title = "System Action: " . $action;
                                    $subtitle = "Affected " . $act['main_text'];
                            }
                        }

                        echo "
                        <div class='activity-item'>
                            <div class='activity-icon' style='background-color: {$color}'>
                                <i class='fas {$icon}'></i>
                            </div>
                            <div class='activity-details'>
                                <div class='activity-main'>
                                    <strong>{$title}</strong>
                                    <span class='activity-admin'>by {$act['admin_name']}</span>
                                </div>
                                <div class='activity-sub'>{$subtitle}</div>
                                <div class='activity-meta'>
                                    <span class='status-badge' style='background-color: {$color}22; color: {$color}'>{$status}</span>
                                    <span class='activity-date'><i class='far fa-clock'></i> {$time_ago}</span>
                                </div>
                            </div>
                        </div>";
                    }
                    echo '</div>';
                } else {
                    echo '<div class="no-activity">
                            <i class="fas fa-inbox"></i>
                            <p>No activity recorded yet.</p>
                          </div>';
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-error">Error loading activity: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>
</div>

<style>
    /* Improved Activity Styles */
    .activity-admin {
        font-size: 0.8rem;
        color: #94a3b8;
        font-weight: normal;
        margin-left: 0.5rem;
    }
    .activity-meta {
        margin-top: 0.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .status-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .activity-date {
        font-size: 0.75rem;
        color: #64748b;
    }
    .activity-main strong {
        color: #1e293b;
    }
    .activity-sub {
        color: #64748b;
        font-size: 0.85rem;
        margin-top: 0.1rem;
    }
</style>

<style>
    /* Recent Activity Styles */
    .recent-activity {
        background: var(--white);
        border-radius: var(--radius-xl);
        padding: var(--spacing-6);
        box-shadow: var(--shadow-md);
        border: 1px solid var(--gray-200);
    }

    .recent-activity h2 {
        font-size: var(--font-size-xl);
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: var(--spacing-6);
        display: flex;
        align-items: center;
        gap: var(--spacing-3);
    }

    .recent-activity h2 i {
        color: var(--primary-color);
    }

    .activity-container {
        max-height: 400px;
        overflow-y: auto;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-4);
    }

    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: var(--spacing-4);
        padding: var(--spacing-4);
        border-radius: var(--radius-lg);
        background: var(--gray-50);
        transition: all var(--transition-fast);
    }

    .activity-item:hover {
        background: var(--gray-100);
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: var(--font-size-sm);
        flex-shrink: 0;
    }

    .activity-details {
        flex: 1;
        min-width: 0;
    }

    .activity-main {
        font-size: var(--font-size-base);
        color: var(--gray-800);
        margin-bottom: var(--spacing-1);
    }

    .activity-sub {
        font-size: var(--font-size-sm);
        color: var(--gray-600);
        margin-bottom: var(--spacing-2);
    }

    .activity-meta {
        display: flex;
        align-items: center;
        gap: var(--spacing-3);
        flex-wrap: wrap;
    }

    .status-badge {
        font-size: var(--font-size-xs);
        font-weight: 600;
        padding: var(--spacing-1) var(--spacing-2);
        border-radius: var(--radius-md);
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .status-badge.issued {
        background: #fef3c7;
        color: #92400e;
    }

    .status-badge.returned {
        background: #d1fae5;
        color: #065f46;
    }

    .status-badge.overdue {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-badge.lost {
        background: #f3f4f6;
        color: #374151;
    }

    .activity-date {
        font-size: var(--font-size-xs);
        color: var(--gray-500);
    }

    .no-activity {
        text-align: center;
        padding: var(--spacing-12) var(--spacing-6);
        color: var(--gray-500);
    }

    .no-activity i {
        font-size: var(--font-size-4xl);
        margin-bottom: var(--spacing-4);
        opacity: 0.5;
    }

    .no-activity p {
        font-size: var(--font-size-lg);
    }

    /* Scrollbar Styling */
    .activity-container::-webkit-scrollbar {
        width: 6px;
    }

    .activity-container::-webkit-scrollbar-track {
        background: var(--gray-100);
        border-radius: 3px;
    }

    .activity-container::-webkit-scrollbar-thumb {
        background: var(--gray-300);
        border-radius: 3px;
    }

    .activity-container::-webkit-scrollbar-thumb:hover {
        background: var(--gray-400);
    }
</style>

<?php include 'includes/footer.php'; ?>