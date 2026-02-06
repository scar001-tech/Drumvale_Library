<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../includes/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$book_id = $data['book_id'] ?? 0;

try {
    // Check if book has active borrows
    $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions WHERE book_id = ? AND status = 'Issued'");
    $check_stmt->execute([$book_id]);
    $active_borrows = $check_stmt->fetch()['count'];
    
    if ($active_borrows > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete book with active borrows']);
        exit();
    }
    
    // Get book details for logging
    $book_stmt = $pdo->prepare("SELECT title, author, accession_number FROM books WHERE book_id = ?");
    $book_stmt->execute([$book_id]);
    $book = $book_stmt->fetch();
    
    // Soft delete - mark as deleted instead of removing
    $stmt = $pdo->prepare("UPDATE books SET status = 'Deleted' WHERE book_id = ?");
    $stmt->execute([$book_id]);
    
    // Log activity
    logActivity($pdo, $_SESSION['admin_id'], 'DELETE', 'books', $book_id, $book, null);
    
    echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
