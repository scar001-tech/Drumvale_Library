<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Member Details";
include '../includes/header.php';
include '../includes/db_connect.php';

$member_id = $_GET['id'] ?? 0;

try {
    // Get member details
    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        $_SESSION['error_message'] = "Member not found!";
        header('Location: index.php');
        exit();
    }
    
    // Get borrowing history
    $trans_stmt = $pdo->prepare("
        SELECT t.*, b.title, b.author, b.accession_number
        FROM transactions t
        JOIN books b ON t.book_id = b.book_id
        WHERE t.member_id = ?
        ORDER BY t.issue_date DESC
        LIMIT 10
    ");
    $trans_stmt->execute([$member_id]);
    $transactions = $trans_stmt->fetchAll();
    
    // Get active fines
    $fines_stmt = $pdo->prepare("
        SELECT f.*, t.issue_date, t.due_date, b.title
        FROM fines f
        JOIN transactions t ON f.transaction_id = t.transaction_id
        JOIN books b ON t.book_id = b.book_id
        WHERE f.member_id = ? AND f.payment_status != 'Paid'
        ORDER BY f.created_at DESC
    ");
    $fines_stmt->execute([$member_id]);
    $fines = $fines_stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: index.php');
    exit();
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-user"></i> Member Details</h1>
        <div class="page-actions">
            <a href="edit.php?id=<?php echo $member_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Member
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="details-grid">
        <div class="details-card">
            <h3><i class="fas fa-id-card"></i> Basic Information</h3>
            <div class="details-content">
                <div class="detail-row">
                    <span class="detail-label">ID Number:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($member['unique_identifier']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Full Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($member['full_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Member Type:</span>
                    <span class="detail-value">
                        <span class="badge <?php echo $member['member_type'] === 'Student' ? 'badge-info' : 'badge-warning'; ?>">
                            <?php echo $member['member_type']; ?>
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php echo $member['member_type'] === 'Student' ? 'Class' : 'Department'; ?>:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($member['class_or_department']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="badge <?php echo $member['status'] === 'Active' ? 'badge-success' : 'badge-secondary'; ?>">
                            <?php echo $member['status']; ?>
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Registration Date:</span>
                    <span class="detail-value"><?php echo date('M d, Y', strtotime($member['registration_date'])); ?></span>
                </div>
            </div>
        </div>

        <div class="details-card">
            <h3><i class="fas fa-address-book"></i> Contact Information</h3>
            <div class="details-content">
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($member['phone_number'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($member['email'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($member['address'] ?? 'N/A'); ?></span>
                </div>
                <?php if ($member['member_type'] === 'Student'): ?>
                <div class="detail-row">
                    <span class="detail-label">Parent/Guardian:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($member['parent_guardian_name'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Guardian Phone:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($member['parent_guardian_phone'] ?? 'N/A'); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($fines)): ?>
    <div class="section-card">
        <h3><i class="fas fa-exclamation-triangle"></i> Active Fines</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Fine Type</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fines as $fine): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fine['title']); ?></td>
                        <td><?php echo $fine['fine_type']; ?></td>
                        <td>KSh <?php echo number_format($fine['total_fine'], 2); ?></td>
                        <td>KSh <?php echo number_format($fine['amount_paid'], 2); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $fine['payment_status'] === 'Pending' ? 'danger' : 'warning'; ?>">
                                <?php echo $fine['payment_status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-card">
        <h3><i class="fas fa-history"></i> Recent Borrowing History</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Accession #</th>
                        <th>Book Title</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No borrowing history</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $trans): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trans['accession_number']); ?></td>
                            <td><?php echo htmlspecialchars($trans['title']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($trans['issue_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($trans['due_date'])); ?></td>
                            <td><?php echo $trans['return_date'] ? date('M d, Y', strtotime($trans['return_date'])) : '-'; ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $trans['status'] === 'Returned' ? 'success' : 
                                        ($trans['status'] === 'Overdue' ? 'danger' : 'primary'); 
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
</div>

<?php include '../includes/footer.php'; ?>
