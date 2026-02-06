<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db_connect.php';

// Handle Bulk Delete Action BEFORE HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete') {
    if (!empty($_POST['selected_books'])) {
        $selected_ids = $_POST['selected_books'];
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        
        try {
            // First, check if any of the selected books are currently issued
            // We can only delete books where total_copies == available_copies
            $check_sql = "SELECT book_id, title FROM books WHERE book_id IN ($placeholders) AND total_copies != available_copies";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute($selected_ids);
            $issued_books = $check_stmt->fetchAll();

            if (!empty($issued_books)) {
                $titles = array_column($issued_books, 'title');
                $_SESSION['error_message'] = "Cannot delete: Some selected books are currently issued (" . implode(", ", array_slice($titles, 0, 3)) . (count($titles) > 3 ? "..." : "") . ")";
            } else {
                // Perform the delete (Hard delete - eliminate from database)
                $delete_sql = "DELETE FROM books WHERE book_id IN ($placeholders)";
                $delete_stmt = $pdo->prepare($delete_sql);
                $delete_stmt->execute($selected_ids);
                
                $count = count($selected_ids);
                $_SESSION['success_message'] = "Success: $count books have been permanently eliminated from the system.";
                header('Location: bulk_manage.php' . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error eliminating books: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "No books were selected for deletion!";
    }
}

// Filters logic
$author = $_GET['author'] ?? '';
$subject = $_GET['subject'] ?? '';
$category = $_GET['category'] ?? '';
$missing_info = $_GET['missing_info'] ?? '';

$where_conditions = ["1=1"];
$params = [];

if (!empty($author)) {
    $where_conditions[] = "author LIKE ?";
    $params[] = "%$author%";
}

if (!empty($subject)) {
    $where_conditions[] = "subject = ?";
    $params[] = $subject;
}

if (!empty($category)) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
}

// Special filter for missing details
if ($missing_info === 'yes') {
    $where_conditions[] = "(isbn IS NULL OR isbn = '' OR publisher IS NULL OR publisher = '' OR publication_year IS NULL OR pages IS NULL OR pages = 0)";
}

$where_clause = implode(' AND ', $where_conditions);

// Fetch filtered books
try {
    $sql = "SELECT * FROM books WHERE $where_clause ORDER BY title ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();

    // Get subjects and categories for filters
    $subjects_stmt = $pdo->query("SELECT DISTINCT subject FROM books ORDER BY subject");
    $subjects = $subjects_stmt->fetchAll(PDO::FETCH_COLUMN);

    $categories_stmt = $pdo->query("SELECT DISTINCT category FROM books ORDER BY category");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$page_title = "Bulk Book Management";
$additional_css = ['assets/css/tables.css'];
include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-tasks"></i> Bulk Book Management</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Books
            </a>
            <a href="bulk_upload.php" class="btn btn-primary">
                <i class="fas fa-upload"></i> Bulk Upload
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card filter-card" style="margin-bottom: 2rem;">
        <div class="card-header" style="background: #f8fafc; color: #1e293b; border-bottom: 1px solid #e2e8f0;">
            <h3 style="color: #1e293b;"><i class="fas fa-filter"></i> Filter Books to Manage</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="filters-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" value="<?php echo htmlspecialchars($author); ?>" class="form-input" placeholder="Search author...">
                </div>

                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject" class="form-select">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $subj): ?>
                            <option value="<?php echo htmlspecialchars($subj); ?>" <?php echo $subject === $subj ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subj); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Missing Details?</label>
                    <select name="missing_info" class="form-select">
                        <option value="">All Books</option>
                        <option value="yes" <?php echo $missing_info === 'yes' ? 'selected' : ''; ?>>With Missing Details</option>
                    </select>
                </div>

                <div class="filter-actions" style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply
                    </button>
                    <a href="bulk_manage.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" id="bulkForm">
        <div class="bulk-actions-bar" style="background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 10px; z-index: 100; border: 1px solid #e2e8f0;">
            <div class="selection-info">
                <span id="selectedCount">0</span> books selected
            </div>
            <div class="actions">
                <button type="submit" name="bulk_action" value="delete" class="btn btn-danger" onclick="return confirm('WARNING: Are you sure you want to delete all selected books? This action cannot be undone.')" id="deleteBtn" disabled>
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                        <th>Accession #</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Subject</th>
                        <th>Missing Details</th>
                        <th>Copies</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($books)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No books found matching your filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($books as $book): 
                            $missing = [];
                            if (empty($book['isbn'])) $missing[] = 'ISBN';
                            if (empty($book['publisher'])) $missing[] = 'Publisher';
                            if (empty($book['publication_year'])) $missing[] = 'Year';
                            if (empty($book['pages'])) $missing[] = 'Pages';
                        ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_books[]" value="<?php echo $book['book_id']; ?>" class="book-checkbox">
                                </td>
                                <td><?php echo htmlspecialchars($book['accession_number']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['subject']); ?></td>
                                <td>
                                    <?php if (!empty($missing)): ?>
                                        <span class="badge badge-warning" title="<?php echo implode(', ', $missing); ?>">
                                            <?php echo count($missing); ?> missing
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Complete</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $book['total_copies']; ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.book-checkbox');
    const deleteBtn = document.getElementById('deleteBtn');
    const selectedCount = document.getElementById('selectedCount');

    function updateActions() {
        const checked = document.querySelectorAll('.book-checkbox:checked').length;
        selectedCount.textContent = checked;
        deleteBtn.disabled = checked === 0;
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => {
            cb.checked = selectAll.checked;
        });
        updateActions();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateActions);
    });
});
</script>

<style>
.badge-warning {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}
.badge-success {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}
.badge {
    padding: 0.25rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.filter-card .card-body {
    padding: 1.5rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #475569;
}
</style>

<?php include '../includes/footer.php'; ?>
