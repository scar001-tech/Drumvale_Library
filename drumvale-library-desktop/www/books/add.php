<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$page_title = "Add New Book";
include '../includes/header.php';
include '../includes/db_connect.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Robust Database Migration for Ledger Schema
    $ledger_cols = [
        'registration_date' => 'DATE',
        'copy_number' => 'VARCHAR(100)',
        'classification_number' => 'VARCHAR(100)',
        'last_borrowed_date' => 'DATE',
        'last_returned_date' => 'DATE',
        'borrower_class' => 'VARCHAR(100)',
        'borrower_name' => 'VARCHAR(100)',
        'pages' => 'INT'
    ];
    foreach ($ledger_cols as $col => $type) {
        try {
            $pdo->query("SELECT $col FROM books LIMIT 1");
        } catch (Exception $e) {
            try { $pdo->exec("ALTER TABLE books ADD COLUMN $col $type"); } catch (Exception $e2) {}
        }
    }

    // Collect new ledger fields
    $registration_date = $_POST['registration_date'] ?: date('Y-m-d');
    $accession_number = trim($_POST['accession_number'] ?? '');
    $copy_number = trim($_POST['copy_number'] ?? '');
    $classification_number = trim($_POST['classification_number'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $publication_year = intval($_POST['publication_year'] ?? 0);
    $pages = intval($_POST['pages'] ?? 0);
    
    // Historical/Ledger borrower info
    $last_borrowed_date = $_POST['last_borrowed_date'] ?: null;
    $last_returned_date = $_POST['last_returned_date'] ?: null;
    $borrower_class = trim($_POST['borrower_class'] ?? '');
    $borrower_name = trim($_POST['borrower_name'] ?? '');

    // Other existing fields
    $isbn = trim($_POST['isbn'] ?? '');
    $subject = trim($_POST['subject'] ?? 'English');
    $category = trim($_POST['category'] ?? 'Textbook');
    $shelf_location = trim($_POST['shelf_location'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $num_copies = max(1, intval($_POST['num_copies'] ?? 1));
    
    // Validation
    if (empty($title) || empty($author)) {
        $error_message = 'Title and Author are required fields.';
    } else {
        try {
            $inserted_count = 0;
            
            // Get base accession number if auto-generating
            $next_base_num = 0;
            if (empty($accession_number)) {
                $stmt = $pdo->query("SELECT MAX(CAST(SUBSTR(accession_number, 2) AS INTEGER)) as max_num FROM books WHERE accession_number LIKE 'B%'");
                $result = $stmt->fetch();
                $next_base_num = ($result['max_num'] ?? 0) + 1;
            }

            // Start transaction for multiple copies
            $pdo->beginTransaction();

            for ($c = 1; $c <= $num_copies; $c++) {
                if (empty($accession_number)) {
                    $current_accession = 'B' . str_pad($next_base_num + $c - 1, 3, '0', STR_PAD_LEFT);
                } else {
                    // If user provides a base but wants multiple copies, append index
                    $current_accession = ($num_copies > 1) ? $accession_number . "-" . $c : $accession_number;
                }

                $current_copy_label = $copy_number;
                if ($num_copies > 1) {
                    $current_copy_label = (empty($copy_number) ? "Copy" : $copy_number) . " " . $c;
                }

                $stmt = $pdo->prepare("
                    INSERT INTO books (
                        registration_date, accession_number, copy_number, classification_number, 
                        author, title, publisher, publication_year, pages, 
                        last_borrowed_date, last_returned_date, borrower_class, borrower_name,
                        isbn, subject, category, shelf_location, total_copies, available_copies, price, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, 'Active')
                ");

                $stmt->execute([
                    $registration_date, $current_accession, $current_copy_label, $classification_number,
                    $author, $title, $publisher, $publication_year ?: null, $pages ?: null,
                    $last_borrowed_date, $last_returned_date, $borrower_class, $borrower_name,
                    $isbn ?: null, $subject, $category, $shelf_location,
                    $price ?: null
                ]);

                $book_id = $pdo->lastInsertId();
                $inserted_count++;

                // Log activity for each copy
                logActivity($pdo, $_SESSION['admin_id'], 'CREATE', 'books', $book_id, null, [
                    'title' => $title,
                    'accession_number' => $current_accession,
                    'copy_number' => $current_copy_label
                ]);
            }

            $pdo->commit();
            $success_message = "Successfully added $inserted_count copies of '$title'.";
            
            // Clear POST for next entry
            $_POST = [];
            
        } catch (PDOException $e) {
            $pdo->rollBack(); // Rollback transaction on error
            // Check if we need to add columns on the fly (Migration fallback)
            if (strpos($e->getMessage(), 'column') !== false) {
                 $error_message = "Database columns missing. Please run the system update or contact support.";
                 // Try to auto-fix if possible
                 try {
                     $pdo->exec("ALTER TABLE books ADD COLUMN registration_date DATE, ADD COLUMN copy_number VARCHAR(50), ADD COLUMN classification_number VARCHAR(100), ADD COLUMN last_borrowed_date DATE, ADD COLUMN last_returned_date DATE, ADD COLUMN borrower_class VARCHAR(100), ADD COLUMN borrower_name VARCHAR(100)");
                     $error_message = "Database updated! Please try submitting again.";
                 } catch (Exception $e2) {}
            } else {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// ... subjects logic remains same ...
try {
    $subjects_stmt = $pdo->query("SELECT DISTINCT subject FROM books WHERE status != 'Deleted' ORDER BY subject");
    $existing_subjects = $subjects_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $existing_subjects = [];
}
$kcse_subjects = ['Mathematics', 'English', 'Kiswahili', 'Biology', 'Chemistry', 'Physics', 'History', 'Geography', 'CRE', 'IRE', 'HRE', 'Business Studies', 'Agriculture', 'Home Science', 'Art & Design', 'Music', 'German', 'French', 'Arabic', 'Computer Studies', 'Literature'];
$all_subjects = array_unique(array_merge($kcse_subjects, $existing_subjects));
sort($all_subjects);
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-book-medical"></i> Register New Book</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" class="book-form">
            <div class="form-grid">
                <!-- Ledger Information -->
                <div class="form-section">
                    <h3><i class="fas fa-file-invoice"></i> Primary Register Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Date of Registration</label>
                            <input type="date" name="registration_date" class="form-input" value="<?php echo $_POST['registration_date'] ?? date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Accession Number</label>
                            <input type="text" name="accession_number" class="form-input" placeholder="B001 (Auto if empty)" value="<?php echo htmlspecialchars($_POST['accession_number'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Number of Copies</label>
                            <input type="number" name="num_copies" class="form-input" value="<?php echo $_POST['num_copies'] ?? '1'; ?>" min="1">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Copy Number</label>
                            <input type="text" name="copy_number" class="form-input" placeholder="e.g. Copy 1" value="<?php echo htmlspecialchars($_POST['copy_number'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Classification Number</label>
                            <input type="text" name="classification_number" class="form-input" placeholder="e.g. 510.7" value="<?php echo htmlspecialchars($_POST['classification_number'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Book Information -->
                <div class="form-section">
                    <h3><i class="fas fa-book"></i> Book Specifications</h3>
                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label required">Title</label>
                            <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required">Author(s)</label>
                            <input type="text" name="author" class="form-input" value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Publisher</label>
                            <input type="text" name="publisher" class="form-input" value="<?php echo htmlspecialchars($_POST['publisher'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Publication Year</label>
                            <input type="number" name="publication_year" class="form-input" value="<?php echo htmlspecialchars($_POST['publication_year'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pagination (Pages)</label>
                            <input type="number" name="pages" class="form-input" value="<?php echo htmlspecialchars($_POST['pages'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Historical Borrower Info -->
                <div class="form-section highlight-section">
                    <h3><i class="fas fa-history"></i> Current/Last Borrower Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Date Borrowed</label>
                            <input type="date" name="last_borrowed_date" class="form-input" value="<?php echo $_POST['last_borrowed_date'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date Returned</label>
                            <input type="date" name="last_returned_date" class="form-input" value="<?php echo $_POST['last_returned_date'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Class/Grade</label>
                            <input type="text" name="borrower_class" class="form-input" placeholder="e.g. Form 4 North" value="<?php echo htmlspecialchars($_POST['borrower_class'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teacher / Student Name</label>
                            <input type="text" name="borrower_name" class="form-input" placeholder="Full name of borrower" value="<?php echo htmlspecialchars($_POST['borrower_name'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save to Register
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.highlight-section {
    background-color: #f8fafc;
    border: 2px solid #e2e8f0 !important;
}
.form-container {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    max-width: 900px;
    margin: 0 auto;
}
.form-grid { display: grid; gap: 25px; }
.form-section { border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; }
.form-section h3 { margin: 0 0 15px 0; font-size: 1.1rem; color: #1e293b; border-bottom: 2px solid #3b82f6; display: inline-block; padding-bottom: 5px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { margin-bottom: 15px; }
.form-label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem; }
.form-label.required::after { content: ' *'; color: #ef4444; }
.form-input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; }
.form-actions { margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end; }
</style>

<style>
.form-container {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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