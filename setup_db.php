<?php
// Quick database setup script
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'drumvale_library';

echo "Testing MySQL connection...<br>";

try {
    // Try different connection methods
    $pdo = new PDO("mysql:host=$host;port=3307", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to MySQL successfully!<br>";
    
    // Check if database exists
    $result = $pdo->query("SHOW DATABASES LIKE 'drumvale_library'");
    if ($result->rowCount() > 0) {
        echo "✅ Database 'drumvale_library' found!<br>";
        $pdo->exec("USE drumvale_library");
    } else {
        echo "❌ Database 'drumvale_library' not found. Please create it first in MySQL Workbench.<br>";
        exit;
    }
    
    // Read and execute schema
    $schema = file_get_contents('database/schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✅ Database setup completed successfully!<br>";
    echo "📚 Default admin login: admin / admin123<br>";
    echo "🔗 <a href='http://localhost/drumvale-library/'>Access the Library System</a>";
    
} catch (PDOException $e) {
    echo "❌ Database setup failed: " . $e->getMessage();
}
?>