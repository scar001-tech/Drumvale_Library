<?php
/**
 * Bulk Upload Process for Books
 * Handles CSV, XLS, and XLSX file uploads with flexible column mapping
 * Accepts files with ANY column names - users map columns in step 2
 */
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

include '../includes/db_connect.php';

$page_title = "Bulk Upload Books";
$step = isset($_POST['step']) ? intval($_POST['step']) : 1;

// Valid subjects and categories from database schema
$valid_subjects = [
    'Mathematics', 'English', 'Kiswahili', 'Biology', 'Chemistry', 'Physics',
    'History', 'Geography', 'CRE', 'IRE', 'HRE', 'French', 'German',
    'Business Studies', 'Agriculture', 'Home Science', 'Art & Design',
    'Music', 'Computer Studies', 'Literature'
];

$valid_categories = ['Textbook', 'Novel', 'Reference', 'Magazine', 'Journal'];

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Parse CSV file
 */
function parseCSV($filepath) {
    $data = [];
    $headers = [];
    
    if (($handle = fopen($filepath, 'r')) !== false) {
        $row_num = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $row_num++;
            if ($row_num === 1) {
                $headers = $row;
            }
            $data[] = $row;
        }
        fclose($handle);
    }
    
    return ['headers' => $headers, 'data' => $data];
}

/**
 * Parse XLSX file (using SimpleXML - no external dependencies)
 */
function parseXLSX($filepath) {
    $data = [];
    $headers = [];
    
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return ['headers' => [], 'data' => [], 'error' => 'Cannot open XLSX file'];
    }
    
    // Read shared strings
    $sharedStrings = [];
    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedStringsXml !== false) {
        $xml = simplexml_load_string($sharedStringsXml);
        if ($xml) {
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } elseif (isset($si->r)) {
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }
    }
    
    // Read worksheet data
    $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($worksheetXml === false) {
        $zip->close();
        return ['headers' => [], 'data' => [], 'error' => 'Cannot read worksheet'];
    }
    
    $xml = simplexml_load_string($worksheetXml);
    if (!$xml) {
        $zip->close();
        return ['headers' => [], 'data' => [], 'error' => 'Invalid worksheet format'];
    }
    
    $rows = [];
    
    if (isset($xml->sheetData->row)) {
        foreach ($xml->sheetData->row as $row) {
            $row_data = [];
            $max_col = 0;
            
            foreach ($row->c as $cell) {
                $value = '';
                $type = isset($cell['t']) ? (string) $cell['t'] : '';
                
                if ($type === 's') {
                    $idx = (int) $cell->v;
                    $value = isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
                } elseif (isset($cell->v)) {
                    $value = (string) $cell->v;
                }
                
                $cell_ref = (string) $cell['r'];
                preg_match('/^([A-Z]+)/', $cell_ref, $matches);
                $col_letter = $matches[1] ?? 'A';
                $col_index = columnLetterToIndex($col_letter);
                
                // Fill gaps with empty strings
                while (count($row_data) < $col_index) {
                    $row_data[] = '';
                }
                $row_data[$col_index] = $value;
                $max_col = max($max_col, $col_index);
            }
            
            // Ensure consistent column count
            while (count($row_data) <= $max_col) {
                $row_data[] = '';
            }
            
            $rows[] = $row_data;
        }
    }
    
    $zip->close();
    
    if (!empty($rows)) {
        $headers = $rows[0];
    }
    
    return ['headers' => $headers, 'data' => $rows];
}

/**
 * Convert Excel column letter to index
 */
function columnLetterToIndex($letter) {
    $letter = strtoupper($letter);
    $index = 0;
    $len = strlen($letter);
    for ($i = 0; $i < $len; $i++) {
        $index = $index * 26 + (ord($letter[$i]) - ord('A') + 1);
    }
    return $index - 1;
}

/**
 * Parse XLS file
 */
function parseXLS($filepath) {
    return [
        'headers' => [], 
        'data' => [], 
        'error' => 'XLS format (Excel 97-2003) is not fully supported. Please save your file as XLSX (Excel 2007+) or CSV format.'
    ];
}

/**
 * Generate next accession number
 */
function generateAccessionNumber($pdo) {
    try {
        $stmt = $pdo->query("SELECT MAX(CAST(SUBSTR(accession_number, 2) AS INTEGER)) as max_num FROM books WHERE accession_number LIKE 'B%'");
        $result = $stmt->fetch();
        $next_num = ($result['max_num'] ?? 0) + 1;
        return 'B' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        return 'B' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
    }
}

/**
 * Match subject to valid subject list
 */
function matchSubject($input, $valid_subjects) {
    $input = trim($input);
    if (empty($input)) {
        return 'English'; // Default subject
    }
    
    // Direct match
    foreach ($valid_subjects as $subject) {
        if (strcasecmp($input, $subject) === 0) {
            return $subject;
        }
    }
    
    // Partial match
    $input_lower = strtolower($input);
    foreach ($valid_subjects as $subject) {
        if (stripos($subject, $input) !== false || stripos($input, strtolower($subject)) !== false) {
            return $subject;
        }
    }
    
    // Common mappings
    $mappings = [
        'math' => 'Mathematics',
        'maths' => 'Mathematics',
        'bio' => 'Biology',
        'chem' => 'Chemistry',
        'phy' => 'Physics',
        'hist' => 'History',
        'geo' => 'Geography',
        'lit' => 'Literature',
        'eng' => 'English',
        'kisw' => 'Kiswahili',
        'swahili' => 'Kiswahili',
        'comp' => 'Computer Studies',
        'ict' => 'Computer Studies',
        'computer' => 'Computer Studies',
        'bus' => 'Business Studies',
        'business' => 'Business Studies',
        'agric' => 'Agriculture',
        'home' => 'Home Science',
        'art' => 'Art & Design',
        'music' => 'Music',
        'french' => 'French',
        'german' => 'German',
        'cre' => 'CRE',
        'ire' => 'IRE',
        'hre' => 'HRE',
        'christian' => 'CRE',
        'islamic' => 'IRE',
        'hindu' => 'HRE',
        'religion' => 'CRE',
        'general' => 'English',
        'science' => 'Biology',
    ];
    
    foreach ($mappings as $key => $subject) {
        if (stripos($input_lower, $key) !== false) {
            return $subject;
        }
    }
    
    // Default
    return 'English';
}

/**
 * Match category to valid category list
 */
function matchCategory($input, $valid_categories) {
    $input = trim($input);
    if (empty($input)) {
        return 'Textbook';
    }
    
    // Direct match
    foreach ($valid_categories as $category) {
        if (strcasecmp($input, $category) === 0) {
            return $category;
        }
    }
    
    // Common mappings
    $input_lower = strtolower($input);
    $mappings = [
        'text' => 'Textbook',
        'book' => 'Textbook',
        'course' => 'Textbook',
        'fiction' => 'Novel',
        'story' => 'Novel',
        'novel' => 'Novel',
        'ref' => 'Reference',
        'dictionary' => 'Reference',
        'encyclopedia' => 'Reference',
        'mag' => 'Magazine',
        'periodical' => 'Magazine',
        'journal' => 'Journal',
        'research' => 'Journal',
    ];
    
    foreach ($mappings as $key => $category) {
        if (stripos($input_lower, $key) !== false) {
            return $category;
        }
    }
    
    return 'Textbook';
}

// Define expected fields for mapping (matching school ledger format)
$expected_fields = [
    'registration_date'    => ['label' => 'Date of Registration', 'required' => false, 'description' => 'Date the book was registered'],
    'accession_number'     => ['label' => 'Accession Number', 'required' => false, 'description' => 'Unique library ID'],
    'copy_number'          => ['label' => 'Copy Number', 'required' => false, 'description' => 'e.g. Copy 1'],
    'classification_number'=> ['label' => 'Classification No', 'required' => false, 'description' => 'Dewey/Classification code'],
    'author'               => ['label' => 'Author(s)', 'required' => false, 'description' => 'Writer name'],
    'title'                => ['label' => 'Title', 'required' => true, 'description' => 'Book title (REQUIRED)'],
    'publisher'            => ['label' => 'Publisher', 'required' => false, 'description' => 'Publishing house'],
    'publication_year'     => ['label' => 'Publication Year', 'required' => false, 'description' => 'Year of release'],
    'pages'                => ['label' => 'Pagination', 'required' => false, 'description' => 'Number of pages'],
    'last_borrowed_date'   => ['label' => 'Date Borrowed', 'required' => false, 'description' => 'Historical borrow date'],
    'last_returned_date'   => ['label' => 'Date Returned', 'required' => false, 'description' => 'Historical return date'],
    'borrower_class'       => ['label' => 'Class/Grade', 'required' => false, 'description' => 'Class of borrower'],
    'borrower_name'        => ['label' => 'Teacher/Student', 'required' => false, 'description' => 'Name of borrower'],
    'subject'              => ['label' => 'Subject', 'required' => false, 'description' => 'e.g. Mathematics'],
    'category'             => ['label' => 'Category', 'required' => false, 'description' => 'e.g. Textbook'],
    'price'                => ['label' => 'Price', 'required' => false, 'description' => 'Cost in KSh']
];

$errors = [];
$warnings = [];
$success_count = 0;
$error_count = 0;
$imported_books = [];
$parsed_data = null;
$file_headers = [];

/**
 * Automatically map file columns to database fields
 * Now handles much more aggressive synonym matching
 */
function autoMapColumns($headers, $expected_fields) {
    $mapping = [];
    $title_found = false;
    $mapped_fields = [];
    
    // Synonyms for better detection (Aggressive Matching)
    $synonyms = [
        'registration_date' => ['registered', 'reg date', 'date of reg', 'entry date', 'date registered', 'created', 'admission date'],
        'copy_number' => ['copy', 'copy no', 'copy number', 'vol', 'volume', 'edition count', 'copy id'],
        'classification_number' => ['class', 'classification', 'class no', 'call number', 'dewey', 'ddc', 'shelf no', 'cat no'],
        'title' => ['name', 'book', 'heading', 'title', 'descriptor', 'label', 'text', 'subject title', 'book name'],
        'accession_number' => ['acc', 'id', 'no', 'number', 'accession', 'serial', 'barcode', 'code', 'ref', 'identifier', 'identity'],
        'author' => ['author', 'writer', 'creator', 'by', 'composer', 'person', 'authors', 'written by'],
        'isbn' => ['isbn', 'isbn10', 'isbn13', 'standard', 'international', 'book code'],
        'subject' => ['subject', 'course', 'topic', 'area', 'field', 'dept', 'department', 'discipline'],
        'category' => ['type', 'category', 'genre', 'class', 'group', 'book type', 'nature'],
        'pages' => ['page', 'pages', 'pagination', 'pagation', 'pg', 'size', 'length'],
        'last_borrowed_date' => ['borrowed', 'date borrowed', 'borrow date', 'out date', 'out', 'issued on'],
        'last_returned_date' => ['returned', 'date returned', 'return date', 'in date', 'in', 'collection date'],
        'borrower_class' => ['grade', 'form', 'class', 'year group', 'stream', 'dept'],
        'borrower_name' => ['teacher', 'borrower', 'student', 'staff', 'taken by', 'issued to', 'user'],
        'publisher' => ['publisher', 'pub', 'press', 'firm', 'house', 'published by'],
        'publication_year' => ['year', 'date', 'pub year', 'published', 'print date'],
        'price' => ['price', 'cost', 'ksh', 'value', 'amount', 'funding', 'worth'],
        'shelf_location' => ['shelf', 'location', 'rack', 'row', 'position', 'place', 'room', 'section']
    ];
    
    foreach ($headers as $idx => $header) {
        $header_lower = strtolower(trim($header));
        if (empty($header_lower)) continue;

        $header_clean = str_replace(['_', '-', '.', ' '], '', $header_lower);
        
        foreach ($expected_fields as $field_key => $field_info) {
            // Skip if this field is already mapped
            if (in_array($field_key, $mapped_fields)) continue;

            $field_lower = strtolower($field_key);
            $label_lower = strtolower(str_replace(' ', '', $field_info['label']));
            
            $is_match = false;
            
            // Match strategies
            if ($header_lower === $field_lower || $header_clean === $field_lower || $header_lower === $label_lower) {
                $is_match = true;
            } else {
                // Check synonyms
                if (isset($synonyms[$field_key])) {
                    foreach ($synonyms[$field_key] as $syn) {
                        if (strpos($header_lower, $syn) !== false || strpos($syn, $header_lower) !== false) {
                            $is_match = true;
                            break;
                        }
                    }
                }
            }
            
            if ($is_match) {
                $mapping[$idx] = $field_key;
                $mapped_fields[] = $field_key;
                if ($field_key === 'title') $title_found = true;
                break;
            }
        }
    }

    // IF NO TITLE FOUND, BE AGGRESSIVE: 
    // Usually the first or second column is the title if it's not a number/date
    if (!$title_found && count($headers) > 0) {
        $mapping[0] = 'title';
        $title_found = true;
    }

    return ['mapping' => $mapping, 'title_found' => $title_found];
}

// ============================================
// STEP 1: File Upload & Auto-Mapping
// ============================================
if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    $has_header = isset($_POST['has_header']) && $_POST['has_header'] == '1';
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds maximum upload size',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds maximum form size',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        ];
        $errors[] = $error_messages[$file['error']] ?? 'Unknown upload error';
    } else {
        $extension = getFileExtension($file['name']);
        $allowed_extensions = ['csv', 'xls', 'xlsx', 'ods', 'txt', 'tsv'];
        
        if (!in_array($extension, $allowed_extensions)) {
            $errors[] = "Invalid file type. Allowed types: CSV, Excel (XLSX/XLS), OpenOffice (ODS), and TSV/TXT";
        } else {
            // Save file temporarily
            $temp_dir = sys_get_temp_dir();
            $temp_file = $temp_dir . '/bulk_upload_' . session_id() . '.' . $extension;
            
            if (move_uploaded_file($file['tmp_name'], $temp_file)) {
                // Parse file
                switch ($extension) {
                    case 'csv':
                    case 'txt':
                    case 'tsv':
                        $parsed = parseCSV($temp_file);
                        break;
                    case 'xlsx':
                        $parsed = parseXLSX($temp_file);
                        break;
                    case 'ods':
                        $parsed = parseXLSX($temp_file); // ODS uses similar structure, we try basic parse
                        break;
                    case 'xls':
                        $parsed = parseXLS($temp_file);
                        break;
                }
                
                if (isset($parsed['error'])) {
                    $errors[] = $parsed['error'];
                } elseif (empty($parsed['data'])) {
                    $errors[] = "No data found in the file.";
                } else {
                    $file_headers = $has_header ? $parsed['headers'] : [];
                    
                    // If no header row, create column labels for mapping
                    if (!$has_header && !empty($parsed['data'])) {
                        $num_cols = count($parsed['data'][0]);
                        for ($i = 0; $i < $num_cols; $i++) {
                            $file_headers[] = 'Column ' . chr(65 + ($i % 26));
                        }
                    }

                    // PERFORM AUTO-MAPPING
                    $auto_map = autoMapColumns($file_headers, $expected_fields);
                    
                    // Always proceed to import, even if some fields aren't found
                    $_SESSION['bulk_upload_data'] = $parsed['data'];
                    $_SESSION['bulk_upload_file'] = $temp_file;
                    $_SESSION['bulk_upload_has_header'] = $has_header;
                    $_SESSION['bulk_upload_filename'] = $file['name'];
                    
                    $_POST['column_mapping'] = $auto_map['mapping'];
                    $step = 2; // In this script, Step 2 handling is the actual import logic
                }
            } else {
                $errors[] = "Failed to upload file.";
            }
        }
    }
}

// STEP 2: Process Import (Automated Zero-Click)
if ($step === 2 && isset($_POST['column_mapping'])) {
    $column_mapping = $_POST['column_mapping'];
    
    if (!isset($_SESSION['bulk_upload_data'])) {
        $step = 1;
        $errors[] = "Session expired. Please upload the file again.";
    } else {
        $parsed_data = $_SESSION['bulk_upload_data'];
        $has_header = $_SESSION['bulk_upload_has_header'];
        $filename = $_SESSION['bulk_upload_filename'] ?? 'Unknown';
        
        // Find title index
        $title_col_idx = null;
        foreach ($column_mapping as $file_col => $db_field) {
            if ($db_field === 'title') {
                $title_col_idx = intval($file_col);
                break;
            }
        }
        
        // Progress to Results
        $step = 3;
        $start_row = $has_header ? 1 : 0;

        // DB Alignment
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
            try { $pdo->query("SELECT $col FROM books LIMIT 1"); } 
            catch (Exception $e) { try { $pdo->exec("ALTER TABLE books ADD COLUMN $col $type"); } catch (Exception $e2) {} }
        }

        $insert_stmt = $pdo->prepare("
            INSERT INTO books (
                registration_date, accession_number, copy_number, classification_number, 
                author, title, publisher, publication_year, pages, 
                last_borrowed_date, last_returned_date, borrower_class, borrower_name,
                subject, category, shelf_location, total_copies, available_copies, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 'Active')
        ");

        for ($i = $start_row; $i < count($parsed_data); $i++) {
            $row = $parsed_data[$i];
            $row_num = $i + 1;
            
            if (count(array_filter($row, fn($v) => trim($v) !== '')) === 0) continue;

            $title = ($title_col_idx !== null && isset($row[$title_col_idx])) ? trim($row[$title_col_idx]) : '';
            if (empty($title)) $title = "Untitled (Missing Details)";

            $book = [];
            foreach ($expected_fields as $f_key => $f_info) $book[$f_key] = null;
            foreach ($column_mapping as $file_idx => $db_field) {
                if ($db_field && $db_field !== 'skip' && isset($row[$file_idx])) {
                    $book[$db_field] = trim($row[$file_idx]);
                }
            }

            foreach(['registration_date', 'last_borrowed_date', 'last_returned_date'] as $d_f) {
                if (!empty($book[$d_f])) {
                    $ts = strtotime($book[$d_f]);
                    $book[$d_f] = $ts ? date('Y-m-d', $ts) : null;
                }
            }
            if (empty($book['registration_date'])) $book['registration_date'] = date('Y-m-d');
            
            $book['publication_year'] = !empty($book['publication_year']) ? intval(preg_replace('/[^0-9]/', '', $book['publication_year'])) : null;
            $book['pages'] = !empty($book['pages']) ? intval(preg_replace('/[^0-9]/', '', $book['pages'])) : null;
            if (empty($book['accession_number'])) $book['accession_number'] = generateAccessionNumber($pdo);

            // Fill in 'Missing Details' for empty essential strings
            $string_fields = ['author', 'publisher', 'classification_number', 'copy_number', 'borrower_class', 'borrower_name', 'shelf_location'];
            foreach ($string_fields as $sf) {
                if (empty($book[$sf])) $book[$sf] = ($sf === 'shelf_location') ? 'General' : 'Missing Details';
            }

            try {
                $insert_stmt->execute([
                    $book['registration_date'], 
                    $book['accession_number'], 
                    $book['copy_number'], 
                    $book['classification_number'],
                    $book['author'], 
                    $title, 
                    $book['publisher'], 
                    $book['publication_year'], 
                    $book['pages'],
                    $book['last_borrowed_date'], 
                    $book['last_returned_date'], 
                    $book['borrower_class'], 
                    $book['borrower_name'],
                    matchSubject($book['subject'] ?? 'English', $valid_subjects),
                    matchCategory($book['category'] ?? 'Textbook', $valid_categories),
                    $book['shelf_location']
                ]);
                $success_count++;
                $imported_books[] = ['accession_number' => $book['accession_number'], 'title' => $title, 'author' => $book['author'] ?: 'Unknown'];
            } catch (PDOException $e) {
                $error_count++;
                $errors[] = "Row $row_num: " . $e->getMessage();
            }
        }
        
        unset($_SESSION['bulk_upload_data'], $_SESSION['bulk_upload_file'], $_SESSION['bulk_upload_has_header'], $_SESSION['bulk_upload_filename']);
        if (isset($temp_file) && file_exists($temp_file)) @unlink($temp_file);
    }
}

include '../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-file-import"></i> Bulk Upload Books</h1>
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Books
            </a>
        </div>
    </div>

    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step <?php echo $step === 1 ? 'active' : 'completed'; ?>">
            <div class="step-number"><?php echo $step > 1 ? '<i class="fas fa-check"></i>' : '1'; ?></div>
            <div class="step-label">Upload & Process</div>
        </div>
        <div class="step-line <?php echo $step > 1 ? 'active' : ''; ?>"></div>
        <div class="step <?php echo $step === 3 ? 'active' : ''; ?>">
            <div class="step-number">2</div>
            <div class="step-label">Results</div>
        </div>
    </div>

    <?php if (!empty($errors) && $step !== 3): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php foreach ($errors as $error): ?>
            <div><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
    <!-- STEP 1: File Upload -->
    <div class="form-container">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Upload any school register file!</strong> The system will automatically detect your columns and import the data. Missing details will be automatically labeled.
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="step" value="1">
            
            <h3><i class="fas fa-file-alt"></i> Select Your File</h3>
            
            <div class="form-group">
                <label for="import_file">Choose File <span class="required">*</span></label>
                <input type="file" name="import_file" id="import_file" class="form-input" 
                       accept=".csv,.xls,.xlsx,.ods,.tsv,.txt,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,application/vnd.oasis.opendocument.spreadsheet" required>
                <small class="form-help">Accepted formats: CSV, Excel (.xlsx, .xls), OpenOffice (.ods), Text (.txt, .tsv)</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="has_header" value="1" checked>
                    First row contains column headers
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-magic"></i> Upload & Import Books
                </button>
            </div>
        </form>

        <div class="download-template">
            <h3><i class="fas fa-download"></i> Need a Template?</h3>
            <p>Download a sample template (optional - you can use your own format):</p>
            <a href="download_template.php" class="btn btn-success">
                <i class="fas fa-file-download"></i> Download CSV Template
            </a>
        </div>
    </div>


    <?php elseif ($step === 3): ?>
    <!-- STEP 3: Results -->
    <div class="summary-cards">
        <div class="summary-card success">
            <div class="summary-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="summary-content">
                <div class="summary-number"><?php echo $success_count; ?></div>
                <div class="summary-label">Books Imported</div>
            </div>
        </div>
        
        <div class="summary-card <?php echo $error_count > 0 ? 'error' : 'neutral'; ?>">
            <div class="summary-icon">
                <i class="fas fa-<?php echo $error_count > 0 ? 'exclamation-circle' : 'info-circle'; ?>"></i>
            </div>
            <div class="summary-content">
                <div class="summary-number"><?php echo $error_count; ?></div>
                <div class="summary-label">Errors</div>
            </div>
        </div>
    </div>

    <?php if ($success_count > 0): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <strong>Success!</strong> <?php echo $success_count; ?> book(s) have been imported successfully.
    </div>
    
    <div class="result-section">
        <h3><i class="fas fa-list"></i> Imported Books</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Accession Number</th>
                        <th>Title</th>
                        <th>Author</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($imported_books, 0, 20) as $book): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($book['accession_number']); ?></code></td>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($imported_books) > 20): ?>
            <p class="table-footer">Showing 20 of <?php echo count($imported_books); ?> imported books.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="result-section errors-section">
        <h3><i class="fas fa-exclamation-triangle"></i> Errors & Warnings</h3>
        <div class="error-list">
            <?php foreach (array_slice($errors, 0, 10) as $error): ?>
            <div class="error-item">
                <i class="fas fa-times-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endforeach; ?>
            <?php if (count($errors) > 10): ?>
            <div class="error-item warning">
                <i class="fas fa-info-circle"></i>
                ... and <?php echo count($errors) - 10; ?> more errors
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($success_count === 0 && empty($errors)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i>
        No books were imported. Please check your file has data and that the Title column is properly mapped.
    </div>
    <?php endif; ?>

    <div class="form-actions" style="margin-top: 2rem;">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Books
        </a>
        <a href="bulk_upload.php" class="btn btn-primary">
            <i class="fas fa-upload"></i> Upload More Books
        </a>
    </div>
    <?php endif; ?>
</div>

<style>
/* Progress Steps */
.progress-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: all 0.3s ease;
}

.step.active .step-number {
    background: #2563eb;
    color: white;
}

.step.completed .step-number {
    background: #10b981;
    color: white;
}

.step-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.step.active .step-label {
    color: #2563eb;
}

.step.completed .step-label {
    color: #10b981;
}

.step-line {
    width: 80px;
    height: 3px;
    background: #e5e7eb;
    margin: 0 1rem;
    margin-bottom: 1.5rem;
    transition: background 0.3s ease;
}

.step-line.active {
    background: #10b981;
}

/* Form Container */
.form-container {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-container h3 {
    margin: 0 0 1.5rem 0;
    color: #374151;
    font-size: 1.125rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-input, .form-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input {
    width: 18px;
    height: 18px;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.required {
    color: #ef4444;
}

/* Mapping Table */
.mapping-intro {
    margin-bottom: 1.5rem;
    color: #6b7280;
}

.mapping-table-container {
    overflow-x: auto;
    margin-bottom: 1rem;
}

.mapping-table {
    width: 100%;
    border-collapse: collapse;
}

.mapping-table th,
.mapping-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.mapping-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
}

.mapping-table .column-name {
    min-width: 150px;
}

.mapping-table .sample-data {
    color: #6b7280;
    font-size: 0.875rem;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mapping-select {
    min-width: 200px;
}

.mapping-legend {
    margin-top: 1rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 6px;
    font-size: 0.875rem;
    color: #6b7280;
}

/* Download Template */
.download-template {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f9fafb;
    border-radius: 8px;
}

.download-template h3 {
    margin: 0 0 0.75rem 0;
}

/* Summary Cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.summary-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border-radius: 12px;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.summary-card.success { border-left: 4px solid #10b981; }
.summary-card.error { border-left: 4px solid #ef4444; }
.summary-card.neutral { border-left: 4px solid #6b7280; }

.summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.summary-card.success .summary-icon { background: #d1fae5; color: #059669; }
.summary-card.error .summary-icon { background: #fee2e2; color: #dc2626; }
.summary-card.neutral .summary-icon { background: #f3f4f6; color: #6b7280; }

.summary-number { font-size: 2rem; font-weight: 700; line-height: 1; }
.summary-label { color: #6b7280; font-size: 0.875rem; }

/* Results */
.result-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.result-section h3 {
    margin: 0 0 1rem 0;
    color: #374151;
}

.table-container { overflow-x: auto; }

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.data-table th {
    background: #f9fafb;
    font-weight: 600;
}

.data-table code {
    background: #e5e7eb;
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
}

.table-footer {
    margin-top: 1rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.errors-section { border-left: 4px solid #ef4444; }

.error-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.error-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #fef2f2;
    border-radius: 6px;
    color: #991b1b;
    font-size: 0.875rem;
}

.error-item.warning {
    background: #fffbeb;
    color: #92400e;
}

/* Alerts */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.alert-info {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

.alert-warning {
    background: #fffbeb;
    color: #92400e;
    border: 1px solid #f59e0b;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #2563eb;
    color: white;
}

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-secondary {
    background: #e5e7eb;
    color: #374151;
}

.btn-secondary:hover {
    background: #d1d5db;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

@media (max-width: 768px) {
    .progress-steps {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .step-line {
        display: none;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .mapping-table-container {
        font-size: 0.875rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
