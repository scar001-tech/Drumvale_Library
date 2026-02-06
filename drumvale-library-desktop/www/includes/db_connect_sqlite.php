<?php
// SQLite Database Connection for PHP Desktop
// This file provides database connectivity for the standalone desktop version

try {
    // Create database directory if it doesn't exist
    $db_dir = __DIR__ . '/../database';
    if (!is_dir($db_dir)) {
        mkdir($db_dir, 0755, true);
    }
    
    // SQLite database file path
    $db_file = $db_dir . '/drumvale_library.sqlite';
    
    // Create PDO connection to SQLite
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign key constraints
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    // Add custom functions for MySQL compatibility
    $pdo->sqliteCreateFunction('CURDATE', function() {
        return date('Y-m-d');
    }, 0);
    
    $pdo->sqliteCreateFunction('DATEDIFF', function($date1, $date2) {
        $d1 = new DateTime($date1);
        $d2 = new DateTime($date2);
        return $d1->diff($d2)->days * ($d1 > $d2 ? 1 : -1);
    }, 2);
    
    $pdo->sqliteCreateFunction('DATE_ADD', function($date, $interval) {
        // Simple implementation for INTERVAL X DAY format
        if (preg_match('/INTERVAL (\d+) DAY/', $interval, $matches)) {
            $days = intval($matches[1]);
            $d = new DateTime($date);
            $d->add(new DateInterval("P{$days}D"));
            return $d->format('Y-m-d');
        }
        return $date;
    }, 2);
    
    // Check if tables exist, if not create them
    $tables_check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admins'")->fetchAll();
    
    if (empty($tables_check)) {
        // Create tables for SQLite
        createTables($pdo);
        insertDefaultData($pdo);
    } else {
        // Check if we need to migrate the books table to remove subject constraint
        try {
            // Try to insert a test subject that would fail with the old constraint
            $test_stmt = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name='books'");
            $test_stmt->execute();
            $table_sql = $test_stmt->fetch()['sql'] ?? '';
            
            // If the table has any brittle CHECK constraints, we need to migrate it
            if (strpos($table_sql, "CHECK (") !== false) {
                migrateBrittleConstraints($pdo);
            }
        } catch (Exception $e) {
            // If there's any issue, just continue
        }
    }
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function createTables($pdo) {
    // Admin table
    $pdo->exec("
        CREATE TABLE admins (
            admin_id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME NULL,
            status VARCHAR(10) DEFAULT 'Active' CHECK (status IN ('Active', 'Inactive'))
        )
    ");
    
    // Books table
    $pdo->exec("
        CREATE TABLE books (
            book_id INTEGER PRIMARY KEY AUTOINCREMENT,
            accession_number VARCHAR(20) UNIQUE NOT NULL,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            subject VARCHAR(50) NOT NULL,
            category VARCHAR(50) NOT NULL,
            total_copies INTEGER NOT NULL DEFAULT 1,
            available_copies INTEGER NOT NULL DEFAULT 1,
            shelf_location VARCHAR(50) NOT NULL,
            barcode VARCHAR(50) NULL,
            isbn VARCHAR(20) NULL,
            publisher VARCHAR(100) NULL,
            publication_year INTEGER NULL,
            edition VARCHAR(50) NULL,
            pages INTEGER NULL,
            price DECIMAL(10,2) NULL,
            description TEXT NULL,
            condition_status VARCHAR(20) DEFAULT 'Good',
            status VARCHAR(20) DEFAULT 'Active',
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Members table
    $pdo->exec("
        CREATE TABLE members (
            member_id INTEGER PRIMARY KEY AUTOINCREMENT,
            unique_identifier VARCHAR(20) UNIQUE NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            member_type VARCHAR(10) NOT NULL CHECK (member_type IN ('Student', 'Teacher')),
            class_or_department VARCHAR(50) NOT NULL,
            phone_number VARCHAR(15) NULL,
            email VARCHAR(100) NULL,
            address TEXT NULL,
            parent_guardian_name VARCHAR(100) NULL,
            parent_guardian_phone VARCHAR(15) NULL,
            status VARCHAR(15) DEFAULT 'Active' CHECK (status IN ('Active', 'Left School')),
            registration_date DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Transactions table
    $pdo->exec("
        CREATE TABLE transactions (
            transaction_id INTEGER PRIMARY KEY AUTOINCREMENT,
            book_id INTEGER NOT NULL,
            member_id INTEGER NOT NULL,
            issue_date DATE NOT NULL,
            due_date DATE NOT NULL,
            return_date DATE NULL,
            status VARCHAR(10) DEFAULT 'Issued' CHECK (status IN ('Issued', 'Returned', 'Overdue', 'Lost')),
            renewal_count INTEGER DEFAULT 0,
            max_renewals INTEGER DEFAULT 2,
            handled_by INTEGER NOT NULL,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
            FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
            FOREIGN KEY (handled_by) REFERENCES admins(admin_id)
        )
    ");
    
    // Fines table
    $pdo->exec("
        CREATE TABLE fines (
            fine_id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id INTEGER NOT NULL,
            member_id INTEGER NOT NULL,
            fine_type VARCHAR(15) NOT NULL CHECK (fine_type IN ('Overdue', 'Damage', 'Lost Book', 'Other')),
            days_overdue INTEGER DEFAULT 0,
            fine_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00,
            total_fine DECIMAL(10,2) NOT NULL,
            amount_paid DECIMAL(10,2) DEFAULT 0.00,
            payment_status VARCHAR(10) DEFAULT 'Pending' CHECK (payment_status IN ('Pending', 'Partial', 'Paid', 'Waived')),
            payment_date DATE NULL,
            waived_by INTEGER NULL,
            waive_reason TEXT NULL,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE,
            FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
            FOREIGN KEY (waived_by) REFERENCES admins(admin_id)
        )
    ");
    
    // System settings table
    $pdo->exec("
        CREATE TABLE system_settings (
            setting_id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value TEXT NOT NULL,
            description TEXT NULL,
            updated_by INTEGER NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (updated_by) REFERENCES admins(admin_id)
        )
    ");
    
    // Activity log table
    $pdo->exec("
        CREATE TABLE activity_log (
            log_id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER NOT NULL,
            action VARCHAR(50) NOT NULL,
            table_affected VARCHAR(50) NOT NULL,
            record_id INTEGER NOT NULL,
            old_values TEXT NULL,
            new_values TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
        )
    ");
    
    // Create indexes
    $pdo->exec("CREATE INDEX idx_books_accession ON books(accession_number)");
    $pdo->exec("CREATE INDEX idx_books_title_author ON books(title, author)");
    $pdo->exec("CREATE INDEX idx_books_subject_category ON books(subject, category)");
    $pdo->exec("CREATE INDEX idx_members_identifier ON members(unique_identifier)");
    $pdo->exec("CREATE INDEX idx_transactions_book_status ON transactions(book_id, status)");
    $pdo->exec("CREATE INDEX idx_transactions_member_status ON transactions(member_id, status)");
}

function insertDefaultData($pdo) {
    // Insert default admin user (password: admin123)
    $pdo->exec("
        INSERT INTO admins (username, password_hash, full_name, email) VALUES 
        ('admin', '\$2y\$10\$mv7qPnGWKjzJB.ehvoOotuBmvQIyjLfd2jdJN7qvlsLyXPcpQwWuq', 'Library Administrator', 'admin@drumvale.edu')
    ");
    
    // Insert default system settings
    $settings = [
        ['student_borrow_limit', '2', 'Maximum books a student can borrow at once'],
        ['teacher_borrow_limit', '5', 'Maximum books a teacher can borrow at once'],
        ['student_loan_duration', '14', 'Default loan duration for students (days)'],
        ['teacher_loan_duration', '30', 'Default loan duration for teachers (days)'],
        ['fine_rate_per_day', '5.00', 'Fine rate per day for overdue books (KSh)'],
        ['max_renewals', '2', 'Maximum number of renewals allowed'],
        ['library_name', 'Drumvale Secondary School Library', 'Library name for reports'],
        ['academic_year', '2024', 'Current academic year']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, description, updated_by) VALUES (?, ?, ?, 1)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    
}

// Activity logging function
function logActivity($pdo, $admin_id, $action, $table_affected, $record_id, $old_values = null, $new_values = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (admin_id, action, table_affected, record_id, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'localhost';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'PHP Desktop App';
        
        $stmt->execute([
            $admin_id,
            $action,
            $table_affected,
            $record_id,
            $old_values ? json_encode($old_values) : null,
            $new_values ? json_encode($new_values) : null,
            $ip_address,
            $user_agent
        ]);
    } catch (PDOException $e) {
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

function migrateBrittleConstraints($pdo) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get existing columns to ensure we don't lose any data
        $stmt = $pdo->query("PRAGMA table_info(books)");
        $existing_cols = $stmt->fetchAll();
        $col_names = array_map(fn($c) => $c['name'], $existing_cols);
        
        // Define the columns we want in the new flexible table
        // We start with the core columns and add any others found in the existing table
        $flexible_columns = [
            'book_id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'accession_number' => 'VARCHAR(20) UNIQUE NOT NULL',
            'title' => 'VARCHAR(255) NOT NULL',
            'author' => 'VARCHAR(255) NOT NULL',
            'subject' => 'VARCHAR(50) NOT NULL',
            'category' => 'VARCHAR(50) NOT NULL',
            'total_copies' => 'INTEGER NOT NULL DEFAULT 1',
            'available_copies' => 'INTEGER NOT NULL DEFAULT 1',
            'shelf_location' => 'VARCHAR(50) NOT NULL',
            'barcode' => 'VARCHAR(50) NULL',
            'isbn' => 'VARCHAR(20) NULL',
            'publisher' => 'VARCHAR(100) NULL',
            'publication_year' => 'INTEGER NULL',
            'edition' => 'VARCHAR(50) NULL',
            'pages' => 'INTEGER NULL',
            'price' => 'DECIMAL(10,2) NULL',
            'description' => 'TEXT NULL',
            'condition_status' => 'VARCHAR(20) DEFAULT "Good"',
            'status' => 'VARCHAR(20) DEFAULT "Active"',
            'notes' => 'TEXT NULL',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ];
        
        // Add any missing columns that were in the old table (e.g. from previous migrations)
        foreach ($existing_cols as $col) {
            if (!isset($flexible_columns[$col['name']])) {
                $flexible_columns[$col['name']] = $col['type'];
            }
        }
        
        $create_parts = [];
        foreach ($flexible_columns as $name => $def) {
            $create_parts[] = "$name $def";
        }
        
        $pdo->exec("CREATE TABLE books_new (" . implode(', ', $create_parts) . ")");
        
        // Copy only columns that exist in both
        $common_cols = implode(', ', $col_names);
        $pdo->exec("INSERT INTO books_new ($common_cols) SELECT $common_cols FROM books");
        
        // Drop old table
        $pdo->exec("DROP TABLE books");
        
        // Rename new table
        $pdo->exec("ALTER TABLE books_new RENAME TO books");
        
        // Recreate indexes
        $pdo->exec("CREATE INDEX idx_books_accession ON books(accession_number)");
        $pdo->exec("CREATE INDEX idx_books_title_author ON books(title, author)");
        $pdo->exec("CREATE INDEX idx_books_subject_category ON books(subject, category)");
        
        // Commit transaction
        $pdo->commit();
        
    } catch (Exception $e) {
        // Rollback on error
        if ($pdo->inTransaction()) $pdo->rollback();
        error_log("Migration failed: " . $e->getMessage());
    }
}
?>