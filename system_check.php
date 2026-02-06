<?php
/**
 * System Status Check for Drumvale Library Management System
 * Run this file to verify all dependencies and configurations
 */

// Prevent direct access in production
if (!isset($_GET['check']) || $_GET['check'] !== 'system') {
    die('Access denied. Use: system_check.php?check=system');
}

echo "<!DOCTYPE html>\n";
echo "<html><head><title>System Check - Drumvale Library</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .check { margin: 10px 0; padding: 10px; border-radius: 5px; }
    .pass { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    h1 { color: #2563eb; }
    .icon { margin-right: 10px; }
</style></head><body>";

echo "<h1>🔧 Drumvale Library System Check</h1>";

$checks = [];

// PHP Version Check
$php_version = phpversion();
$php_ok = version_compare($php_version, '8.0.0', '>=');
$checks[] = [
    'name' => 'PHP Version',
    'status' => $php_ok ? 'pass' : 'fail',
    'message' => "PHP $php_version " . ($php_ok ? '✅ OK' : '❌ Requires PHP 8.0+')
];

// Required PHP Extensions
$required_extensions = ['pdo', 'pdo_mysql', 'session', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    $checks[] = [
        'name' => "PHP Extension: $ext",
        'status' => $loaded ? 'pass' : 'fail',
        'message' => $loaded ? '✅ Loaded' : '❌ Missing'
    ];
}

// Database Connection Test
try {
    include 'includes/db_connect.php';
    $checks[] = [
        'name' => 'Database Connection',
        'status' => 'pass',
        'message' => '✅ Connected successfully'
    ];
    
    // Test database structure
    $tables = ['admins', 'books', 'members', 'transactions', 'fines', 'system_settings', 'activity_log'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $checks[] = [
                'name' => "Table: $table",
                'status' => 'pass',
                'message' => '✅ Exists'
            ];
        } catch (PDOException $e) {
            $checks[] = [
                'name' => "Table: $table",
                'status' => 'fail',
                'message' => '❌ Missing or inaccessible'
            ];
        }
    }
    
    // Check for admin user
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins WHERE status = 'Active'");
        $admin_count = $stmt->fetch()['count'];
        $checks[] = [
            'name' => 'Admin Users',
            'status' => $admin_count > 0 ? 'pass' : 'warning',
            'message' => $admin_count > 0 ? "✅ $admin_count active admin(s)" : '⚠️ No active admin users found'
        ];
    } catch (PDOException $e) {
        $checks[] = [
            'name' => 'Admin Users',
            'status' => 'fail',
            'message' => '❌ Cannot check admin users'
        ];
    }
    
} catch (Exception $e) {
    $checks[] = [
        'name' => 'Database Connection',
        'status' => 'fail',
        'message' => '❌ Failed: ' . $e->getMessage()
    ];
}

// File Permissions Check
$critical_files = [
    'includes/db_connect.php',
    'login.php',
    'index.php',
    'assets/css/main.css',
    'assets/js/main.js'
];

foreach ($critical_files as $file) {
    $readable = is_readable($file);
    $checks[] = [
        'name' => "File: $file",
        'status' => $readable ? 'pass' : 'fail',
        'message' => $readable ? '✅ Readable' : '❌ Not readable'
    ];
}

// Directory Structure Check
$required_dirs = ['books', 'members', 'transactions', 'fines', 'reports', 'settings', 'assets', 'includes', 'database'];
foreach ($required_dirs as $dir) {
    $exists = is_dir($dir);
    $checks[] = [
        'name' => "Directory: $dir",
        'status' => $exists ? 'pass' : 'fail',
        'message' => $exists ? '✅ Exists' : '❌ Missing'
    ];
}

// Display Results
$pass_count = 0;
$fail_count = 0;
$warning_count = 0;

foreach ($checks as $check) {
    echo "<div class='check {$check['status']}'>";
    echo "<strong>{$check['name']}:</strong> {$check['message']}";
    echo "</div>";
    
    if ($check['status'] === 'pass') $pass_count++;
    elseif ($check['status'] === 'fail') $fail_count++;
    else $warning_count++;
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<div class='check " . ($fail_count === 0 ? 'pass' : 'fail') . "'>";
echo "<strong>Total Checks:</strong> " . count($checks) . " | ";
echo "<strong>Passed:</strong> $pass_count | ";
echo "<strong>Failed:</strong> $fail_count | ";
echo "<strong>Warnings:</strong> $warning_count";
echo "</div>";

if ($fail_count === 0) {
    echo "<div class='check pass'>";
    echo "<strong>🎉 System Status:</strong> All critical checks passed! Your system is ready to use.";
    echo "</div>";
    echo "<p><a href='login.php'>→ Go to Login Page</a></p>";
} else {
    echo "<div class='check fail'>";
    echo "<strong>⚠️ System Status:</strong> $fail_count critical issues found. Please resolve them before using the system.";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>System check completed at " . date('Y-m-d H:i:s') . "</small></p>";
echo "</body></html>";
?>