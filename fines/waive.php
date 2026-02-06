<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Waive Fine";
include '../includes/header.php';
include '../includes/db_connect.php';

$fine_id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT f.*, m.unique_identifier, m.full_name, b.title, b.accession_number
        FROM fines f
        JOIN members m ON f.member_id = m.member_id
        JOIN transactions t ON f.transaction_id = t.transaction_id
        JOIN books b ON t.book_id = b.book_id
        WHERE f.fine_id = ?
    ");
    $stmt->execute([$fine_id]);
    $fine = $stmt->fetch();
    
    if (!$fine) {
        $_SESSION['error_message'] = "Fine not found!";
        header('Location: index.php');
        exit();
    }
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $waive_reason = trim($_POST['waive_reason']);
        $admin_id = $_SESSION['admin_id'];
        
        $sql = "UPDATE fines SET payment_status = 'Waived', waived_by = ?, waive_reason = ? WHERE fine_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$admin_id, $waive_reason, $fine_id]);
        
        $_SESSION['success_message'] = "Fine waived successfully!";
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-hand-holding-usd"></i> Waive Fine</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Fines
            </a>
        </div>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Warning:</strong> Waiving a fine will permanently mark it as waived. This action cannot be undone.
    </div>

    <div class="details-grid">
        <div class="details-card">
            <h3><i class="fas fa-user"></i> Member Information</h3>
            <div class="details-content">
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($fine['full_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">ID:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($fine['unique_identifier']); ?></span>
                </div>
            </div>
        </div>

        <div class="details-card">
            <h3><i class="fas fa-receipt"></i> Fine Details</h3>
            <div class="details-content">
                <div class="detail-row">
                    <span class="detail-label">Book:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($fine['title']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Fine Type:</span>
                    <span class="detail-value"><?php echo $fine['fine_type']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Fine:</span>
                    <span class="detail-value"><strong>KSh <?php echo number_format($fine['total_fine'], 2); ?></strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount Paid:</span>
                    <span class="detail-value">KSh <?php echo number_format($fine['amount_paid'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Balance:</span>
                    <span class="detail-value"><strong class="text-danger">KSh <?php echo number_format($fine['total_fine'] - $fine['amount_paid'], 2); ?></strong></span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-container">
        <form method="POST">
            <h3><i class="fas fa-comment-alt"></i> Waive Reason</h3>
            
            <div class="form-group">
                <label for="waive_reason">Reason for Waiving <span class="required">*</span></label>
                <textarea name="waive_reason" id="waive_reason" class="form-textarea" 
                          rows="4" required placeholder="Enter reason for waiving this fine..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to waive this fine?')">
                    <i class="fas fa-check"></i> Waive Fine
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
