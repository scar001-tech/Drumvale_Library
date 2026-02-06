<?php
// Check and fix admin credentials
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'drumvale_library';

try {
    $pdo = new PDO("mysql:host=$host;port=3307;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Current Admin Users:</h2>";
    
    // Check existing admin users
    $stmt = $pdo->query("SELECT admin_id, username, full_name, status, created_at FROM admins");
    $admins = $stmt->fetchAll();
    
    if (empty($admins)) {
        echo "❌ No admin users found!<br><br>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Status</th><th>Created</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr>";
            echo "<td>" . $admin['admin_id'] . "</td>";
            echo "<td>" . $admin['username'] . "</td>";
            echo "<td>" . $admin['full_name'] . "</td>";
            echo "<td>" . $admin['status'] . "</td>";
            echo "<td>" . $admin['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // Update existing admin user with correct password hash
    $correct_password = 'admin123';
    $password_hash = password_hash($correct_password, PASSWORD_DEFAULT);
    
    echo "<h2>Updating Admin User Password:</h2>";
    
    // Update existing admin user
    $stmt = $pdo->prepare("UPDATE admins SET password_hash = ?, full_name = ?, email = ?, status = 'Active' WHERE username = 'admin'");
    $result = $stmt->execute([
        $password_hash,
        'Library Administrator',
        'admin@drumvale.edu'
    ]);
    
    if ($result) {
        echo "✅ Admin user updated successfully!<br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong><br>";
        echo "Password Hash: " . substr($password_hash, 0, 50) . "...<br><br>";
        
        // Verify the password works
        if (password_verify($correct_password, $password_hash)) {
            echo "✅ Password verification test: PASSED<br>";
        } else {
            echo "❌ Password verification test: FAILED<br>";
        }
        
        echo "<br><a href='login.php'>→ Go to Login Page</a>";
    } else {
        echo "❌ Failed to update admin user<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>