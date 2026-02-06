<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Collect Fine Payment";
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
    
    $balance = $fine['total_fine'] - $fine['amount_paid'];
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $payment_amount = floatval($_POST['payment_amount']);
        $new_total_paid = $fine['amount_paid'] + $payment_amount;
        
        if ($new_total_paid > $fine['total_fine']) {
            $_SESSION['error_message'] = "Payment amount exceeds balance!";
        } else {
            $new_status = ($new_total_paid >= $fine['total_fine']) ? 'Paid' : 'Partial';
            $payment_date = ($new_status === 'Paid') ? date('Y-m-d') : null;
            
            $sql = "UPDATE fines SET amount_paid = ?, payment_status = ?, payment_date = ? WHERE fine_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_total_paid, $new_status, $payment_date, $fine_id]);
            
            $_SESSION['success_message'] = "Payment of KSh " . number_format($payment_amount, 2) . " collected successfully!";
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-money-bill"></i> Collect Fine Payment</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Fines
            </a>
        </div>
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
            <h3><i class="fas fa-book"></i> Book Information</h3>
            <div class="details-content">
                <div class="detail-row">
                    <span class="detail-label">Title:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($fine['title']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Accession #:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($fine['accession_number']); ?></span>
                </div>
            </div>
        </div>

        <div class="details-card">
            <h3><i class="fas fa-receipt"></i> Fine Details</h3>
            <div class="details-content">
                <div class="detail-row">
                    <span class="detail-label">Fine Type:</span>
                    <span class="detail-value"><?php echo $fine['fine_type']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Days Overdue:</span>
                    <span class="detail-value"><?php echo $fine['days_overdue']; ?> days</span>
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
                    <span class="detail-value"><strong class="text-danger">KSh <?php echo number_format($balance, 2); ?></strong></span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-container">
        <form method="POST">
            <h3><i class="fas fa-hand-holding-usd"></i> Collect Payment</h3>
            
            <div class="form-group">
                <label for="payment_amount">Payment Amount (KSh) <span class="required">*</span></label>
                <input type="number" name="payment_amount" id="payment_amount" 
                       class="form-input" step="0.01" min="0.01" 
                       max="<?php echo $balance; ?>" 
                       value="<?php echo $balance; ?>" required>
                <small class="form-help">Maximum: KSh <?php echo number_format($balance, 2); ?></small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Collect Payment
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
