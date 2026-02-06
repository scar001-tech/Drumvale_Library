<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../includes/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$member_id = $data['member_id'] ?? 0;

try {
    // Check if member has active borrows
    $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions WHERE member_id = ? AND status = 'Issued'");
    $check_stmt->execute([$member_id]);
    $active_borrows = $check_stmt->fetch()['count'];
    
    if ($active_borrows > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete member with active borrows']);
        exit();
    }
    
    $stmt = $pdo->prepare("DELETE FROM members WHERE member_id = ?");
    $stmt->execute([$member_id]);
    
    echo json_encode(['success' => true, 'message' => 'Member deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
