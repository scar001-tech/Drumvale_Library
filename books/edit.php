<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$book_id = intval($_GET['id'] ?? 0);
if (!$book_id) {
    header('Location: index.php');
    exit();
}

include '../includes/db_connect.php';

// Get book details
try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ? AND status != 'Deleted'");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    if (!$book) {
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

$page_title = "Edit Book";
include '../includes/header.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $publication_year = intval($_POST['publication_year'] ?? 0);
    $edition = trim($_POST['edition'] ?? '');
    $pages = intval($_POST['pages'] ?? 0);
    $shelf_location = trim($_POST['shelf_location'] ?? '');
    $total_copies = intval($_POST['total_copies'] ?? 1);
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    
    // Validation
    if (empty($title) || empty($author) || empty($subject)) {
        $error_message = 'Title, Author, and Subject are required fields.';
    } elseif ($total_copies < 1) {
        $error_message = 'Total copies must be at least 1.';
    } elseif ($total_copies < ($book['total_copies'] - $book['available_copies'])) {
        $issued_copies = $book['total_copies'] - $book['available_copies'];
        $error_message = "Cannot reduce total copies below $issued_copies (currently issued copies).";
    } else {
        try {
            // Store old values for logging
            $old_values = $book;
            
            // Calculate new available copies
            $issued_copies = $book['total_copies'] - $book['available_copies'];
            $new_available_copies = $total_copies - $issued_copies;
            
            // Update book
            $stmt = $pdo->prepare("
                UPDATE books SET 
                    title = ?, author = ?, isbn = ?, subject = ?, publisher = ?, 
                    publication_year = ?, edition = ?, pages = ?, 
                    shelf_location = ?, total_copies = ?, available_copies = ?, 
                    price = ?, description = ?, status = ?, updated_at = ?
                WHERE book_id = ?
            ");
            
            $stmt->execute([
                $title, $author, $isbn, $subject, $publisher,
                $publication_year ?: null, $edition ?: null, $pages ?: null, 
                $shelf_location, $total_copies, $new_available_copies, 
                $price ?: null, $description ?: null, $status, date('Y-m-d H:i:s'), $book_id
            ]);
            
            // Log activity
            logActivity($pdo, $_SESSION['admin_id'], 'UPDATE', 'books', $book_id, $old_values, [
                'title' => $title,
                'author' => $author,
                'total_copies' => $total_copies,
                'status' => $status
            ]);
            
            $success_message = "Book updated successfully!";
            
            // Refresh book data
            $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
            
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Get subjects for dropdown
try {
    $subjects_stmt = $pdo->query("SELECT DISTINCT subject FROM books WHERE status != 'Deleted' ORDER BY subject");
    $existing_subjects = $subjects_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $existing_subjects = [];
}

// Common KCSE subjects
$kcse_subjects = [
    'Mathematics', 'English', 'Kiswahili', 'Biology', 'Chemistry', 'Physics',
    'History', 'Geography', 'CRE', 'IRE', 'HRE', 'Business Studies',
    'Agriculture', 'Home Science', 'Art & Design', 'Music', 'German',
    'French', 'Arabic', 'Computer Studies', 'Aviation Technology',
    'Building Construction', 'Power Mechanics', 'General Science'
];

$all_subjects = array_unique(array_merge($kcse_subjects, $existing_subjects));
sort($all_subjects);
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Edit Book</h1>
        <div class="page-actions">
            <a href="view.php?id=<?php echo $book_id; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Books
            </a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <div class="book-info">
            <h3>Accession Number: <?php echo htmlspecialchars($book['accession_number']); ?></h3>
            <p>Created: <?php echo date('M j, Y g:i A', strtotime($book['created_at'])); ?></p>
            <?php if ($book['updated_at']): ?>
                <p>Last Updated: <?php echo date('M j, Y g:i A', strtotime($book['updated_at'])); ?></p>
            <?php endif; ?>
        </div>

        <form method="POST" class="book-form">
            <div class="form-grid">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                    
                    <div class="form-group">
                        <label for="title" class="form-label required">Book Title</label>
                        <input type="text" id="title" name="title" class="form-input" 
                               value="<?php echo htmlspecialchars($book['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="author" class="form-label required">Author</label>
                        <input type="text" id="author" name="author" class="form-input" 
                               value="<?php echo htmlspecialchars($book['author']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="isbn" class="form-label">ISBN</label>
                            <input type="text" id="isbn" name="isbn" class="form-input" 
                                   value="<?php echo htmlspecialchars($book['isbn']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="subject" class="form-label required">Subject</label>
                            <select id="subject" name="subject" class="form-select" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($all_subjects as $subj): ?>
                                    <option value="<?php echo htmlspecialchars($subj); ?>" 
                                            <?php echo $book['subject'] === $subj ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subj); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Publication Details -->
                <div class="form-section">
                    <h3><i class="fas fa-book-open"></i> Publication Details</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="publisher" class="form-label">Publisher</label>
                            <input type="text" id="publisher" name="publisher" class="form-input" 
                                   value="<?php echo htmlspecialchars($book['publisher']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="publication_year" class="form-label">Publication Year</label>
                            <input type="number" id="publication_year" name="publication_year" class="form-input" 
                                   min="1900" max="<?php echo date('Y'); ?>" 
                                   value="<?php echo $book['publication_year']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edition" class="form-label">Edition</label>
                            <input type="text" id="edition" name="edition" class="form-input" 
                                   value="<?php echo htmlspecialchars($book['edition']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="pages" class="form-label">Number of Pages</label>
                            <input type="number" id="pages" name="pages" class="form-input" min="1" 
                                   value="<?php echo $book['pages']; ?>">
                        </div>
                    </div>
                </div>

                <!-- Library Details -->
                <div class="form-section">
                    <h3><i class="fas fa-warehouse"></i> Library Details</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shelf_location" class="form-label">Shelf Location</label>
                            <input type="text" id="shelf_location" name="shelf_location" class="form-input" 
                                   placeholder="e.g., A1-B2" 
                                   value="<?php echo htmlspecialchars($book['shelf_location']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="total_copies" class="form-label required">Total Copies</label>
                            <input type="number" id="total_copies" name="total_copies" class="form-input" 
                                   min="<?php echo $book['total_copies'] - $book['available_copies']; ?>" 
                                   value="<?php echo $book['total_copies']; ?>" required>
                            <small class="form-help">
                                Currently issued: <?php echo $book['total_copies'] - $book['available_copies']; ?> copies
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price" class="form-label">Price (KSH)</label>
                            <input type="number" id="price" name="price" class="form-input" 
                                   min="0" step="0.01" 
                                   value="<?php echo $book['price']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="Active" <?php echo $book['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $book['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-textarea" rows="3" 
                                  placeholder="Brief description of the book..."><?php echo htmlspecialchars($book['description']); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Book
                </button>
                <a href="view.php?id=<?php echo $book_id; ?>" class="btn btn-info">
                    <i class="fas fa-eye"></i> View Details
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.form-container {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.book-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    border-left: 4px solid #2563eb;
}

.book-info h3 {
    margin: 0 0 10px 0;
    color: #2563eb;
}

.book-info p {
    margin: 5px 0;
    color: #6b7280;
}

.form-grid {
    display: grid;
    gap: 30px;
}

.form-section {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
}

.form-section h3 {
    margin: 0 0 20px 0;
    color: #374151;
    font-size: 18px;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 10px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #374151;
}

.form-label.required::after {
    content: ' *';
    color: #ef4444;
}

.form-help {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #6b7280;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php include '../includes/footer.php'; ?>