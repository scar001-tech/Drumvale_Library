<?php
/**
 * Database Connection Handler for Drumvale Library Management System
 * Automatically detects environment and uses appropriate database
 * - SQLite for PHP Desktop (standalone executable)
 * - MySQL for web server (XAMPP/WAMP/etc.)
 */

// Check if running in PHP Desktop environment
$is_phpdesktop = (php_sapi_name() === 'cgi-fcgi' && isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Mongoose') !== false) 
                 || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '8080')
                 || file_exists(__DIR__ . '/../phpdesktop-config.json');

if ($is_phpdesktop) {
    // Use SQLite for PHP Desktop
    require_once __DIR__ . '/db_connect_sqlite.php';
} else {
    // Use MySQL for web server environment
    $db_config = [
        'host' => 'localhost',
        'port' => '3307',
        'dbname' => 'drumvale_library',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ];

    try {
        // Create PDO connection
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
        
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true
        ]);
        
    } catch (PDOException $e) {
        // Log error and show user-friendly message
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please contact the system administrator.");
    }

    /**
     * Function to log admin activities (MySQL version)
     */
    function logActivity($pdo, $admin_id, $action, $table_affected, $record_id, $old_values = null, $new_values = null) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (admin_id, action, table_affected, record_id, old_values, new_values, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $admin_id,
                $action,
                $table_affected,
                $record_id,
                $old_values ? json_encode($old_values) : null,
                $new_values ? json_encode($new_values) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }
}

/**
 * Common functions for both database types
 */

/**
 * Function to execute prepared statements safely
 */
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        throw new Exception("Database operation failed");
    }
}

/**
 * Function to get system settings
 */
function getSystemSetting($pdo, $key, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Function to calculate fine for overdue books
 */
function calculateFine($days_overdue, $fine_rate = null) {
    global $pdo;
    
    if ($fine_rate === null) {
        $fine_rate = getSystemSetting($pdo, 'fine_rate_per_day', 5.00);
    }
    
    return $days_overdue * floatval($fine_rate);
}

/**
 * Function to check if book is available
 */
function isBookAvailable($pdo, $book_id) {
    $stmt = $pdo->prepare("SELECT available_copies FROM books WHERE book_id = ? AND status = 'Active'");
    $stmt->execute([$book_id]);
    $result = $stmt->fetch();
    
    return $result && $result['available_copies'] > 0;
}

/**
 * Function to get member borrow limit
 */
function getMemberBorrowLimit($pdo, $member_type) {
    $setting_key = strtolower($member_type) . '_borrow_limit';
    $default_limit = $member_type === 'Student' ? 2 : 5;
    
    return intval(getSystemSetting($pdo, $setting_key, $default_limit));
}

/**
 * Function to get loan duration
 */
function getLoanDuration($pdo, $member_type) {
    $setting_key = strtolower($member_type) . '_loan_duration';
    $default_duration = $member_type === 'Student' ? 14 : 30;
    
    return intval(getSystemSetting($pdo, $setting_key, $default_duration));
}